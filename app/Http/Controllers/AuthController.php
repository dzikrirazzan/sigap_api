<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\RefreshToken;
use App\Services\EmailOtpService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Carbon\Carbon;

class AuthController extends Controller
{
    /**
     * Daftar semua user (hanya admin)
     */
    public function index()
    {
        /** @var \App\Models\User $user */
        $user = auth()->user();

        // Hanya admin yang bisa lihat semua user
        if (!$user->isAdmin()) {
            return response()->json(['message' => 'Tidak memiliki akses'], 403);
        }

        return response()->json(User::latest()->get());
    }

    /**
     * Register untuk user umum (public) - dengan email verification
     */
    public function register(Request $request)
    {
        $fields = $request->validate([
            'name' => 'required|string',
            'email' => 'required|string|email|unique:users,email',
            'password' => 'required|string|min:6',
            'no_telp' => 'required|string|max:15',
            'nim' => 'nullable|string|max:25',
            'jurusan' => 'nullable|string|max:100',
        ]);

        $user = User::create([
            'name' => $fields['name'],
            'email' => $fields['email'],
            'password' => bcrypt($fields['password']),
            'role' => User::ROLE_USER,
            'no_telp' => $fields['no_telp'],
            'nim' => $fields['nim'] ?? null,
            'jurusan' => $fields['jurusan'] ?? null,
        ]);

        // Send email verification OTP
        $otpService = new EmailOtpService();
        $otpSent = $otpService->sendEmailVerificationOtp($user->email);

        if (!$otpSent) {
            return response()->json([
                'message' => 'Registrasi berhasil tetapi gagal mengirim email verifikasi. Silakan kirim ulang OTP.',
                'user' => $user,
                'email_verification_required' => true,
            ], 201);
        }

        $response = [
            'message' => 'Registrasi berhasil. Silakan cek email Anda untuk kode verifikasi OTP.',
            'user' => $user,
            'email_verification_required' => true,
            'otp_expires_in_minutes' => 10,
        ];

        return response($response, 201);
    }

    /**
     * Register relawan (hanya admin)
     */
    public function registerRelawan(Request $request)
    {
        /** @var \App\Models\User $currentUser */
        $currentUser = auth()->user();

        if (!$currentUser->isAdmin()) {
            return response()->json(['message' => 'Tidak memiliki akses'], 403);
        }

        $fields = $request->validate([
            'name' => 'required|string',
            'email' => 'required|string|unique:users,email',
            'password' => 'required|string|min:6',
            'nik' => 'required|string|size:16|unique:users,nik',
            'nim' => 'nullable|string|max:20',
            'jurusan' => 'nullable|string|max:100',
            'no_telp' => 'required|string|max:15',
        ]);

        $user = User::create([
            'name' => $fields['name'],
            'email' => $fields['email'],
            'password' => bcrypt($fields['password']),
            'role' => User::ROLE_RELAWAN,
            'nik' => $fields['nik'],
            'nim' => $fields['nim'] ?? null,
            'jurusan' => $fields['jurusan'] ?? null,
            'no_telp' => $fields['no_telp'],
        ]);

        return response()->json([
            'message' => 'Relawan berhasil didaftarkan',
            'user' => $user
        ], 201);
    }

    /**
     * Register admin (hanya admin)
     */
    public function registerAdmin(Request $request)
    {
        /** @var \App\Models\User $currentUser */
        $currentUser = auth()->user();

        if (!$currentUser->isAdmin()) {
            return response()->json(['message' => 'Tidak memiliki akses'], 403);
        }

        $fields = $request->validate([
            'name' => 'required|string',
            'email' => 'required|string|unique:users,email',
            'password' => 'required|string|min:6',
        ]);

        $user = User::create([
            'name' => $fields['name'],
            'email' => $fields['email'],
            'password' => bcrypt($fields['password']),
            'role' => User::ROLE_ADMIN,
        ]);

        return response()->json([
            'message' => 'Admin berhasil didaftarkan',
            'user' => $user
        ], 201);
    }

