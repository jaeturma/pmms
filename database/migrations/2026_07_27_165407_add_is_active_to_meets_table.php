<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * The single meet featured on the public landing page — distinct from
     * `status` (lifecycle) and `is_published` (portal visibility). Only one
     * meet is ever active at a time, enforced in MeetController::setActive().
     */
    public function up(): void
    {
        Schema::table('meets', function (Blueprint $table) {
            $table->boolean('is_active')->default(false)->index()->after('is_published');
        });
    }

    public function down(): void
    {
        Schema::table('meets', function (Blueprint $table) {
            $table->dropColumn('is_active');
        });
    }
};
