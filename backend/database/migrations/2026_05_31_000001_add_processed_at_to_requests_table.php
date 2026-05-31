<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('requests', function (Blueprint $table): void {
            $table->timestamp('processed_at')->nullable()->after('started_at')->index();
        });
    }

    public function down(): void
    {
        Schema::table('requests', function (Blueprint $table): void {
            $table->dropIndex(['processed_at']);
            $table->dropColumn('processed_at');
        });
    }
};
