<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Laravel\Passkeys\Passkeys;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table($this->usersTable(), function (Blueprint $table): void {
            $table->string('passkey_user_handle', 64)->nullable()->unique();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table($this->usersTable(), function (Blueprint $table): void {
            $table->dropColumn('passkey_user_handle');
        });
    }

    /**
     * Resolve the users table name from the configured user model.
     */
    protected function usersTable(): string
    {
        $userModel = Passkeys::userModel();

        return (new $userModel)->getTable();
    }
};
