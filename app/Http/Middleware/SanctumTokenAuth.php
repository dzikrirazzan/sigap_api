<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Laravel\Sanctum\PersonalAccessToken;

/**
 * Custom Sanctum Token Authentication Middleware
 * 
 * This middleware validates Bearer tokens directly without relying on 
 * Auth::guard('sanctum') which has issues with SanctumServiceProvider
 * loading order on some deployments (like Heroku).
 */
class SanctumTokenAuth
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        $token = $request->bearerToken();
        
        if (!$token) {
            return response()->json([
                'message' => 'Unauthenticated. Silakan login terlebih dahulu.'
            ], 401);
        }
        
        // Find the token in database
        $accessToken = PersonalAccessToken::findToken($token);
        
        if (!$accessToken) {
            return response()->json([
                'message' => 'Token tidak valid atau sudah kedaluwarsa.'
            ], 401);
        }
        
        // Check if token is expired
        $expiration = config('sanctum.expiration');
        if ($expiration && $accessToken->created_at->lte(now()->subMinutes($expiration))) {
            return response()->json([
                'message' => 'Token sudah kedaluwarsa. Silakan login kembali.'
            ], 401);
        }
        
        // Get the user associated with this token
        $user = $accessToken->tokenable;
        
        if (!$user) {
            return response()->json([
                'message' => 'User tidak ditemukan.'
            ], 401);
        }
        
        // Update last used at
        $accessToken->forceFill(['last_used_at' => now()])->save();
        
        // Set the authenticated user on the request
        $request->setUserResolver(function () use ($user) {
            return $user;
        });
        
        // Also set auth user for compatibility
        auth()->setUser($user);
        
        return $next($request);
    }
}
