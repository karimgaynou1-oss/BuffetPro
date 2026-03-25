<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Token blacklist / revocation table
        Schema::create('revoked_tokens', function (Blueprint $table) {
            $table->id();
            $table->string('token_hash', 64)->unique();
            $table->timestamp('expires_at');
            $table->timestamp('revoked_at')->useCurrent();

            $table->index('expires_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('revoked_tokens');
    }
};
