<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Where each meeting stands in the nightly Zoom -> Google Drive copy.
        Schema::table('meetings', function (Blueprint $table) {
            $table->string('recordings_status', 20)->nullable()->after('recordings_synced_at'); // processing | none | failed | queued | archived | purged
            $table->text('recordings_error')->nullable()->after('recordings_status');
            $table->timestamp('recordings_checked_at')->nullable()->after('recordings_error');

            $table->index('recordings_status');
        });
    }

    public function down(): void
    {
        Schema::table('meetings', function (Blueprint $table) {
            $table->dropIndex(['recordings_status']);
            $table->dropColumn(['recordings_status', 'recordings_error', 'recordings_checked_at']);
        });
    }
};
