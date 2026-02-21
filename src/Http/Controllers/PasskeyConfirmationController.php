<?php

declare(strict_types=1);

namespace Laravel\Passkeys\Http\Controllers;

use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Session\Store as SessionStore;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Config;
use Laravel\Passkeys\Actions\GenerateVerificationOptions;
use Laravel\Passkeys\Actions\VerifyPasskey;
use Laravel\Passkeys\Contracts\PasskeyConfirmationResponse;
use Laravel\Passkeys\Contracts\PasskeyUser;
use Laravel\Passkeys\Http\Requests\PasskeyVerificationRequest;
use Laravel\Passkeys\Support\WebAuthn;
use RuntimeException;

class PasskeyConfirmationController extends Controller
{
    /**
     * Get passkey confirmation options for the authenticated user.
     */
    public function index(Request $request, GenerateVerificationOptions $generate): JsonResponse
    {
        $user = Auth::guard(Config::string('passkeys.guard'))->user()
            ?? throw new AuthenticationException;

        if (! $user instanceof PasskeyUser) {
            throw new RuntimeException('User model must implement the PasskeyUser contract.');
        }

        $options = $generate($user);

        $serialized = WebAuthn::toJson($options);

        $request->session()->put('passkey.verification_options', $serialized);

        return response()->json([
            'options' => json_decode($serialized, true),
        ]);
    }

    /**
     * Confirm the user's password via passkey verification.
     */
    public function store(
        PasskeyVerificationRequest $request,
        VerifyPasskey $verify,
    ): PasskeyConfirmationResponse {
        $user = Auth::guard(Config::string('passkeys.guard'))->user()
            ?? throw new AuthenticationException;

        if (! $user instanceof PasskeyUser) {
            throw new RuntimeException('User model must implement the PasskeyUser contract.');
        }

        $verify(
            $request->credential(),
            $request->verificationOptions(),
            $user
        );

        /** @var SessionStore $session */
        $session = $request->session();

        $session->passwordConfirmed();

        return app(PasskeyConfirmationResponse::class);
    }
}
