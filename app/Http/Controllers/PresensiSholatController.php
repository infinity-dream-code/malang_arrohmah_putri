<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class PresensiSholatController extends Controller
{
    private const API_BASE_URL_PRESENSI_SHOLAT = 'http://vps1.smartpayment.co.id:8888/Data/Malang_Arrohmah_Putri_PresensiSholat/WebAPI.php';
    private const JWT_SECRET = 'a7c2a8a9b3c4a5a6a7a8a9b0c1a2a3';

    public function showQr()
    {
        if (session('user.app') !== 'presensi-sholat') {
            return redirect()->route('dashboard');
        }

        return view('presensi_sholat_qr');
    }

    public function showHaidQr()
    {
        if (session('user.app') !== 'presensi-sholat') {
            return redirect()->route('dashboard');
        }

        return view('presensi_haid_qr');
    }

    public function postSholat(Request $request)
    {
        if (session('user.app') !== 'presensi-sholat') {
            return response()->json([
                'ok' => false,
                'message' => 'Akses ditolak.',
            ], 403);
        }

        $validated = $request->validate([
            'nokartu' => ['required', 'string', 'min:1', 'max:255'],
        ]);

        $username = session('user.username');
        if (!$username) {
            return response()->json([
                'ok' => false,
                'message' => 'Session tidak valid. Silakan login kembali.',
            ], 401);
        }

        $payload = [
            'METHOD'  => 'POSTSholat',
            'NOKARTU' => $validated['nokartu'],
            'USERNAME' => $username,
        ];

        $token = $this->generateJwt($payload);

        try {
            Log::info('POSTSholat request', [
                'username' => $username,
                'nokartu' => $validated['nokartu'],
                'payload' => $payload,
                'token_preview' => substr($token, 0, 40) . '...',
            ]);

            $response = Http::timeout(15)
                ->get(self::API_BASE_URL_PRESENSI_SHOLAT . '?token=' . urlencode($token));
        } catch (\Throwable $e) {
            Log::error('POSTSholat error calling external API', [
                'message' => $e->getMessage(),
            ]);

            return response()->json([
                'ok' => false,
                'message' => 'Tidak dapat terhubung ke server. Silakan coba lagi.',
            ], 502);
        }

        if (! $response->ok()) {
            Log::warning('POSTSholat non-200 response', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);
            return response()->json([
                'ok' => false,
                'message' => 'Terjadi kesalahan pada server. Silakan coba lagi.',
            ], 502);
        }

        $data = $response->json();

        Log::info('POSTSholat API response parsed', [
            'status' => $response->status(),
            'data' => $data,
        ]);

        // Format respon Presensi Sholat berbeda dengan perizinan.
        // Contoh: [{"STATUS":"NOTOK","RESULT":"TELAH_MELAKUKAN_PRESENSI","RES":"PRESENSI TELAH DILAKUKAN"}]
        if (is_array($data) && isset($data[0])) {
            $item = $data[0];
            $statusFlag = $item['STATUS'] ?? null;
            $resultCode = $item['RESULT'] ?? null;
            $resMessage = $item['RES'] ?? null;

            if ($statusFlag === 'OK') {
                return response()->json([
                    'ok' => true,
                    'message' => $resMessage ?: 'Presensi sholat berhasil.',
                    'data' => $data,
                ]);
            }

            // Khusus jika sudah pernah presensi, beri pesan yang jelas
            if ($statusFlag === 'NOTOK' && $resultCode === 'TELAH_MELAKUKAN_PRESENSI') {
                return response()->json([
                    'ok' => false,
                    'message' => $resMessage ?: 'Presensi sudah pernah dilakukan untuk kartu ini.',
                    'data' => $data,
                ], 422);
            }

            return response()->json([
                'ok' => false,
                'message' => $resMessage ?: 'Presensi sholat gagal.',
                'data' => $data,
            ], 422);
        }

        // Fallback untuk format lain
        if (isset($data['KodeRespon']) && (int) $data['KodeRespon'] === 1) {
            return response()->json([
                'ok' => true,
                'message' => $data['PesanRespon'] ?? 'Presensi sholat berhasil.',
                'data' => $data,
            ]);
        }

        return response()->json([
            'ok' => false,
            'message' => $data['PesanRespon'] ?? 'Presensi sholat gagal.',
            'data' => $data,
        ], 422);
    }

    public function postHaid(Request $request)
    {
        if (session('user.app') !== 'presensi-sholat') {
            return response()->json([
                'ok' => false,
                'message' => 'Akses ditolak.',
            ], 403);
        }

        $validated = $request->validate([
            'nokartu' => ['required', 'string', 'min:1', 'max:255'],
        ]);

        $username = session('user.username');
        if (!$username) {
            return response()->json([
                'ok' => false,
                'message' => 'Session tidak valid. Silakan login kembali.',
            ], 401);
        }

        $payload = [
            'METHOD'  => 'POSTHaid',
            'NOKARTU' => $validated['nokartu'],
            'USERNAME' => $username,
        ];

        $token = $this->generateJwt($payload);

        try {
            Log::info('POSTHaid request', [
                'username' => $username,
                'nokartu' => $validated['nokartu'],
                'payload' => $payload,
                'token_preview' => substr($token, 0, 40) . '...',
            ]);

            $response = Http::timeout(15)
                ->get(self::API_BASE_URL_PRESENSI_SHOLAT . '?token=' . urlencode($token));
        } catch (\Throwable $e) {
            Log::error('POSTHaid error calling external API', [
                'message' => $e->getMessage(),
            ]);

            return response()->json([
                'ok' => false,
                'message' => 'Tidak dapat terhubung ke server. Silakan coba lagi.',
            ], 502);
        }

        if (! $response->ok()) {
            Log::warning('POSTHaid non-200 response', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);
            return response()->json([
                'ok' => false,
                'message' => 'Terjadi kesalahan pada server. Silakan coba lagi.',
            ], 502);
        }

        $data = $response->json();

        Log::info('POSTHaid API response parsed', [
            'status' => $response->status(),
            'data' => $data,
        ]);

        if (is_array($data) && isset($data[0])) {
            $item = $data[0];
            $statusFlag = $item['STATUS'] ?? null;
            $resultCode = $item['RESULT'] ?? null;
            $resMessage = $item['RES'] ?? null;

            if ($statusFlag === 'OK') {
                return response()->json([
                    'ok' => true,
                    'message' => $resMessage ?: 'Presensi haid berhasil.',
                    'data' => $data,
                ]);
            }

            if ($statusFlag === 'NOTOK' && $resultCode === 'TELAH_MELAKUKAN_PRESENSI') {
                return response()->json([
                    'ok' => false,
                    'message' => $resMessage ?: 'Presensi haid sudah pernah dilakukan untuk kartu ini.',
                    'data' => $data,
                ], 422);
            }

            return response()->json([
                'ok' => false,
                'message' => $resMessage ?: 'Presensi haid gagal.',
                'data' => $data,
            ], 422);
        }

        if (isset($data['KodeRespon']) && (int) $data['KodeRespon'] === 1) {
            return response()->json([
                'ok' => true,
                'message' => $data['PesanRespon'] ?? 'Presensi haid berhasil.',
                'data' => $data,
            ]);
        }

        return response()->json([
            'ok' => false,
            'message' => $data['PesanRespon'] ?? 'Presensi haid gagal.',
            'data' => $data,
        ], 422);
    }

    public function showLogMarifah(Request $request)
    {
        if (session('user.app') !== 'presensi-sholat') {
            return redirect()->route('dashboard');
        }

        $username = session('user.username');
        if (! $username) {
            return redirect()->route('login.form');
        }

        $tanggal = $request->query('tanggal');
        $today   = now()->format('Y-m-d');

        if (! $tanggal) {
            $tanggal = $today;
        }

        // Jika tanggal = hari ini pakai LogMarifahTodayRequest, selain itu LogMarifahRequest
        if ($tanggal === $today) {
            $payload = [
                'METHOD'   => 'LogMarifahTodayRequest',
                'USERNAME' => $username,
            ];
        } else {
            $payload = [
                'METHOD'   => 'LogMarifahRequest',
                'USERNAME' => $username,
                'HARIOUT'  => $tanggal,
            ];
        }

        $token = $this->generateJwt($payload);

        $entries = [];
        $error   = null;

        try {
            Log::info('LogMarifah request', [
                'username' => $username,
                'tanggal'  => $tanggal,
                'payload'  => $payload,
            ]);

            $response = Http::timeout(15)
                ->get(self::API_BASE_URL_PRESENSI_SHOLAT . '?token=' . urlencode($token));

            if ($response->ok()) {
                $data = $response->json();
                Log::info('LogMarifah API response', [
                    'status' => $response->status(),
                    'data' => $data,
                ]);

                if (is_array($data)) {
                    // API mengembalikan { "datas": [ ... ] }
                    if (isset($data['datas']) && is_array($data['datas'])) {
                        $entries = $data['datas'];
                    } else {
                        $entries = $data;
                    }
                }
            } else {
                $error = 'Terjadi kesalahan pada server (HTTP ' . $response->status() . ').';
            }
        } catch (\Throwable $e) {
            Log::error('LogMarifah error', [
                'message' => $e->getMessage(),
            ]);
            $error = 'Tidak dapat terhubung ke server. Silakan coba lagi.';
        }

        return view('log_marifah', [
            'tanggal' => $tanggal,
            'today'   => $today,
            'entries' => $entries,
            'error'   => $error,
        ]);
    }

    public function showLogPresensi(Request $request)
    {
        if (session('user.app') !== 'presensi-sholat') {
            return redirect()->route('dashboard');
        }

        $username = session('user.username');
        if (! $username) {
            return redirect()->route('login.form');
        }

        $tanggal = $request->query('tanggal');
        $today   = now()->format('Y-m-d');

        if (! $tanggal) {
            $tanggal = $today;
        }

        if ($tanggal === $today) {
            $payload = [
                'METHOD'   => 'LogPresensiTodayRequest',
                'USERNAME' => $username,
            ];
        } else {
            $payload = [
                'METHOD'   => 'LogPresensiRequest',
                'USERNAME' => $username,
                'HARIOUT'  => $tanggal,
            ];
        }

        $token = $this->generateJwt($payload);

        $entries = [];
        $error   = null;

        try {
            Log::info('LogPresensi request', [
                'username' => $username,
                'tanggal'  => $tanggal,
                'payload'  => $payload,
            ]);

            $response = Http::timeout(15)
                ->get(self::API_BASE_URL_PRESENSI_SHOLAT . '?token=' . urlencode($token));

            if ($response->ok()) {
                $data = $response->json();

                if (is_array($data)) {
                    if (isset($data['datas']) && is_array($data['datas'])) {
                        $entries = $data['datas'];
                    } else {
                        $entries = $data;
                    }
                }
            } else {
                $error = 'Terjadi kesalahan pada server (HTTP ' . $response->status() . ').';
            }
        } catch (\Throwable $e) {
            Log::error('LogPresensi error', ['message' => $e->getMessage()]);
            $error = 'Tidak dapat terhubung ke server. Silakan coba lagi.';
        }

        return view('log_presensi', [
            'tanggal' => $tanggal,
            'today'   => $today,
            'entries' => $entries,
            'error'   => $error,
        ]);
    }

    public function showKelolaPresensi(Request $request)
    {
        if (session('user.app') !== 'presensi-sholat') {
            return redirect()->route('dashboard');
        }

        $username = session('user.username');
        if (! $username) {
            return redirect()->route('login.form');
        }

        $tanggal = $request->query('tanggal');
        $today   = now()->format('Y-m-d');

        if (! $tanggal) {
            $tanggal = $today;
        }

        return view('kelola_presensi', [
            'tanggal' => $tanggal,
            'today'   => $today,
            'entries' => [],
            'error'   => null,
        ]);
    }

    public function kelolaPresensiData(Request $request)
    {
        if (session('user.app') !== 'presensi-sholat') {
            return response('', 403);
        }

        $username = session('user.username');
        if (! $username) {
            return response('', 401);
        }

        $tanggal = $request->query('tanggal', now()->format('Y-m-d'));
        $today   = now()->format('Y-m-d');

        $cacheKey = 'kelola_presensi_' . $username . '_' . $tanggal;
        $entries  = \Illuminate\Support\Facades\Cache::remember($cacheKey, 60, function () use ($username, $tanggal, $today) {
            if ($tanggal === $today) {
                $payload = [
                    'METHOD'   => 'DetailPresensiRequestToday',
                    'USERNAME' => $username,
                ];
            } else {
                $payload = [
                    'METHOD'   => 'DetailPresensiRequest',
                    'USERNAME' => $username,
                    'HARIOUT'  => $tanggal,
                ];
            }

            $token = $this->generateJwt($payload);
            $list  = [];

            try {
                $response = Http::timeout(20)
                    ->get(self::API_BASE_URL_PRESENSI_SHOLAT . '?token=' . urlencode($token));

                if ($response->ok()) {
                    $data = $response->json();
                    if (is_array($data)) {
                        $list = $data['datas'] ?? $data;
                        if (! is_array($list)) {
                            $list = [];
                        }
                    }
                }
            } catch (\Throwable $e) {
                Log::error('KelolaPresensi data error', ['message' => $e->getMessage()]);
            }

            return $list;
        });

        $html = view('kelola_presensi_list', [
            'entries' => $entries,
            'tanggal' => $tanggal,
        ])->render();

        return response($html, 200, ['Content-Type' => 'text/html']);
    }

    public function showRekapSholat(Request $request)
    {
        if (session('user.app') !== 'presensi-sholat') {
            return redirect()->route('dashboard');
        }

        $username = session('user.username');
        if (! $username) {
            return redirect()->route('login.form');
        }

        $bulan = $request->query('bulan', '');

        return view('rekap_sholat', [
            'bulan' => $bulan,
        ]);
    }

    public function rekapSholatData(Request $request)
    {
        if (session('user.app') !== 'presensi-sholat') {
            return response('', 403);
        }

        $username = session('user.username');
        if (! $username) {
            return response('', 401);
        }

        $bulan = $request->query('bulan', now()->format('Y-m'));

        $cacheKey = 'rekap_sholat_' . $username . '_' . $bulan;
        $entries  = \Illuminate\Support\Facades\Cache::remember($cacheKey, 120, function () use ($username, $bulan) {
            $payload = [
                'METHOD'  => 'RekapRequest',
                'USERNAME' => $username,
                'BULAN'   => $bulan,
            ];

            $token = $this->generateJwt($payload);
            $list  = [];

            try {
                $response = Http::timeout(25)
                    ->get(self::API_BASE_URL_PRESENSI_SHOLAT . '?token=' . urlencode($token));

                if ($response->ok()) {
                    $data = $response->json();
                    if (is_array($data)) {
                        $list = $data['datas'] ?? $data;
                        if (! is_array($list)) {
                            $list = [];
                        }
                    }
                }
            } catch (\Throwable $e) {
                Log::error('RekapSholat data error', ['message' => $e->getMessage()]);
            }

            return $list;
        });

        $html = view('rekap_sholat_list', [
            'entries' => $entries,
            'bulan'   => $bulan,
        ])->render();

        return response($html, 200, ['Content-Type' => 'text/html']);
    }

    public function updatePresensi(Request $request)
    {
        if (session('user.app') !== 'presensi-sholat') {
            return back()->with('error', 'Akses ditolak.');
        }

        $username = session('user.username');
        if (! $username) {
            return back()->with('error', 'Session tidak valid. Silakan login kembali.');
        }

        $validated = $request->validate([
            'id'      => ['required', 'string'],
            'session' => ['required', 'string', 'in:1,2,3,4,5'],
            'status'  => ['required', 'string', 'in:Alpa,Izin,Sholat,Haid,Sakit'],
        ]);

        $statusToCode = [
            'Alpa'   => 'A',
            'Izin'   => 'I',
            'Sholat' => 'SH',
            'Haid'   => 'H',
            'Sakit'  => 'SK',
        ];
        $izinCode = $statusToCode[$validated['status']] ?? $validated['status'];

        $payload = [
            'METHOD'   => 'UpdatePresensiRequest',
            'ID'       => $validated['id'],
            'SESSION'  => $validated['session'],
            'USERNAME' => $username,
            'IZIN'     => $izinCode,
        ];

        $token = $this->generateJwt($payload);

        try {
            $response = Http::timeout(15)
                ->get(self::API_BASE_URL_PRESENSI_SHOLAT . '?token=' . urlencode($token));

            $data = $response->json();

            if ($response->ok() && is_array($data) && isset($data[0])) {
                $item = $data[0];
                if (($item['STATUS'] ?? '') === 'OK') {
                    $tanggal = $request->input('tanggal', now()->format('Y-m-d'));
                    $username = session('user.username');
                    if ($username) {
                        \Illuminate\Support\Facades\Cache::forget('kelola_presensi_' . $username . '_' . $tanggal);
                    }
                    return redirect()->route('presensi.kelola', ['tanggal' => $tanggal])
                        ->with('success', 'Presensi berhasil diupdate.');
                }
                $msg = $item['RES'] ?? $item['PesanRespon'] ?? null;
                if ($msg) {
                    return back()->with('error', $msg);
                }
            }

            $tanggal = $request->input('tanggal', now()->format('Y-m-d'));
            return redirect()->route('presensi.kelola', ['tanggal' => $tanggal]);
        } catch (\Throwable $e) {
            Log::error('UpdatePresensi error', ['message' => $e->getMessage()]);
            return back()->with('error', 'Tidak dapat terhubung ke server.');
        }
    }

    private function generateJwt(array $payload): string
    {
        $header = [
            'alg' => 'HS256',
            'typ' => 'JWT',
        ];

        $headerEncoded = $this->base64UrlEncode(json_encode($header, JSON_UNESCAPED_SLASHES));
        $payloadEncoded = $this->base64UrlEncode(json_encode($payload, JSON_UNESCAPED_SLASHES));

        $signingInput = $headerEncoded . '.' . $payloadEncoded;
        $signature = hash_hmac('sha256', $signingInput, self::JWT_SECRET, true);
        $signatureEncoded = $this->base64UrlEncode($signature);

        return $signingInput . '.' . $signatureEncoded;
    }

    private function base64UrlEncode(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }
}

