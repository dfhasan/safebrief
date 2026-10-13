<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('companies', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->timestamp('trial_ends_at');
            $table->timestamps();
        });

        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('company_id')->nullable()->after('id')->constrained()->cascadeOnDelete();
        });

        Schema::create('briefings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->text('incident_text');
            $table->string('title');
            $table->text('summary');
            $table->json('key_actions');
            $table->string('status')->default('draft');
            $table->timestamp('published_at')->nullable();
            $table->timestamps();
        });

        Schema::create('questions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('briefing_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('position');
            $table->string('prompt');
            $table->json('options');
            $table->unsignedTinyInteger('correct_index');
            $table->timestamps();
        });

        Schema::create('crew_members', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('contact')->nullable();
            $table->timestamps();
        });

        Schema::create('crew_links', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('briefing_id')->constrained()->cascadeOnDelete();
            $table->foreignId('crew_member_id')->constrained()->cascadeOnDelete();
            $table->string('token', 64)->unique();
            $table->timestamp('opened_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->json('answers')->nullable();
            $table->unsignedTinyInteger('score')->nullable();
            $table->timestamps();
            $table->unique(['briefing_id', 'crew_member_id']);
        });

        Schema::create('funnel_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->timestamp('occurred_at');
            $table->unique(['company_id', 'name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('funnel_events');
        Schema::dropIfExists('crew_links');
        Schema::dropIfExists('crew_members');
        Schema::dropIfExists('questions');
        Schema::dropIfExists('briefings');
        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('company_id');
        });
        Schema::dropIfExists('companies');
    }
};
