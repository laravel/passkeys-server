<?php

declare(strict_types=1);

namespace Laravel\Passkeys\Http\Controllers;

use Illuminate\Contracts\Auth\StatefulGuard;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Config;
use Laravel\Passkeys\Actions\GenerateVerificationOptions;
use Laravel\Passkeys\Actions\VerifyPasskey;
use Laravel\Passkeys\Contracts\PasskeyVerificationResponse;
use Laravel\Passkeys\Http\Requests\PasskeyVerificationRequest;
use Laravel\Passkeys\Support\WebAuthn;
use RuntimeException;

class PasskeyVerificationController extends Controller
{
    /**
     * Get passkey verification options.
     */
    public function index(Request $request, GenerateVerificationOptions $generate): JsonResponse
    {
        $options = $generate();

        $serialized = WebAuthn::toJson($options);

        $request->session()->put('passkey.verification_options', $serialized);

        return response()->json([
            'options' => json_decode($serialized, true),
        ]);
    }

    /**
     * Verify the passkey and log the user in.
     */
    public function store(
        PasskeyVerificationRequest $request,
        VerifyPasskey $verify,
    ): PasskeyVerificationResponse {
        $passkey = $verify(
            $request->credential(),
            $request->verificationOptions()
        );

        $guard = Auth::guard(Config::string('passkeys.guard'));

        if (! $guard instanceof StatefulGuard) {
            throw new RuntimeException('Passkeys requires a stateful authentication guard.');
        }

        $guard->login($passkey->user, $request->remember());

        $request->session()->regenerate();

        return app(PasskeyVerificationResponse::class);
    }
}
