<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AuthController extends Controller
{
    private const API_BASE_URL_PERIZINAN = 'http://vps1.smartpayment.co.id:8888/Data/Malang_Arrohmah_Putri_Perizinan/WebAPI.php';
    private const API_BASE_URL_PRESENSI_SHOLAT = 'http://vps1.smartpayment.co.id:8888/Data/Malang_Arrohmah_Putri_PresensiSholat/WebAPI.php';
    private const JWT_SECRET = 'a7c2a8a9b3c4a5a6a7a8a9b0c1a2a3';

    public function showLogin()
    {
        // Jika sudah login, redirect ke dashboard
        if (session()->has('user') && session('user.username')) {
            return redirect()->route($this->dashboardRouteName(session('user.app')));
        }
        return view('login');
    }

    public function logout(Request $request)
    {
        $request->session()->flush();
        return redirect()->route('login.form');
    }

    public function login(Request $request)
    {
        $validated = $request->validate([
            'app' => ['required', 'string'],
            'username' => ['required', 'string'],
            'password' => ['required', 'string'],
        ]);

        $apiBaseUrl = $this->apiBaseUrlFor($validated['app']);

        Log::info('Login attempt received', [
            'app' => $validated['app'],
            'username' => $validated['username'],
        ]);

        $payload = [
            'METHOD'   => 'LoginRequest',
            'USERNAME' => $validated['username'],
            'PASSWORD' => $validated['password'],
        ];

        $token = $this->generateJwt($payload);

        try {
            Log::info('Sending request to external API', [
                'url' => $apiBaseUrl,
                'token_preview' => substr($token, 0, 40) . '...',
            ]);

            $response = Http::timeout(15)
                ->get($apiBaseUrl . '?token=' . urlencode($token));

            Log::info('External API response raw', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);
        } catch (\Throwable $e) {
            Log::error('Error calling external API', [
                'message' => $e->getMessage(),
            ]);
            return back()
                ->withInput($request->except('password'))
                ->with('login_error', 'Tidak dapat terhubung ke server. Silakan coba lagi.');
        }

        if (! $response->ok()) {
            return back()
                ->withInput($request->except('password'))
                ->with('login_error', 'Terjadi kesalahan pada server. Silakan coba lagi.');
        }

        $data = $response->json();

        if (isset($data['KodeRespon']) && (int) $data['KodeRespon'] === 1) {
            // Simpan username asli yang diinput saat login (untuk keperluan API calls seperti RequestNewPassword)
            // API RequestNewPassword mengharapkan username asli, bukan yang dikembalikan API login
            $username = $validated['username'];

            // Simpan informasi dasar user di session
            $request->session()->put('user', [
                'username' => $username,
                'app'      => $validated['app'],
            ]);

            // Ambil jenis izin untuk dropdown setelah login berhasil
            // SingleEntry = Perizinan Kedatangan/Kepulangan (Masuk/Keluar)
            $izinTypesSingle = $this->fetchIzinTypes('SingleEntryListRequest');
            $request->session()->put('izin_types_single', $izinTypesSingle);

            // MultipleEntry = Perizinan Umum
            $izinTypesMultiple = $this->fetchIzinTypes('MultipleEntryListRequest');
            $request->session()->put('izin_types_multiple', $izinTypesMultiple);

            // SpecialEntry = Perizinan Khusus
            $izinTypesSpecial = $this->fetchIzinTypes('SpecialEntryListRequest');
            $request->session()->put('izin_types_special', $izinTypesSpecial);

            return redirect()
                ->route($this->dashboardRouteName($validated['app']))
                ->with('login_success', 'Login berhasil.');
        }

        $message = $data['PesanRespon'] ?? 'Login gagal. Akses Ditolak.';

        return back()
            ->withInput($request->except('password'))
            ->with('login_error', $message);
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

    private function fetchIzinTypes(string $method): array
    {
        $now = now()->timestamp;
        $payload = [
            'METHOD' => $method,
            'iat'    => $now,
            'exp'    => $now + 300,
        ];

        $token = $this->generateJwt($payload);

        try {
            $response = Http::timeout(10)
                ->get(self::API_BASE_URL_PERIZINAN . '?token=' . urlencode($token));

            if ($response->ok()) {
                $data = $response->json();

                // Response format: [{ "KodeRespon": 1, "ListRespone": [...] }]
                if (is_array($data) && isset($data[0]['ListRespone'])) {
                    return $data[0]['ListRespone'];
                }
            }
        } catch (\Throwable $e) {
            Log::error('Error fetching izin types', [
                'method' => $method,
                'message' => $e->getMessage(),
            ]);
        }

        return [];
    }

    private function apiBaseUrlFor(?string $app): string
    {
        return $app === 'presensi-sholat'
            ? self::API_BASE_URL_PRESENSI_SHOLAT
            : self::API_BASE_URL_PERIZINAN;
    }

    private function dashboardRouteName(?string $app): string
    {
        return $app === 'presensi-sholat'
            ? 'dashboard.presensi-sholat'
            : 'dashboard';
    }

    public function showGantiPassword()
    {
        if (session('user.app') === 'presensi-sholat') {
            return redirect()->route('presensi.account.ganti-password');
        }
        return view('ganti_password');
    }

    public function showGantiPasswordPresensi()
    {
        if (session('user.app') !== 'presensi-sholat') {
            return redirect()->route('account.ganti-password');
        }
        return view('ganti_password_presensi');
    }

    public function gantiPassword(Request $request)
    {
        $validated = $request->validate([
            'old_password' => ['required', 'string'],
            'new_password' => ['required', 'string', 'min:3'],
            'confirm_password' => ['required', 'string', 'same:new_password'],
        ]);

        $username = session('user.username');
        if (!$username) {
            return back()->with('password_error', 'Session tidak valid. Silakan login kembali.');
        }

        $apiBaseUrl = $this->apiBaseUrlFor(session('user.app'));

        $oldPassword = $validated['old_password'];

        $payload = [
            'METHOD'       => 'RequestNewPassword',
            'USERNAME'     => $username,
            'PASSWORD'     => $oldPassword,
            'NEWPASSWORD'  => $validated['new_password'],
            'NEWPASSWORD2' => $validated['confirm_password'],
        ];

        // Log payload untuk debugging
        Log::info('Ganti password payload', [
            'payload' => $payload,
            'payload_json' => json_encode($payload, JSON_UNESCAPED_SLASHES),
        ]);

        $token = $this->generateJwt($payload);
        
        // Decode token untuk verifikasi
        $parts = explode('.', $token);
        if (count($parts) === 3) {
            $decodedPayload = json_decode(base64_decode(strtr($parts[1], '-_', '+/')), true);
            Log::info('Ganti password decoded token payload', ['decoded' => $decodedPayload]);
        }

        try {
            Log::info('Ganti password request', [
                'username' => $username,
                'payload' => $payload,
                'token_preview' => substr($token, 0, 50) . '...',
                'token_length' => strlen($token),
            ]);

            $url = $apiBaseUrl . '?token=' . urlencode($token);
            Log::info('Ganti password API URL', [
                'url_preview' => substr($url, 0, 100) . '...',
                'url_length' => strlen($url),
            ]);

            // Coba GET dulu (sesuai dengan API lainnya)
            $response = Http::timeout(15)
                ->get($url);
            
            // Jika GET gagal dengan 500, coba beberapa variasi POST
            if ($response->status() === 500 && empty($response->body())) {
                Log::info('Trying POST method for ganti password');
                
                // Variasi 1: POST dengan token di query string (seperti GET)
                $response = Http::timeout(15)
                    ->post($url);
                
                // Jika masih 500, coba variasi 2: POST dengan token di body sebagai form
                if ($response->status() === 500 && empty($response->body())) {
                    Log::info('Trying POST with token in form body');
                    $response = Http::timeout(15)
                        ->asForm()
                        ->post($apiBaseUrl, ['token' => $token]);
                }
                
                // Jika masih 500, coba variasi 3: POST dengan token di body sebagai JSON
                if ($response->status() === 500 && empty($response->body())) {
                    Log::info('Trying POST with token in JSON body');
                    $response = Http::timeout(15)
                        ->asJson()
                        ->post($apiBaseUrl, ['token' => $token]);
                }
            }

            Log::info('Ganti password API response', [
                'status' => $response->status(),
                'headers' => $response->headers(),
                'body' => $response->body(),
                'body_length' => strlen($response->body()),
                'successful' => $response->ok(),
            ]);

            if (!$response->ok()) {
                $status = $response->status();
                $body = $response->body();
                
                // Coba parse JSON response jika ada
                $errorMsg = 'Terjadi kesalahan pada server (HTTP ' . $status . ').';
                
                if ($body) {
                    $jsonData = json_decode($body, true);
                    if (json_last_error() === JSON_ERROR_NONE && isset($jsonData['PesanRespon'])) {
                        $errorMsg = $jsonData['PesanRespon'];
                    } else {
                        $errorMsg .= ' ' . substr($body, 0, 150);
                    }
                } else {
                    // HTTP 500 dengan body kosong biasanya berarti server error atau endpoint tidak tersedia
                    $errorMsg = 'Server API mengembalikan error tanpa pesan. Kemungkinan: password lama salah, endpoint tidak tersedia, atau server bermasalah. Silakan coba lagi atau hubungi administrator.';
                }
                
                Log::error('Ganti password failed', [
                    'status' => $status,
                    'body' => $body,
                    'body_length' => strlen($body),
                    'username' => $username,
                ]);
                
                return back()
                    ->withInput($request->except(['old_password', 'new_password', 'confirm_password']))
                    ->with('password_error', $errorMsg);
            }

            $data = $response->json();
            
            Log::info('Ganti password response data', ['data' => $data]);

            if (isset($data['KodeRespon']) && (int) $data['KodeRespon'] === 1) {
                return back()->with('password_success', 'Password berhasil diubah.');
            }

            $message = $data['PesanRespon'] ?? 'Gagal mengubah password.';
            return back()
                ->withInput($request->except(['old_password', 'new_password', 'confirm_password']))
                ->with('password_error', $message);

        } catch (\Throwable $e) {
            Log::error('Error changing password', [
                'message' => $e->getMessage(),
            ]);
            return back()
                ->withInput($request->except(['old_password', 'new_password', 'confirm_password']))
                ->with('password_error', 'Tidak dapat terhubung ke server. Silakan coba lagi.');
        }
    }
}

