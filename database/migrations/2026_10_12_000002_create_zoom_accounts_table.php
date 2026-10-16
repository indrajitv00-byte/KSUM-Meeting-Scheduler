<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('zoom_accounts', function (Blueprint $table) {
            $table->id();
            $table->string('label');
            $table->string('host_email')->unique();
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('priority')->default(100); // lower number = used first
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('zoom_accounts');
    }
};
