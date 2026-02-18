<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;

class JwtBlacklistMiddleware
{
    /**
     * Cache key prefix for blacklisted tokens
     */
    private const BLACKLIST_PREFIX = 'jwt_blacklist:';

    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $token = $request->bearerToken();

        if ($token && $this->isBlacklisted($token)) {
            return response()->json([
                'success' => false,
                'message' => 'Token has been revoked. Please login again.',
            ], 401);
        }

        return $next($request);
    }

    /**
     * Check if a token is blacklisted
     */
    public function isBlacklisted(string $token): bool
    {
        $tokenHash = $this->hashToken($token);
        return Cache::has(self::BLACKLIST_PREFIX . $tokenHash);
    }

    /**
     * Add a token to the blacklist
     *
     * @param string $token The JWT token to blacklist
     * @param int $ttl Time to live in seconds (should match token expiry)
     */
    public static function blacklist(string $token, int $ttl = 86400): void
    {
        $tokenHash = self::hashTokenStatic($token);
        Cache::put(self::BLACKLIST_PREFIX . $tokenHash, true, $ttl);
    }

    /**
     * Remove a token from the blacklist
     */
    public static function removeFromBlacklist(string $token): void
    {
        $tokenHash = self::hashTokenStatic($token);
        Cache::forget(self::BLACKLIST_PREFIX . $tokenHash);
    }

    /**
     * Hash a token for storage (we don't store the actual token)
     */
    private function hashToken(string $token): string
    {
        return hash('sha256', $token);
    }

    /**
     * Static version of hashToken for use in static methods
     */
    private static function hashTokenStatic(string $token): string
    {
        return hash('sha256', $token);
    }

    /**
     * Clear all blacklisted tokens (use with caution)
     */
    public static function clearBlacklist(): void
    {
        // This would require Redis SCAN or similar for cache stores
        // For now, individual tokens should be cleared when they expire
    }

    /**
     * Blacklist all tokens for a specific user by storing user ID with timestamp
     * Tokens issued before this timestamp will be considered invalid
     */
    public static function blacklistAllForUser(int $userId, int $ttl = 86400): void
    {
        $key = 'jwt_user_invalidation:' . $userId;
        Cache::put($key, now()->timestamp, $ttl);
    }

    /**
     * Check if a user's tokens have been globally invalidated
     */
    public static function isUserInvalidated(int $userId, int $tokenIssuedAt): bool
    {
        $key = 'jwt_user_invalidation:' . $userId;
        $invalidatedAt = Cache::get($key);

        if (!$invalidatedAt) {
            return false;
        }

        return $tokenIssuedAt < $invalidatedAt;
    }
}
