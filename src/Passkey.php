<?php

declare(strict_types=1);

namespace Laravel\Passkeys;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Laravel\Passkeys\Support\Aaguids;

/**
 * @mixin \Illuminate\Database\Eloquent\Builder<Passkey>
 *
 * @property int $id
 * @property int|string $user_id
 * @property string $name
 * @property string $credential_id
 * @property array<string, mixed> $credential
 * @property \Illuminate\Support\Carbon|null $last_used_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Contracts\Auth\Authenticatable $user
 * @property-read string|null $authenticator
 */
class Passkey extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'credential_id',
        'credential',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'credential' => 'json',
            'last_used_at' => 'datetime',
        ];
    }

    /**
     * Get the user that owns the passkey.
     *
     * @return BelongsTo<Model, $this>
     */
    public function user(): BelongsTo
    {
        /** @phpstan-ignore argument.type, argument.templateType */
        return $this->belongsTo(Passkeys::userModel());
    }

    /**
     * Get the authenticator name based on the AAGUID.
     */
    protected function authenticator(): Attribute
    {
        return Attribute::get(function (): ?string {
            $aaguid = $this->credential['aaguid'] ?? null;

            if (! $aaguid || $aaguid === '00000000-0000-0000-0000-000000000000') {
                return null;
            }

            return Aaguids::MAP[$aaguid] ?? null;
        });
    }
}
