<?php

declare(strict_types=1);

namespace Laravel\Passkeys\Http\Responses;

use Illuminate\Http\JsonResponse;
use Laravel\Passkeys\Contracts\PasskeyRegistrationResponse as PasskeyRegistrationResponseContract;
use Laravel\Passkeys\Passkey;

class PasskeyRegistrationResponse implements PasskeyRegistrationResponseContract
{
    /**
     * The passkey that was registered.
     */
    protected ?Passkey $passkey = null;

    /**
     * Set the passkey that was registered.
     */
    public function withPasskey(Passkey $passkey): static
    {
        $this->passkey = $passkey;

        return $this;
    }

    /**
     * Create an HTTP response that represents the object.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function toResponse($request)
    {
        if ($request->wantsJson()) {
            $data = ['status' => 'passkey-registered'];

            if ($this->passkey) {
                $data['id'] = (string) $this->passkey->id;
                $data['name'] = $this->passkey->name;
            }

            return new JsonResponse($data, 200);
        }

        return back()->with('status', 'passkey-registered');
    }
}
