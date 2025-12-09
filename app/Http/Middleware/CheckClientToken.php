<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Laravel\Passport\Passport;
use Laravel\Passport\Token;
use Symfony\Component\HttpFoundation\Response;

class CheckClientToken
{
    public function handle(Request $request, Closure $next): Response
    {
        $bearerToken = $request->bearerToken();
        
        if (!$bearerToken) {
            return response()->json([
                'message' => 'Unauthenticated.'
            ], 401);
        }

        try {
            // Decode JWT token to get jti (token ID)
            $parts = explode('.', $bearerToken);
            if (count($parts) !== 3) {
                return response()->json([
                    'message' => 'Invalid token format.'
                ], 401);
            }

            $payload = json_decode(base64_decode(str_replace(['-', '_'], ['+', '/'], $parts[1])));
            
            if (!$payload || !isset($payload->jti)) {
                return response()->json([
                    'message' => 'Invalid token payload.'
                ], 401);
            }

            // Check if token exists in database and not revoked
            $token = Token::where('id', $payload->jti)->first();
            
            if (!$token || $token->revoked) {
                return response()->json([
                    'message' => 'Token is invalid or revoked.'
                ], 401);
            }

            // Check if token is expired
            if ($token->expires_at < now()) {
                return response()->json([
                    'message' => 'Token has expired.'
                ], 401);
            }

            return $next($request);
            
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Invalid token: ' . $e->getMessage()
            ], 401);
        }
    }
}
