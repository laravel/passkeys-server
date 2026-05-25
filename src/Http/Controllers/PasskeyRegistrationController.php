<?php

declare(strict_types=1);

namespace Laravel\Passkeys\Http\Controllers;

use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Laravel\Passkeys\Actions\DeletePasskey;
use Laravel\Passkeys\Actions\GenerateRegistrationOptions;
use Laravel\Passkeys\Actions\StorePasskey;
use Laravel\Passkeys\Contracts\PasskeyDeletedResponse;
use Laravel\Passkeys\Contracts\PasskeyRegistrationResponse;
use Laravel\Passkeys\Contracts\PasskeyUser;
use Laravel\Passkeys\Http\Requests\PasskeyRegistrationRequest;
use Laravel\Passkeys\Passkey;
use Laravel\Passkeys\Support\WebAuthn;
use RuntimeException;

class PasskeyRegistrationController extends Controller
{
    /**
     * Get passkey registration options for the authenticated user.
     */
    public function index(Request $request, GenerateRegistrationOptions $generate): JsonResponse
    {
        $guard = (string) ($request->route()?->defaults['passkey_guard'] ?? 'web');

        $user = Auth::guard($guard)->user()
            ?? throw new AuthenticationException;

        $options = $generate($user, $guard);

        $serialized = WebAuthn::toJson($options);

        $request->session()->put('passkey.registration_options', $serialized);

        return response()->json([
            'options' => WebAuthn::toBrowserArray($options),
        ]);
    }

    /**
     * Store a new passkey for the authenticated user.
     */
    public function store(
        PasskeyRegistrationRequest $request,
        StorePasskey $storePasskey,
    ): PasskeyRegistrationResponse {
        $guard = (string) ($request->route()?->defaults['passkey_guard'] ?? 'web');

        $user = Auth::guard($guard)->user()
            ?? throw new AuthenticationException;

        $passkey = $storePasskey(
            $user,
            $request->string('name')->toString(),
            $request->credential(),
            $request->registrationOptions(),
            $guard,
        );

        return app(PasskeyRegistrationResponse::class)->withPasskey($passkey);
    }

    /**
     * Delete a passkey for the authenticated user.
     */
    public function destroy(
        Request $request,
        Passkey $passkey,
        DeletePasskey $deletePasskey,
    ): PasskeyDeletedResponse {
        $guard = (string) ($request->route()?->defaults['passkey_guard'] ?? 'web');

        $user = Auth::guard($guard)->user()
            ?? throw new AuthenticationException;

        if (! $user instanceof PasskeyUser) {
            throw new RuntimeException('User model must implement the PasskeyUser contract.');
        }

        $identifier = $user->getKey();
        $expectedType = $user->getMorphClass();

        abort_unless(
            is_scalar($identifier)
                && (string) $passkey->authenticatable_id === (string) $identifier
                && $passkey->authenticatable_type === $expectedType,
            403,
        );

        $deletePasskey($user, $passkey, $guard);

        return app(PasskeyDeletedResponse::class);
    }
}
