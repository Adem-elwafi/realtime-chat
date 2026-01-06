<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * This migration adds three fields to the users table that will be used
     * for our real-time chat application:
     * - avatar: Stores the path to the user's profile picture (nullable)
     * - is_online: Boolean flag to indicate if the user is currently online
     * - last_seen: Timestamp of the user's last activity
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Add avatar column - stores path to profile image
            $table->string('avatar')->nullable();
            
            // Add is_online column - indicates if user is currently active
            $table->boolean('is_online')->default(false);
            
            // Add last_seen column - tracks when user was last active
            $table->timestamp('last_seen')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * This method removes the columns added in the up() method.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['avatar', 'is_online', 'last_seen']);
        });
    }
};