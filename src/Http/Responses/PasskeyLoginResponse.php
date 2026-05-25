<?php

declare(strict_types=1);

namespace Laravel\Passkeys\Http\Responses;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Laravel\Passkeys\Contracts\PasskeyLoginResponse as PasskeyLoginResponseContract;
use Symfony\Component\HttpFoundation\Response;

class PasskeyLoginResponse implements PasskeyLoginResponseContract
{
    /**
     * Create an HTTP response that represents the object.
     *
     * @param  Request  $request
     * @return Response
     */
    public function toResponse($request)
    {
        $guard = (string) ($request->route()?->defaults['passkey_guard'] ?? 'web');
        $redirect = (string) config("passkeys.guards.{$guard}.redirect", '/');

        if ($request->wantsJson()) {
            return new JsonResponse([
                'redirect' => redirect()->intended($redirect)->getTargetUrl(),
            ], 200);
        }

        return redirect()->intended($redirect);
    }
}
