<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Laravel\Passkeys\Passkeys;

return new class extends Migration
{
    public function up(): void
    {
        // Step 1: add polymorphic columns (nullable for backfill).
        Schema::table('passkeys', function (Blueprint $table): void {
            if (! Schema::hasColumn('passkeys', 'authenticatable_type')) {
                $table->string('authenticatable_type')->nullable()->after('id');
            }

            if (! Schema::hasColumn('passkeys', 'authenticatable_id')) {
                $table->string('authenticatable_id')->nullable()->after('authenticatable_type');
            }
        });

        // Step 2: backfill from user_id for fork-as-standalone users
        // upgrading from upstream's single-guard schema.
        if (Schema::hasColumn('passkeys', 'user_id')) {
            $defaultUserModel = Passkeys::$userModel ?? 'App\\Models\\User';

            DB::table('passkeys')
                ->whereNotNull('user_id')
                ->update([
                    'authenticatable_type' => $defaultUserModel,
                    'authenticatable_id' => DB::raw('CAST(user_id AS CHAR)'),
                ]);
        }

        // Step 3: drop user_id (FK + column). No-op on fresh installs.
        Schema::table('passkeys', function (Blueprint $table): void {
            if (! Schema::hasColumn('passkeys', 'user_id')) {
                return;
            }

            try {
                $table->dropForeign(['user_id']);
            } catch (Throwable) {
                // FK may not exist (e.g. SQLite); ignore.
            }

            try {
                $table->dropIndex(['user_id']);
            } catch (Throwable) {
                // Index may not exist; ignore.
            }

            $table->dropColumn('user_id');
        });

        // Step 4: index the polymorphic pair.
        // Blueprint commands are deferred to a single execute at the end of
        // Schema::table(), so we cannot try/catch the addition itself.
        // Instead, check for the index up-front via Schema::getIndexes().
        $existingIndexes = collect(Schema::getIndexes('passkeys'))
            ->pluck('name')
            ->all();

        if (! in_array('passkeys_authenticatable_index', $existingIndexes, true)) {
            Schema::table('passkeys', function (Blueprint $table): void {
                $table->index(
                    ['authenticatable_type', 'authenticatable_id'],
                    'passkeys_authenticatable_index'
                );
            });
        }

        // Backfill verification — abort if any orphan rows remain.
        $orphans = DB::table('passkeys')
            ->where(function ($query): void {
                $query->whereNull('authenticatable_type')
                    ->orWhereNull('authenticatable_id');
            })
            ->count();

        if ($orphans > 0) {
            throw new RuntimeException(
                "Migration left {$orphans} orphan passkey rows with null authenticatable. Aborting."
            );
        }
    }

    public function down(): void
    {
        Schema::table('passkeys', function (Blueprint $table): void {
            try {
                $table->dropIndex('passkeys_authenticatable_index');
            } catch (Throwable) {
                // Index may not exist; ignore.
            }

            $table->dropColumn(['authenticatable_type', 'authenticatable_id']);
            // user_id is NOT restored — down is irreversible from polymorphic.
        });
    }
};
