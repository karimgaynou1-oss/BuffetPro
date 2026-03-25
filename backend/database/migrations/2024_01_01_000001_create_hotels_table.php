<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hotels', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('email');
            $table->string('phone')->nullable();
            $table->text('address')->nullable();
            $table->string('country', 2)->default('MA');
            $table->string('locale', 5)->default('fr');
            $table->string('currency', 3)->default('MAD');
            $table->string('timezone')->default('Africa/Casablanca');
            $table->string('logo_url')->nullable();
            $table->json('branding')->nullable(); // { primary_color, secondary_color, ... }
            $table->enum('plan', ['trial', 'starter', 'pro', 'enterprise'])->default('trial');
            $table->enum('subscription_status', ['active', 'trial', 'past_due', 'cancelled'])->default('trial');
            $table->string('stripe_customer_id')->nullable();
            $table->string('stripe_subscription_id')->nullable();
            $table->timestamp('trial_ends_at')->nullable();
            $table->timestamp('subscription_ends_at')->nullable();
            $table->integer('max_users')->default(3);
            $table->integer('repetition_alert_hours')->default(48);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->index('slug');
            $table->index('is_active');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hotels');
    }
};