    /**
     * Login untuk semua tipe user
     */
    public function login(Request $request)
    {
        $fields = $request->validate([
            'email' => 'required|string|email',
            'password' => 'required|string'
        ]);

        // Check user credentials
        $user = User::where('email', $fields['email'])->first();

        if (!$user || !Hash::check($fields['password'], $user->password)) {
            return response()->json(['message' => 'Email atau kata sandi salah. Coba lagi atau klik Lupa kata sandi untuk mengatur ulang.'], 401);
        }

        // Check email verification for regular users
        if ($user->role === User::ROLE_USER && !$user->hasVerifiedEmail()) {
            return response()->json([
                'message' => 'Silakan verifikasi email Anda terlebih dahulu. Cek inbox Anda untuk kode OTP.',
                'email_verification_required' => true,
                'email' => $user->email
            ], 403);
        }

        // Delete old tokens
        $user->tokens()->delete();

        // Create new token
        $token = $user->createToken('access_token')->plainTextToken;

        // Create refresh token
        $refreshToken = $user->createRefreshToken();

        $response = [
            'user' => $user,
            'access_token' => $token,
            'token_type' => 'Bearer',
            'expires_in' => config('sanctum.expiration') * 60,
            'refresh_token' => $refreshToken->token,
        ];

        return response($response, 200);
    }

    /**
     * Refresh token handler
     */
    public function refreshToken(Request $request)
    {
        $request->validate([
            'refresh_token' => 'required|string',
        ]);

        $refreshToken = RefreshToken::where('token', $request->refresh_token)->first();

        if (!$refreshToken) {
            return response()->json(['message' => 'Token refresh tidak valid'], 401);
        }

        if ($refreshToken->isExpired()) {
            $refreshToken->delete();
            return response()->json(['message' => 'Token refresh sudah kedaluwarsa'], 401);
        }

        $user = $refreshToken->user;

        $user->tokens()->delete();

        $token = $user->createToken('access_token')->plainTextToken;

        $newRefreshToken = $user->createRefreshToken();

        return response()->json([
            'access_token' => $token,
            'token_type' => 'Bearer',
            'expires_in' => config('sanctum.expiration') * 60,
            'refresh_token' => $newRefreshToken->token,
        ]);
    }

    /**
     * Logout user
     */
    public function logout(Request $request)
    {
        /** @var \App\Models\User $user */
        $user = auth()->user();

        // Revoke current access token using tokenable
        /** @var \Laravel\Sanctum\PersonalAccessToken $token */
        $token = $request->user()->currentAccessToken();
        $token->delete();

        // Delete all refresh tokens
        $user->refreshTokens()->delete();

        return response()->json([
            'message' => 'Berhasil keluar dari sistem SIGAP UNDIP'
        ]);
    }

    /**
     * Lihat detail user
     */
    public function show($id)
    {
        /** @var \App\Models\User $currentUser */
        $currentUser = auth()->user();

        $user = User::find($id);

        if (!$user) {
            return response([
                'message' => 'Pengguna tidak ditemukan'
            ], 404);
        }

        if (!$currentUser->isAdmin() && auth()->id() !== $user->id) {
            return response()->json(['message' => 'Tidak memiliki akses'], 403);
        }

        return response()->json($user);
    }

