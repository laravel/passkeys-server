<?php

declare(strict_types=1);

namespace Laravel\Passkeys\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\ValidationException;
use Laravel\Passkeys\Support\WebAuthn;
use Throwable;
use Webauthn\PublicKeyCredential;
use Webauthn\PublicKeyCredentialCreationOptions;

class PasskeyRegistrationRequest extends FormRequest
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
            'name' => ['required', 'string', 'max:255'],
            'credential' => ['required', 'array'],
            'credential.id' => ['required', 'string'],
            'credential.rawId' => ['required', 'string'],
            'credential.type' => ['required', 'string', 'in:public-key'],
            'credential.response' => ['required', 'array'],
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
     * Get the registration options from the session.
     *
     * @throws ValidationException
     */
    public function registrationOptions(): PublicKeyCredentialCreationOptions
    {
        /** @var string|null $serialized */
        $serialized = $this->session()->pull('passkey.registration_options');

        if (! $serialized) {
            throw ValidationException::withMessages([
                'credential' => __('Passkey registration session expired. Please try again.'),
            ]);
        }

        return WebAuthn::fromJson($serialized, PublicKeyCredentialCreationOptions::class);
    }
}
