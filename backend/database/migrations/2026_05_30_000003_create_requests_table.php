<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('requests', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('consumer_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('service_id')->nullable()->constrained()->nullOnDelete();
            $table->string('method', 20);
            $table->text('uri');
            $table->text('url');
            $table->unsignedInteger('request_size')->nullable();
            $table->text('upstream_uri')->nullable();
            $table->unsignedSmallInteger('response_status');
            $table->unsignedInteger('response_size')->nullable();
            $table->unsignedInteger('proxy_latency')->nullable();
            $table->unsignedInteger('gateway_latency')->nullable();
            $table->unsignedInteger('request_latency')->nullable();
            $table->string('client_ip', 45)->nullable();
            $table->unsignedInteger('started_at');
            $table->json('request_headers')->nullable();
            $table->json('response_headers')->nullable();
            $table->json('querystring')->nullable();
            $table->timestamps();

            $table->index('method');
            $table->index('response_status');
            $table->index('client_ip');
            $table->index('started_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('requests');
    }
};
