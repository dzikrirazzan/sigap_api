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
        // Hanya admin yang bisa lihat semua user
        if (!auth()->user()->isAdmin()) {
            return response()->json(['message' => 'Unauthorized'], 403);
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

        // Send OTP for email verification
        $otpService = new \App\Services\EmailOtpService();
        $otpSent = $otpService->sendEmailVerificationOtp($user->email);

        if (!$otpSent) {
            return response()->json([
                'message' => 'Registration successful but failed to send verification email. Please try to resend OTP.',
                'user' => $user,
                'email_verification_required' => true,
            ], 201);
        }

        $response = [
            'message' => 'Registration successful. Please check your email for the verification code.',
            'user' => $user,
            'email_verification_required' => true,
        ];

        return response($response, 201);
    }

    /**
     * Register relawan (hanya admin)
     */
    public function registerRelawan(Request $request)
    {
        if (!auth()->user()->isAdmin()) {
            return response()->json(['message' => 'Unauthorized'], 403);
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
            'message' => 'Relawan registered successfully',
            'user' => $user
        ], 201);
    }

    /**
     * Register admin (hanya admin)
     */
    public function registerAdmin(Request $request)
    {
        if (!auth()->user()->isAdmin()) {
            return response()->json(['message' => 'Unauthorized'], 403);
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
            'message' => 'Admin registered successfully',
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
            return response()->json(['message' => 'Invalid credentials'], 401);
        }

        // Check if email is verified (only for regular users, not admin/relawan)
        if ($user->role === User::ROLE_USER && !$user->hasVerifiedEmail()) {
            return response()->json([
                'message' => 'Please verify your email address first.',
                'email_verification_required' => true,
                'user_id' => $user->id
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
            return response()->json(['message' => 'Invalid refresh token'], 401);
        }

        if ($refreshToken->isExpired()) {
            $refreshToken->delete();
            return response()->json(['message' => 'Refresh token expired'], 401);
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
        auth()->user()->currentAccessToken()->delete();

        auth()->user()->refreshTokens()->delete();

        return response()->json([
            'message' => 'Logged out successfully'
        ]);
    }

    /**
     * Lihat detail user
     */
    public function show($id)
    {
        $user = User::find($id);

        if (!$user) {
            return response([
                'message' => 'User not found'
            ], 404);
        }

        if (!auth()->user()->isAdmin() && auth()->id() !== $user->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        return response()->json($user);
    }

    /**
     * Update user
     */
    public function update(Request $request, $id)
    {
        $user = User::find($id);

        if (!$user) {
            return response([
                'message' => 'User not found'
            ], 404);
        }

        if (!auth()->user()->isAdmin() && auth()->id() !== $user->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
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

        if (isset($request->role) && auth()->user()->isAdmin()) {
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
            'message' => 'User updated successfully',
            'user' => $user
        ]);
    }

    /**
     * Delete user
     */
    public function destroy($id)
    {
        $user = User::find($id);

        if (!$user) {
            return response([
                'message' => 'User not found'
            ], 404);
        }

        if (!auth()->user()->isAdmin()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $user->delete();

        return response()->json([
            'message' => 'User deleted successfully'
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
        if (!auth()->user()->isAdmin()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $relawan = User::where('role', User::ROLE_RELAWAN)->latest()->get();

        return response()->json($relawan);
    }

    /**
     * Get all regular users (untuk admin)
     */
    public function getAllUsers()
    {
        if (!auth()->user()->isAdmin()) {
            return response()->json(['message' => 'Unauthorized'], 403);
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
            return response()->json(['message' => 'User not found'], 404);
        }

        if ($user->hasVerifiedEmail()) {
            return response()->json(['message' => 'Email already verified'], 200);
        }

        $otpService = new EmailOtpService();
        $otpSent = $otpService->sendEmailVerificationOtp($user->email);

        if (!$otpSent) {
            return response()->json(['message' => 'Failed to send OTP email'], 500);
        }

        return response()->json(['message' => 'OTP sent successfully'], 200);
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
            return response()->json(['message' => 'User not found'], 404);
        }

        if ($user->hasVerifiedEmail()) {
            return response()->json(['message' => 'Email already verified'], 200);
        }

        $otpService = new EmailOtpService();
        $otpSent = $otpService->sendEmailVerificationOtp($user->email);

        if (!$otpSent) {
            return response()->json(['message' => 'Failed to send OTP email'], 500);
        }

        return response()->json(['message' => 'OTP resent successfully'], 200);
    }
}