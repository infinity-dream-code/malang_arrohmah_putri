<?php
error_reporting(E_ALL);
ini_set("display_errors", 1);
date_default_timezone_set("Asia/Jakarta");

require __DIR__ . "/config/DbClass.php";
require __DIR__ . "/config/conn.php";
require __DIR__ . "/config/jwt.php";

header("Content-Type: application/json; charset=utf-8");
header("Access-Control-Allow-Origin: http://localhost:8000");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Access-Control-Allow-Credentials: true");

function writeLog($data): void
{
    $line = "[" . date("Y-m-d H:i:s") . "] ";
    $line .= is_array($data) || is_object($data)
        ? json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)
        : (string) $data;
    file_put_contents(__DIR__ . "/error.log", $line . "\n", FILE_APPEND);
}

function loadEnv(string $path): void
{
    if (!file_exists($path)) {
        writeLog(["level" => "ERROR", "event" => "ENV_NOT_FOUND", "path" => $path]);
        http_response_code(500);
        echo json_encode(["status" => 500, "message" => "ENV tidak ditemukan"], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $lines = file($path, FILE_IGNORE_NEW_LINES);
    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === "" || str_starts_with($line, "#")) continue;
        if (!str_contains($line, "=")) continue;
        [$name, $value] = explode("=", $line, 2);
        $name = trim($name);
        $value = trim($value);
        $value = trim($value, "\"'");
        putenv("$name=$value");
        $_ENV[$name] = $value;
        $_SERVER[$name] = $value;
    }
}

function getJsonInput(): array
{
    $raw = file_get_contents("php://input");
    $json = json_decode($raw, true);
    if (is_array($json)) return $json;
    if (!empty($_POST)) return $_POST;
    return [];
}

function dbConnectPdo(): PDO
{
    $host = (string) ($_ENV["DB_HOST"] ?? "");
    $user = (string) ($_ENV["DB_USERNAME"] ?? "");
    $pass = (string) ($_ENV["DB_PASSWORD"] ?? "");
    $port = (string) ($_ENV["DB_PORT"] ?? "3306");
    $name = (string) ($_ENV["DB_DATABASE"] ?? "");

    if ($host === "" || $user === "" || $name === "") {
        throw new RuntimeException("ENV_DB_INCOMPLETE");
    }

    $conn = new conn();
    $pdo = $conn->DBConnect([
        "host" => $host,
        "user" => $user,
        "pass" => $pass,
        "port" => $port,
        "name" => $name,
    ]);

    if (!$pdo instanceof PDO) {
        throw new RuntimeException("DBConnect tidak mengembalikan PDO");
    }

    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    $pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, true);
    return $pdo;
}

