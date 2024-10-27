<?php

namespace App\Http\Middleware;

use App\Stellar\StellarSep10Config;
use ArgoNavis\PhpAnchorSdk\Sep10\Sep10Jwt;
use Closure;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class StellarAuthMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param Closure(Request): (Response) $next
     */
    public function handle(Request $request, Closure $next): Response
    {

        try {
            $token = request()->bearerToken();
            if ($token === null) {
                Log::error(
                    'Failed to authenticate the incoming request, the JWT token is null.',
                    ['context' => 'stellar_auth'],
                );

                throw new Exception("missing jwt token");
            }
            Log::debug(
                'Authenticating the incoming request.',
                ['context' => 'stellar_auth', 'token' => $token],
            );

            $sep10Config = new StellarSep10Config();
            $jwtSigningKey = $sep10Config->getSep10JWTSigningKey();
            $sep10Jwt = Sep10Jwt::validateSep10Jwt($token, $jwtSigningKey);
            $request->merge(['stellar_auth' => $sep10Jwt->toArray()]);

            return $next($request);
        } catch (Exception $e) {
            Log::error(
                'Failed to authenticate the incoming request, invalid JWT token.',
                ['context' => 'stellar_auth', 'error' => $e->getMessage(),
                    'exception' => $e, 'http_status_code' => 403,
                ],
            );

            return new Response(__('shared_lang.error.unauthorized', ['error' => $e->getMessage()]), 403);
        }
    }
}
