<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Tymon\JWTAuth\Facades\JWTAuth;
use Tymon\JWTAuth\Exceptions\JWTException;
use Tymon\JWTAuth\Exceptions\TokenExpiredException;
use Tymon\JWTAuth\Exceptions\TokenInvalidException;

class JwtSuper
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        try {
            $authUser = JwtAuth::parseToken()->authenticate();
            $tokenPayload = JWTAuth::parseToken()->getPayload();
            $userToken = $tokenPayload->get('user');

            if (!$authUser) {
                if (!$userToken) {
                    return response()->json([
                        'error' => 'User not authenticated or invalid token.'
                    ], Response::HTTP_UNAUTHORIZED);
                }
            }
            if (!$authUser->active || !$authUser->super) {
                if (!$userToken->get('active') || !$userToken->get('super')) {
                    return response()->json([
                        'error' => 'Inactive user. Access denied.'
                    ], Response::HTTP_FORBIDDEN);
                }
            }
        } catch (TokenExpiredException $e) {
            return response()->json(['error' => 'Token expired. Please login again.'], Response::HTTP_UNAUTHORIZED);
        } catch (TokenInvalidException $e) {
            return response()->json(['error' => 'Token invalid. Please log in again.'], Response::HTTP_UNAUTHORIZED);
        } catch (JWTException $e) {
            return response()->json(['error' => 'Token missing. Please provide a valid token.'], Response::HTTP_BAD_REQUEST);
        }
        return $next($request);
    }
}
