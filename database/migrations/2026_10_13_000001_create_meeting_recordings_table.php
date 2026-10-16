<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // One row per recording file that was copied from Zoom to Google Drive.
        Schema::create('meeting_recordings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('meeting_id')->constrained()->cascadeOnDelete();
            $table->string('zoom_file_id')->unique();
            $table->string('recording_type')->nullable();
            $table->string('file_type', 30);
            $table->unsignedBigInteger('file_size')->default(0);
            $table->dateTime('recording_start')->nullable();
            $table->string('name'); // file name in Drive, with extension
            $table->string('drive_file_id');
            $table->timestamps();

            $table->index('meeting_id');
        });

        // set once every recording of the meeting is in Drive and gone from Zoom
        Schema::table('meetings', function (Blueprint $table) {
            $table->timestamp('recordings_synced_at')->nullable()->after('reminder_sent');
        });
    }

    public function down(): void
    {
        Schema::table('meetings', function (Blueprint $table) {
            $table->dropColumn('recordings_synced_at');
        });
        Schema::dropIfExists('meeting_recordings');
    }
};
