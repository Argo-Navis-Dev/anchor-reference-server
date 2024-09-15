<?php

namespace App\Http\Middleware;

use App\Stellar\StellarSep10Config;
use ArgoNavis\PhpAnchorSdk\Sep10\Sep10Jwt;
use Closure;
use Exception;
use Illuminate\Http\Request;
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
                throw new Exception("missing jwt token");
            }
            $sep10Config = new StellarSep10Config();
            $jwtSigningKey = $sep10Config->getSep10JWTSigningKey();
            $sep10Jwt = Sep10Jwt::validateSep10Jwt($token, $jwtSigningKey);
            $request->merge(['stellar_auth' => $sep10Jwt->toArray()]);

            return $next($request);
        } catch (Exception $e) {
            return new Response(__('shared_lang.error.unauthorized', ['error' => $e->getMessage()]), 403);
        }
    }
}
