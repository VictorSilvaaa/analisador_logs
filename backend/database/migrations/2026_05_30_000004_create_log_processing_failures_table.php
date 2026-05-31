<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('log_processing_failures', function (Blueprint $table): void {
            $table->id();
            $table->text('file_path');
            $table->unsignedInteger('line_number')->nullable();
            $table->longText('content')->nullable();
            $table->text('error_message');
            $table->json('context')->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->text('resolved_message')->nullable();
            $table->timestamps();

            $table->index('line_number');
            $table->index('resolved_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('log_processing_failures');
    }
};
