<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pilot_sessions', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('driver');
            $table->string('remote_id')->index();
            $table->text('connect_url')->nullable();
            $table->text('live_view_url')->nullable();
            $table->string('status')->default('running')->index();
            $table->string('profile')->nullable();
            $table->string('region')->nullable();
            $table->text('handoff_reason')->nullable();
            $table->timestamp('handoff_requested_at')->nullable();
            $table->timestamp('handed_back_at')->nullable();
            $table->nullableMorphs('owner');
            $table->json('metadata')->nullable();
            $table->timestamp('ended_at')->nullable();
            $table->timestamps();
        });

        Schema::create('pilot_profiles', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('driver');
            $table->string('remote_id');
            $table->nullableMorphs('owner');
            $table->timestamps();

            $table->unique(['driver', 'name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pilot_profiles');
        Schema::dropIfExists('pilot_sessions');
    }
};
