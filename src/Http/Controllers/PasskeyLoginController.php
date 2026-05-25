<?php

declare(strict_types=1);

namespace Laravel\Passkeys\Http\Controllers;

use Illuminate\Contracts\Auth\StatefulGuard;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Laravel\Passkeys\Actions\GenerateVerificationOptions;
use Laravel\Passkeys\Actions\VerifyPasskey;
use Laravel\Passkeys\Contracts\PasskeyLoginResponse;
use Laravel\Passkeys\Exceptions\InvalidPasskeyException;
use Laravel\Passkeys\Http\Requests\PasskeyVerificationRequest;
use Laravel\Passkeys\Passkeys;
use Laravel\Passkeys\Support\WebAuthn;
use RuntimeException;

class PasskeyLoginController extends Controller
{
    /**
     * Get passkey login options.
     */
    public function index(Request $request, GenerateVerificationOptions $generate): JsonResponse
    {
        $guard = (string) ($request->route()?->defaults['passkey_guard'] ?? 'web');

        $options = $generate(null, $guard);

        $serialized = WebAuthn::toJson($options);

        $request->session()->put('passkey.verification_options', $serialized);

        return response()->json([
            'options' => WebAuthn::toBrowserArray($options),
        ]);
    }

    /**
     * Verify the passkey and log the user in.
     */
    public function store(
        PasskeyVerificationRequest $request,
        VerifyPasskey $verify,
    ): PasskeyLoginResponse {
        $guard = (string) ($request->route()?->defaults['passkey_guard'] ?? 'web');

        $passkey = $verify(
            $request->credential(),
            $request->verificationOptions(),
            null,
            $guard,
        );

        $statefulGuard = Auth::guard($guard);

        if (! $statefulGuard instanceof StatefulGuard) {
            throw new RuntimeException('Passkeys requires a stateful authentication guard.');
        }

        if (! Passkeys::allowsLogin($request, $passkey, $guard)) {
            throw InvalidPasskeyException::make('Unable to sign in with this account.');
        }

        $statefulGuard->login($passkey->authenticatable, $request->remember());

        $request->session()->regenerate();

        return app(PasskeyLoginResponse::class);
    }
}
