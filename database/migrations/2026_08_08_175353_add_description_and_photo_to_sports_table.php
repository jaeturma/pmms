<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('sports', function (Blueprint $table) {
            $table->string('short_description', 200)->nullable()->after('name');
            $table->text('description')->nullable()->after('short_description');
            $table->foreignId('photo_upload_id')->nullable()->after('description')->constrained('file_uploads')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sports', function (Blueprint $table) {
            $table->dropConstrainedForeignId('photo_upload_id');
            $table->dropColumn(['short_description', 'description']);
        });
    }
};
