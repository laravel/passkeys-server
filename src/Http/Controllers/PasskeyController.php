<?php

declare(strict_types=1);

namespace Laravel\Passkeys\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Laravel\Passkeys\Actions\DeletePasskey;
use Laravel\Passkeys\Contracts\PasskeyDeletedResponse;
use Laravel\Passkeys\Passkey;

class PasskeyController extends Controller
{
    /**
     * List all passkeys for the authenticated user.
     */
    public function index(Request $request): JsonResponse
    {
        $passkeys = $request->user()->passkeys()
            ->select(['id', 'name', 'created_at', 'last_used_at'])
            ->latest()
            ->get();

        return response()->json([
            'passkeys' => $passkeys,
        ]);
    }

    /**
     * Delete a passkey for the authenticated user.
     */
    public function destroy(
        Request $request,
        Passkey $passkey,
        DeletePasskey $deletePasskey
    ): PasskeyDeletedResponse {
        $user = $request->user();

        abort_unless($passkey->user_id === $user->getAuthIdentifier(), 403);

        $deletePasskey($user, $passkey);

        return app(PasskeyDeletedResponse::class);
    }
}
