<?php

declare(strict_types=1);

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Laravel\Passkeys\Passkeys;
use Laravel\Passkeys\Tests\User;

/**
 * Simulate the fork-as-standalone upgrade path:
 *
 *   1. Drop the polymorphic test fixture table (created by TestCase::defineDatabaseMigrations).
 *   2. Re-create it with upstream's single-guard schema (id + user_id FK + ...).
 *   3. Run the additive `make_passkeys_polymorphic` migration on top.
 *   4. Assert the resulting schema matches the polymorphic shape.
 */
function rebuildPasskeysTableAsSingleGuard(): void
{
    Schema::dropIfExists('passkeys');

    Schema::create('passkeys', function (Blueprint $table): void {
        $table->id();
        $table->foreignIdFor(Passkeys::userModel(), 'user_id');
        $table->string('name');
        $table->string('credential_id')->unique();
        $table->json('credential');
        $table->timestamp('last_used_at')->nullable();
        $table->timestamps();

        $table->index('user_id');
    });
}

function runMakePolymorphicMigration(): void
{
    $migration = require __DIR__.'/../../../database/migrations/2026_05_25_000001_make_passkeys_polymorphic.php';
    $migration->up();
}

it('adds the authenticatable_type column', function (): void {
    rebuildPasskeysTableAsSingleGuard();

    expect(Schema::hasColumn('passkeys', 'authenticatable_type'))->toBeFalse();

    runMakePolymorphicMigration();

    expect(Schema::hasColumn('passkeys', 'authenticatable_type'))->toBeTrue();
});

it('adds the authenticatable_id column', function (): void {
    rebuildPasskeysTableAsSingleGuard();

    expect(Schema::hasColumn('passkeys', 'authenticatable_id'))->toBeFalse();

    runMakePolymorphicMigration();

    expect(Schema::hasColumn('passkeys', 'authenticatable_id'))->toBeTrue();
});

it('drops the user_id column', function (): void {
    rebuildPasskeysTableAsSingleGuard();

    expect(Schema::hasColumn('passkeys', 'user_id'))->toBeTrue();

    runMakePolymorphicMigration();

    expect(Schema::hasColumn('passkeys', 'user_id'))->toBeFalse();
});

it('creates the composite authenticatable index', function (): void {
    rebuildPasskeysTableAsSingleGuard();

    runMakePolymorphicMigration();

    $indexes = collect(Schema::getIndexes('passkeys'))
        ->pluck('name')
        ->all();

    expect($indexes)->toContain('passkeys_authenticatable_index');
});

it('backfills authenticatable columns from user_id on existing rows', function (): void {
    rebuildPasskeysTableAsSingleGuard();

    $user = User::create([
        'name' => 'Alice',
        'email' => 'alice@example.com',
    ]);

    DB::table('passkeys')->insert([
        'user_id' => $user->getKey(),
        'name' => 'legacy device',
        'credential_id' => 'legacy-credential-id',
        'credential' => json_encode([]),
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    runMakePolymorphicMigration();

    $row = DB::table('passkeys')->where('credential_id', 'legacy-credential-id')->first();

    expect($row->authenticatable_type)->toBe(User::class);
    expect((string) $row->authenticatable_id)->toBe((string) $user->getKey());
});

it('is idempotent on re-run', function (): void {
    rebuildPasskeysTableAsSingleGuard();

    runMakePolymorphicMigration();
    runMakePolymorphicMigration();

    expect(Schema::hasColumn('passkeys', 'authenticatable_type'))->toBeTrue();
    expect(Schema::hasColumn('passkeys', 'authenticatable_id'))->toBeTrue();
    expect(Schema::hasColumn('passkeys', 'user_id'))->toBeFalse();
});