    /**
     * Update user
     */
    public function update(Request $request, $id)
    {
        /** @var \App\Models\User $currentUser */
        $currentUser = auth()->user();

        $user = User::find($id);

        if (!$user) {
            return response([
                'message' => 'Pengguna tidak ditemukan'
            ], 404);
        }

        if (!$currentUser->isAdmin() && auth()->id() !== $user->id) {
            return response()->json(['message' => 'Tidak memiliki akses'], 403);
        }

        if ($user->isRelawan()) {
            $fields = $request->validate([
                'name' => 'sometimes|string',
                'email' => 'sometimes|string|unique:users,email,' . $id,
                'password' => 'sometimes|string|min:6',
                'nik' => 'sometimes|string|size:16|unique:users,nik,' . $id,
                'nim' => 'nullable|string|max:20',
                'jurusan' => 'nullable|string|max:100',
                'no_telp' => 'sometimes|string|max:15',
            ]);
        } else {
            $fields = $request->validate([
                'name' => 'sometimes|string',
                'email' => 'sometimes|string|unique:users,email,' . $id,
                'password' => 'sometimes|string|min:6',
                'nim' => 'nullable|string|max:20',
                'jurusan' => 'nullable|string|max:100',
            ]);
        }

        if (isset($request->role) && $currentUser->isAdmin()) {
            $user->role = $request->role;
        }

        if (isset($fields['name'])) {
            $user->name = $fields['name'];
        }

        if (isset($fields['email'])) {
            $user->email = $fields['email'];
        }

        if (isset($fields['password'])) {
            $user->password = bcrypt($fields['password']);
        }

        if (isset($fields['nim'])) {
            $user->nim = $fields['nim'];
        }

        if (isset($fields['jurusan'])) {
            $user->jurusan = $fields['jurusan'];
        }

        if ($user->isRelawan()) {
            if (isset($fields['nik'])) {
                $user->nik = $fields['nik'];
            }

            if (isset($fields['no_telp'])) {
                $user->no_telp = $fields['no_telp'];
            }
        }

        $user->save();

        return response()->json([
            'message' => 'Data user berhasil diperbarui',
            'user' => $user
        ]);
    }

    /**
     * Delete user
     */
    public function destroy($id)
    {
        /** @var \App\Models\User $currentUser */
        $currentUser = auth()->user();

        $user = User::find($id);

        if (!$user) {
            return response([
                'message' => 'Pengguna tidak ditemukan'
            ], 404);
        }

        if (!$currentUser->isAdmin()) {
            return response()->json(['message' => 'Tidak memiliki akses'], 403);
        }

        $user->delete();

        return response()->json([
            'message' => 'User berhasil dihapus'
        ]);
    }

    /**
     * Get user profile
     */
    public function profile()
    {
        return response()->json(auth()->user());
    }

    /**
     * Get all relawan (untuk admin)
     */
    public function getAllRelawan()
    {
        /** @var \App\Models\User $currentUser */
        $currentUser = auth()->user();

        if (!$currentUser->isAdmin()) {
            return response()->json(['message' => 'Tidak memiliki akses'], 403);
        }

        $relawan = User::where('role', User::ROLE_RELAWAN)->latest()->get();

        return response()->json($relawan);
    }

    /**
     * Get all regular users (untuk admin)
     */
    public function getAllUsers()
    {
        /** @var \App\Models\User $currentUser */
        $currentUser = auth()->user();

        if (!$currentUser->isAdmin()) {
            return response()->json(['message' => 'Tidak memiliki akses'], 403);
        }

        $users = User::where('role', User::ROLE_USER)->latest()->get();

        return response()->json($users);
    }

    /**
     * Send OTP for email verification
     */
    public function sendEmailVerificationOtp(Request $request)
    {
        $request->validate([
            'email' => 'required|email'
        ]);

        $user = User::where('email', $request->email)->first();

        if (!$user) {
            return response()->json(['message' => 'Pengguna tidak ditemukan'], 404);
        }

        if ($user->hasVerifiedEmail()) {
            return response()->json(['message' => 'Email sudah diverifikasi'], 200);
        }

        $otpService = new EmailOtpService();
        $otpSent = $otpService->sendEmailVerificationOtp($user->email);

        if (!$otpSent) {
            return response()->json(['message' => 'Gagal mengirim email OTP'], 500);
        }

        return response()->json(['message' => 'OTP berhasil dikirim'], 200);
    }

