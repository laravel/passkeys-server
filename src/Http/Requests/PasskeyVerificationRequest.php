<?php

declare(strict_types=1);

namespace Laravel\Passkeys\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\ValidationException;
use Laravel\Passkeys\Support\WebAuthn;
use Throwable;
use Webauthn\PublicKeyCredential;
use Webauthn\PublicKeyCredentialRequestOptions;

class PasskeyVerificationRequest extends FormRequest
{
    /**
     * The deserialized public key credential.
     */
    protected PublicKeyCredential $publicKeyCredential;

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'credential' => ['required', 'array'],
            'credential.id' => ['required', 'string'],
            'credential.rawId' => ['required', 'string'],
            'credential.type' => ['required', 'string', 'in:public-key'],
            'credential.response' => ['required', 'array'],
            'remember' => ['boolean'],
        ];
    }

    /**
     * Handle a passed validation attempt.
     */
    protected function passedValidation(): void
    {
        try {
            $this->publicKeyCredential = WebAuthn::fromJson(
                json_encode($this->input('credential')) ?: '{}',
                PublicKeyCredential::class
            );
        } catch (Throwable) {
            throw ValidationException::withMessages([
                'credential' => __('Invalid credential format.'),
            ]);
        }
    }

    /**
     * Get the public key credential.
     */
    public function credential(): PublicKeyCredential
    {
        return $this->publicKeyCredential;
    }

    /**
     * Determine if the user wants to be remembered.
     */
    public function remember(): bool
    {
        return $this->boolean('remember', false);
    }

    /**
     * Get the verification options from the session.
     *
     * @throws ValidationException
     */
    public function verificationOptions(): PublicKeyCredentialRequestOptions
    {
        /** @var string|null $serialized */
        $serialized = $this->session()->pull('passkey.verification_options');

        if (! $serialized) {
            throw ValidationException::withMessages([
                'credential' => __('Passkey verification session expired. Please try again.'),
            ]);
        }

        return WebAuthn::fromJson($serialized, PublicKeyCredentialRequestOptions::class);
    }
}
