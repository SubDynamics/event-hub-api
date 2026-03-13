<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table): void {
            $table->string('id', 36)->primary();
            $table->string('email')->unique();
            $table->string('password_hash');
            $table->string('display_name');
            $table->string('role', 32)->default('user');
            $table->timestamps();
        });

        Schema::create('hosts', function (Blueprint $table): void {
            $table->string('id', 36)->primary();
            $table->string('slug')->unique();
            $table->string('name');
            $table->enum('host_type', ['venue', 'band', 'promoter', 'individual']);
            $table->string('region_key', 16);
            $table->string('ownership_user_id', 36)->nullable();
            $table->timestamps();

            $table->foreign('ownership_user_id')->references('id')->on('users')->nullOnDelete();
            $table->index(['region_key', 'host_type']);
        });

        Schema::create('venues', function (Blueprint $table): void {
            $table->string('id', 36)->primary();
            $table->string('host_id', 36)->unique();
            $table->string('address');
            $table->string('city');
            $table->string('state', 64);
            $table->string('zip', 32)->nullable();
            $table->decimal('lat', 10, 7)->nullable();
            $table->decimal('lng', 10, 7)->nullable();
            $table->string('timezone', 64)->default('UTC');
            $table->string('website_url')->nullable();
            $table->timestamps();

            $table->foreign('host_id')->references('id')->on('hosts')->cascadeOnDelete();
            $table->index(['city', 'state']);
        });

        Schema::create('bands', function (Blueprint $table): void {
            $table->string('id', 36)->primary();
            $table->string('host_id', 36)->unique();
            $table->string('home_region_key', 16)->nullable();
            $table->json('genres')->nullable();
            $table->timestamps();

            $table->foreign('host_id')->references('id')->on('hosts')->cascadeOnDelete();
        });

        Schema::create('events', function (Blueprint $table): void {
            $table->string('id', 36)->primary();
            $table->string('title');
            $table->text('description')->nullable();
            $table->dateTime('starts_at_utc');
            $table->dateTime('ends_at_utc');
            $table->string('timezone', 64)->default('UTC');
            $table->string('region_key', 16);
            $table->string('venue_id', 36)->nullable();
            $table->enum('visibility', ['public', 'private', 'unlisted'])->default('public');
            $table->enum('source_type', ['aggregator', 'host_submitted'])->default('host_submitted');
            $table->string('source_ref')->nullable();
            $table->string('permalink_slug');
            $table->string('permalink_path')->unique();
            $table->string('hero_image_url')->nullable();
            $table->enum('status', ['draft', 'published', 'canceled'])->default('published');
            $table->string('created_by_user_id', 36)->nullable();
            $table->timestamps();

            $table->foreign('venue_id')->references('id')->on('venues')->nullOnDelete();
            $table->foreign('created_by_user_id')->references('id')->on('users')->nullOnDelete();

            $table->index(['region_key', 'starts_at_utc']);
            $table->index('starts_at_utc');
            $table->index(['source_type', 'source_ref']);
        });

        Schema::create('event_hosts', function (Blueprint $table): void {
            $table->string('event_id', 36);
            $table->string('host_id', 36);
            $table->string('role', 32)->default('organizer');
            $table->timestamps();

            $table->primary(['event_id', 'host_id']);
            $table->foreign('event_id')->references('id')->on('events')->cascadeOnDelete();
            $table->foreign('host_id')->references('id')->on('hosts')->cascadeOnDelete();
            $table->index(['host_id', 'event_id']);
        });

        Schema::create('categories', function (Blueprint $table): void {
            $table->string('id', 36)->primary();
            $table->string('slug')->unique();
            $table->string('name');
            $table->timestamps();
        });

        Schema::create('event_category', function (Blueprint $table): void {
            $table->string('event_id', 36);
            $table->string('category_id', 36);
            $table->timestamps();

            $table->primary(['event_id', 'category_id']);
            $table->foreign('event_id')->references('id')->on('events')->cascadeOnDelete();
            $table->foreign('category_id')->references('id')->on('categories')->cascadeOnDelete();
        });

        Schema::create('follows', function (Blueprint $table): void {
            $table->string('user_id', 36);
            $table->string('host_id', 36);
            $table->timestamps();

            $table->unique(['user_id', 'host_id']);
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
            $table->foreign('host_id')->references('id')->on('hosts')->cascadeOnDelete();
            $table->index('user_id');
        });

        Schema::create('rsvps', function (Blueprint $table): void {
            $table->string('id', 36)->primary();
            $table->string('user_id', 36);
            $table->string('event_id', 36);
            $table->enum('response', ['yes', 'no', 'maybe']);
            $table->dateTime('responded_at');
            $table->timestamps();

            $table->unique(['user_id', 'event_id']);
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
            $table->foreign('event_id')->references('id')->on('events')->cascadeOnDelete();
            $table->index(['event_id', 'response']);
        });

        Schema::create('checkins', function (Blueprint $table): void {
            $table->string('id', 36)->primary();
            $table->string('user_id', 36);
            $table->string('event_id', 36);
            $table->dateTime('checked_in_at');
            $table->timestamps();

            $table->unique(['user_id', 'event_id']);
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
            $table->foreign('event_id')->references('id')->on('events')->cascadeOnDelete();
        });

        Schema::create('badges', function (Blueprint $table): void {
            $table->string('id', 36)->primary();
            $table->string('code')->unique();
            $table->string('name');
            $table->text('description')->nullable();
            $table->timestamps();
        });

        Schema::create('user_badges', function (Blueprint $table): void {
            $table->string('user_id', 36);
            $table->string('badge_id', 36);
            $table->dateTime('earned_at');
            $table->timestamps();

            $table->primary(['user_id', 'badge_id']);
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
            $table->foreign('badge_id')->references('id')->on('badges')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_badges');
        Schema::dropIfExists('badges');
        Schema::dropIfExists('checkins');
        Schema::dropIfExists('rsvps');
        Schema::dropIfExists('follows');
        Schema::dropIfExists('event_category');
        Schema::dropIfExists('categories');
        Schema::dropIfExists('event_hosts');
        Schema::dropIfExists('events');
        Schema::dropIfExists('bands');
        Schema::dropIfExists('venues');
        Schema::dropIfExists('hosts');
        Schema::dropIfExists('users');
    }
};