function login(array $req): void
{
    $username = trim((string) ($req["username"] ?? ""));
    $password = trim((string) ($req["password"] ?? ""));

    if ($username === "" || $password === "") {
        http_response_code(422);
        echo json_encode(["status" => 422, "message" => "Username dan password wajib diisi"], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $pdo = dbConnectPdo();

    $stmt = $pdo->prepare("SELECT * FROM kepsek_user WHERE username = :username LIMIT 1");
    $stmt->bindValue(":username", $username, PDO::PARAM_STR);
    $stmt->execute();
    $user = $stmt->fetch();

    if (!$user) {
        http_response_code(401);
        echo json_encode(["status" => 401, "message" => "Username atau password salah"], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $passwordHash = (string) ($user["password"] ?? "");
    $valid = false;

    if (strlen($passwordHash) === 32 && ctype_xdigit($passwordHash)) {
        $valid = md5($password) === $passwordHash;
    } elseif (strlen($passwordHash) === 64 && ctype_xdigit($passwordHash)) {
        $valid = hash("sha256", $password) === $passwordHash;
    } elseif (str_starts_with($passwordHash, "$2")) {
        $valid = password_verify($password, $passwordHash);
    } else {
        $valid = $password === $passwordHash;
    }

    if (!$valid) {
        http_response_code(401);
        echo json_encode(["status" => 401, "message" => "Username atau password salah"], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $key = (string) ($_ENV["JWT_KEY"] ?? "");
    if ($key === "") {
        http_response_code(500);
        echo json_encode(["status" => 500, "message" => "JWT_KEY belum di set"], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $code01 = isset($user["CODE01"]) && (string)$user["CODE01"] !== "" ? (string)$user["CODE01"] : null;

    $payload = [
        "user_id"  => $user["idincrement"],
        "username" => $user["username"],
        "nama"     => $user["nama"],
        "code01"   => $code01,
        "iat"      => time(),
        "exp"      => time() + 86400,
    ];

    $jwt = new JWT();
    $token = $jwt->encode($payload, $key, "HS256");

    http_response_code(200);
    echo json_encode([
        "status"  => 200,
        "message" => "Login berhasil",
        "data"    => [
            "token"    => $token,
            "nama"     => $user["nama"],
            "username" => $user["username"],
            "code01"   => $code01,
        ]
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

function tagihanFilterSql(array $req, ?string $code01, array &$params): string
{
    $sql = "";

    if ($code01 !== null && $code01 !== "") {
        $sql .= " AND c.CODE01 = :code01 ";
        $params[":code01"] = $code01;
    }

    $bta = trim((string) ($req["bta"] ?? $req["tahun_akademik"] ?? ""));
    if ($bta !== "") {
        $sql .= " AND b.BTA = :bta ";
        $params[":bta"] = $bta;
    }

    $paidst = isset($req["paidst"]) && $req["paidst"] !== "" ? (int) $req["paidst"] : null;
    if ($paidst !== null) {
        $sql .= " AND b.PAIDST = :paidst ";
        $params[":paidst"] = $paidst;
    }

    $unit = trim((string) ($req["unit"] ?? ""));
    if ($unit !== "") {
        $sql .= " AND c.CODE02 = :unit ";
        $params[":unit"] = $unit;
    }

    $kelas = trim((string) ($req["kelas"] ?? ""));
    if ($kelas !== "") {
        $sql .= " AND c.DESC02 = :kelas ";
        $params[":kelas"] = $kelas;
    }

    $kelompok = trim((string) ($req["kelompok"] ?? ""));
    if ($kelompok !== "") {
        $sql .= " AND c.DESC03 = :kelompok ";
        $params[":kelompok"] = $kelompok;
    }

    $search = trim((string) ($req["search"] ?? ""));
    $nis = trim((string) ($req["nis"] ?? $req["nocust"] ?? ""));

    if ($nis !== "") {
        $sql .= " AND c.NOCUST = :nis ";
        $params[":nis"] = $nis;
    } elseif ($search !== "") {
        $like = "%" . $search . "%";
        $sql .= " AND (c.NMCUST LIKE :search_nama OR c.NOCUST LIKE :search_nis OR c.NUM2ND LIKE :search_nopend) ";
        $params[":search_nama"] = $like;
        $params[":search_nis"] = $like;
        $params[":search_nopend"] = $like;
    }

    return $sql;
}

function bindTagihanParams(\PDOStatement $stmt, array $params): void
{
    foreach ($params as $k => $v) {
        if (is_int($v)) {
            $stmt->bindValue($k, $v, PDO::PARAM_INT);
        } else {
            $stmt->bindValue($k, $v, PDO::PARAM_STR);
        }
    }
}

function tagihanOrderSql(array $req): string
{
    $dir = strtoupper(trim((string) ($req["sort_dir"] ?? "asc")));
    if ($dir !== "DESC") {
        $dir = "ASC";
    }

    return " ORDER BY b.FUrutan {$dir}, c.NOCUST ASC, b.BILLCD ASC ";
}

function getDataTagihan(array $req, ?string $code01): void
{
    $limit    = (int) ($req["limit"] ?? 500);
    $offset   = (int) ($req["offset"] ?? 0);

    if ($limit <= 0) $limit = 500;
    if ($limit > 5000) $limit = 5000;
    if ($offset < 0) $offset = 0;

    $pdo = dbConnectPdo();

    $sql = "
        SELECT
            b.CUSTID AS custid,
            b.BILLCD AS kode_tagihan,
            b.BILLNM AS nama_tagihan,
            b.BILLAM AS jumlah,
            b.BILLAC AS billac,
            b.PAIDST AS status_bayar,
            b.PAIDDT AS tanggal_bayar,
            b.FTGLTagihan AS tanggal_tagihan,
            b.BTA AS tahun_akademik,
            b.FUrutan AS furutan,
            b.NOREFF AS noreff,
            b.FIDBANK AS fidbank,
            b.FSTSBolehBayar AS boleh_bayar,
            c.CODE01 AS code01,
            c.CODE02 AS unit,
            c.DESC02 AS kelas,
            c.DESC03 AS kelompok,
            c.DESC04 AS tahun_angkatan,
            c.NOCUST AS nis,
            c.NMCUST AS nama,
            c.NUM2ND AS no_pend
        FROM scctbill b
        INNER JOIN scctcust c ON c.CUSTID = b.CUSTID
        WHERE b.FSTSBolehBayar = 1
    ";

    $params = [];
    $sql .= tagihanFilterSql($req, $code01, $params);
    $sql .= tagihanOrderSql($req) . " LIMIT $limit OFFSET $offset ";

    $stmt = $pdo->prepare($sql);
    bindTagihanParams($stmt, $params);
    $stmt->execute();
    $rows = $stmt->fetchAll();

    http_response_code(200);
    echo json_encode(["status" => 200, "data" => $rows], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function getSummaryTagihan(array $req, ?string $code01): void
{
    $pdo = dbConnectPdo();
    $params = [];

    $sql = "
        SELECT
            COUNT(*) AS total_rows,
            COALESCE(SUM(b.BILLAM), 0) AS total_jumlah
        FROM scctbill b
        INNER JOIN scctcust c ON c.CUSTID = b.CUSTID
        WHERE b.FSTSBolehBayar = 1
    ";
    $sql .= tagihanFilterSql($req, $code01, $params);

    $stmt = $pdo->prepare($sql);
    bindTagihanParams($stmt, $params);
    $stmt->execute();
    $row = $stmt->fetch() ?: ["total_rows" => 0, "total_jumlah" => 0];

    http_response_code(200);
    echo json_encode([
        "status" => 200,
        "data"   => [
            "total_rows"   => (int) ($row["total_rows"] ?? 0),
            "total_jumlah" => (float) ($row["total_jumlah"] ?? 0),
        ],
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function getFilterTagihan(array $req, ?string $code01): void
{
    $pdo = dbConnectPdo();

    $sqlBta = "
        SELECT thn_aka AS value
        FROM mst_thn_aka
        WHERE thn_aka IS NOT NULL AND thn_aka != ''
        ORDER BY urut DESC
    ";
    $stmtBta = $pdo->query($sqlBta);
    $bta = array_column($stmtBta->fetchAll(), "value");

    $paramsKelas = [];
    $filterSqlKelas = tagihanFilterSql([], $code01, $paramsKelas);
    $sqlKelas = "
        SELECT DISTINCT c.DESC02 AS value
        FROM scctbill b
        INNER JOIN scctcust c ON c.CUSTID = b.CUSTID
        WHERE b.FSTSBolehBayar = 1
          AND c.DESC02 IS NOT NULL AND c.DESC02 != ''
          $filterSqlKelas
        ORDER BY c.DESC02 ASC
        LIMIT 100
    ";
    $stmtKelas = $pdo->prepare($sqlKelas);
    bindTagihanParams($stmtKelas, $paramsKelas);
    $stmtKelas->execute();
    $kelas = array_column($stmtKelas->fetchAll(), "value");

    http_response_code(200);
    echo json_encode([
        "status" => 200,
        "data"   => [
            "bta"   => $bta,
            "kelas" => $kelas,
        ],
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function getDetailTagihan(array $req, ?string $code01): void
{
    $custid = trim((string) ($req["custid"] ?? ""));
    $billcd = trim((string) ($req["kode_tagihan"] ?? $req["billcd"] ?? ""));

    if ($custid === "" || $billcd === "") {
        http_response_code(422);
        echo json_encode(["status" => 422, "message" => "custid dan kode_tagihan wajib diisi"], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $pdo = dbConnectPdo();
    $custidInt = (int) $custid;

    $sqlDetail = "
        SELECT
            d.KodePost AS kode_akun,
            d.BILLAM AS jumlah,
            b.BTA AS bta,
            COALESCE(ua.NamaAkun, d.KodePost) AS nama_akun
        FROM scctbill_detail d
        INNER JOIN scctbill b ON b.CUSTID = d.CUSTID AND b.BILLCD = d.BILLCD
        LEFT JOIN u_akun ua ON ua.KodeAkun = d.KodePost
        WHERE d.CUSTID = :custid AND d.BILLCD = :billcd
        ORDER BY d.KodePost ASC
    ";

    $stmtD = $pdo->prepare($sqlDetail);
    $stmtD->bindValue(":custid", $custidInt, PDO::PARAM_INT);
    $stmtD->bindValue(":billcd", $billcd, PDO::PARAM_STR);
    $stmtD->execute();
    $detail = $stmtD->fetchAll();

    if (empty($detail)) {
        http_response_code(404);
        echo json_encode(["status" => 404, "message" => "Rincian tagihan tidak ditemukan"], JSON_UNESCAPED_UNICODE);
        exit;
    }

    http_response_code(200);
    echo json_encode([
        "status" => 200,
        "data"   => [
            "header" => null,
            "detail" => array_map(static fn ($r) => [
                "kode_akun" => $r["kode_akun"],
                "nama_akun" => $r["nama_akun"],
                "jumlah"    => $r["jumlah"],
                "bta"       => $r["bta"],
            ], $detail),
        ],
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

if (($_SERVER["REQUEST_METHOD"] ?? "") === "OPTIONS") {
    http_response_code(204);
    exit;
}

try {
    loadEnv(__DIR__ . "/.env");

    $req = getJsonInput();

    $method = trim((string) ($req["method"] ?? ""));

    if ($method === "login") {
        login($req);
        exit;
    }

    $token = null;
    if (isset($req["token"]) && is_string($req["token"]) && $req["token"] !== "") {
        $token = $req["token"];
    } elseif (isset($_SERVER["HTTP_AUTHORIZATION"])) {
        $authHeader = $_SERVER["HTTP_AUTHORIZATION"];
        if (preg_match('/Bearer\s+(.*)$/i', $authHeader, $matches)) {
            $token = $matches[1];
        }
    }

    if (!$token) {
        http_response_code(401);
        echo json_encode(["status" => 401, "message" => "Token wajib diisi"], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $jwt = new JWT();
    $key = (string) ($_ENV["JWT_KEY"] ?? "");
    if ($key === "") {
        http_response_code(500);
        echo json_encode(["status" => 500, "message" => "JWT_KEY belum di set"], JSON_UNESCAPED_UNICODE);
        exit;
    }

    try {
        $decoded = $jwt->decode($token, $key, ["HS256"]);
        if (is_object($decoded)) $decoded = (array) $decoded;
        $req = array_merge($req, (array) $decoded);
    } catch (Throwable $e) {
        http_response_code(401);
        echo json_encode(["status" => 401, "message" => "Token JWT tidak valid"], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $code01 = isset($req["code01"]) && (string)$req["code01"] !== "" ? (string)$req["code01"] : null;

    if ($method === "getDataTagihan") {
        getDataTagihan($req, $code01);
        exit;
    }

    if ($method === "getDetailTagihan") {
        getDetailTagihan($req, $code01);
        exit;
    }

    if ($method === "getSummaryTagihan") {
        getSummaryTagihan($req, $code01);
        exit;
    }

    if ($method === "getFilterTagihan") {
        getFilterTagihan($req, $code01);
        exit;
    }

    http_response_code(422);
    echo json_encode(["status" => 422, "message" => "Metode '$method' tidak valid"], JSON_UNESCAPED_UNICODE);
    exit;
} catch (Throwable $e) {
    writeLog([
        "level"   => "ERROR",
        "event"   => "EXCEPTION",
        "type"    => get_class($e),
        "message" => $e->getMessage(),
        "file"    => $e->getFile(),
        "line"    => $e->getLine()
    ]);

    http_response_code(500);
    echo json_encode(["status" => 500, "message" => "Gagal mengambil data: " . $e->getMessage()], JSON_UNESCAPED_UNICODE);
    exit;
}