<?php

declare(strict_types=1);

namespace Laravel\Passkeys\Http\Responses;

use Illuminate\Http\JsonResponse;
use Laravel\Passkeys\Contracts\PasskeyVerificationResponse as PasskeyVerificationResponseContract;

class PasskeyVerificationResponse implements PasskeyVerificationResponseContract
{
    /**
     * Create an HTTP response that represents the object.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function toResponse($request)
    {
        if ($request->wantsJson()) {
            return new JsonResponse([
                'verified' => true,
                'redirect' => redirect()->intended(config('passkeys.redirect', '/dashboard'))->getTargetUrl(),
            ], 200);
        }

        return redirect()->intended(config('passkeys.redirect', '/dashboard'));
    }
}
