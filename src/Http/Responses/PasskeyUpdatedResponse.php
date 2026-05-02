<?php

declare(strict_types=1);

namespace Laravel\Passkeys\Http\Responses;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Laravel\Passkeys\Contracts\PasskeyUpdatedResponse as PasskeyUpdatedResponseContract;
use Laravel\Passkeys\Passkey;
use Symfony\Component\HttpFoundation\Response;

class PasskeyUpdatedResponse implements PasskeyUpdatedResponseContract
{
    /**
     * The passkey that was updated.
     */
    protected ?Passkey $passkey = null;

    /**
     * Set the passkey that was updated.
     */
    public function withPasskey(Passkey $passkey): static
    {
        $this->passkey = $passkey;

        return $this;
    }

    /**
     * Create an HTTP response that represents the object.
     *
     * @param  Request  $request
     * @return Response
     */
    public function toResponse($request)
    {
        if (! $request->wantsJson()) {
            return back()->with('status', 'passkey-updated');
        }

        $data = ['status' => 'passkey-updated'];

        if ($this->passkey instanceof Passkey) {
            $data['id'] = (string) $this->passkey->id;
            $data['name'] = $this->passkey->name;
        }

        return new JsonResponse($data, 200);
    }
}
