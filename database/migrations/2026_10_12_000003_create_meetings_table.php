<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('meetings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->text('agenda')->nullable();
            $table->string('department')->nullable();
            $table->unsignedInteger('participants_est')->nullable();

            $table->dateTime('start_time');
            $table->dateTime('end_time');
            // time actually blocked on the Zoom account (meeting + buffer)
            $table->dateTime('block_start');
            $table->dateTime('block_end');

            $table->string('status')->default('scheduled'); // scheduled | failed | cancelled
            $table->foreignId('zoom_account_id')->nullable()->constrained()->nullOnDelete();
            $table->string('zoom_meeting_id')->nullable();
            $table->text('join_url')->nullable();
            $table->string('passcode')->nullable();
            $table->boolean('reminder_sent')->default(false);
            $table->timestamps();

            $table->index(['user_id', 'start_time']);
            $table->index('start_time');
            $table->index(['zoom_account_id', 'status', 'block_start', 'block_end'], 'meetings_account_block_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('meetings');
    }
};