    /**
     * Verify OTP and mark email as verified
     */
    public function verifyEmailOtp(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'otp' => 'required|string|size:6'
        ]);

        $otpService = new EmailOtpService();
        $result = $otpService->verifyEmailOtp($request->email, $request->otp);

        if (!$result['success']) {
            return response()->json([
                'message' => $result['message']
            ], 400);
        }

        return response()->json($result, 200);
    }


    /**
     * Resend OTP for email verification
     */
    public function resendEmailVerificationOtp(Request $request)
    {
        $request->validate([
            'email' => 'required|email'
        ]);

        $user = User::where('email', $request->email)->first();

        if (!$user) {
            return response()->json(['message' => 'Pengguna tidak ditemukan'], 404);
        }

        if ($user->hasVerifiedEmail()) {
            return response()->json(['message' => 'Email sudah diverifikasi'], 200);
        }

        $otpService = new EmailOtpService();
        $otpSent = $otpService->sendEmailVerificationOtp($user->email);

        if (!$otpSent) {
            return response()->json(['message' => 'Gagal mengirim email OTP'], 500);
        }

        return response()->json(['message' => 'OTP berhasil dikirim ulang'], 200);
    }

    /**
     * Send Password Reset OTP
     */
    public function sendPasswordResetOtp(Request $request)
    {
        $request->validate([
            'email' => 'required|email|exists:users,email',
        ]);

        $user = User::where('email', $request->email)->first();

        $emailOtpService = new EmailOtpService();
        $result = $emailOtpService->sendPasswordResetOtp($user);

        if ($result['success']) {
            return response()->json([
                'message' => 'OTP reset password telah dikirim ke email Anda',
                'expires_in_minutes' => 10
            ], 200);
        }

        return response()->json([
            'message' => $result['message']
        ], 500);
    }

    /**
     * Verify Password Reset OTP
     */
    public function verifyPasswordResetOtp(Request $request)
    {
        $request->validate([
            'email' => 'required|email|exists:users,email',
            'otp' => 'required|string|size:6',
        ]);

        $user = User::where('email', $request->email)->first();

        $emailOtpService = new EmailOtpService();
        $result = $emailOtpService->verifyPasswordResetOtp($user, $request->otp);

        if ($result['success']) {
            return response()->json([
                'message' => 'OTP berhasil diverifikasi. Anda sekarang dapat mereset password Anda.',
                'email' => $user->email
            ], 200);
        }

        return response()->json([
            'message' => $result['message']
        ], 400);
    }

    /**
     * Reset Password with verified OTP
     */
    public function resetPassword(Request $request)
    {
        $request->validate([
            'email' => 'required|email|exists:users,email',
            'otp' => 'required|string|size:6',
            'password' => 'required|string|min:6|confirmed',
        ]);

        $user = User::where('email', $request->email)->first();

        // Verify OTP one more time
        $emailOtpService = new EmailOtpService();
        $result = $emailOtpService->verifyPasswordResetOtp($user, $request->otp);

        if (!$result['success']) {
            return response()->json([
                'message' => $result['message']
            ], 400);
        }

        // Update password
        $user->password = bcrypt($request->password);
        $user->save();

        // Mark OTP as used
        $emailOtpService->markOtpAsUsed($user->email, $request->otp, 'password_reset');

        // Revoke all tokens for security
        $user->tokens()->delete();

        return response()->json([
            'message' => 'Password berhasil direset. Silakan login dengan password baru Anda.'
        ], 200);
    }

    /**
     * Resend Password Reset OTP
     */
    public function resendPasswordResetOtp(Request $request)
    {
        $request->validate([
            'email' => 'required|email|exists:users,email',
        ]);

        $user = User::where('email', $request->email)->first();

        $emailOtpService = new EmailOtpService();
        $result = $emailOtpService->sendPasswordResetOtp($user);

        if ($result['success']) {
            return response()->json([
                'message' => 'OTP reset password baru telah dikirim ke email Anda',
                'expires_in_minutes' => 10
            ], 200);
        }

        return response()->json([
            'message' => $result['message']
        ], 500);
    }
}
