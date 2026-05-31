<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('services', function (Blueprint $table): void {
            $table->id();
            $table->uuid('external_id')->unique();
            $table->string('name')->nullable();
            $table->string('host');
            $table->string('path')->nullable();
            $table->unsignedInteger('port');
            $table->string('protocol', 20);
            $table->unsignedInteger('connect_timeout')->nullable();
            $table->unsignedInteger('read_timeout')->nullable();
            $table->unsignedInteger('write_timeout')->nullable();
            $table->unsignedInteger('retries')->nullable();
            $table->unsignedInteger('service_created_at')->nullable();
            $table->unsignedInteger('service_updated_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('services');
    }
};
