<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('news_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('meet_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('author_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('featured_image_upload_id')->nullable()->constrained('file_uploads')->nullOnDelete();
            $table->string('title', 180);
            $table->string('slug', 200)->unique();
            $table->text('summary')->nullable();
            $table->longText('body');
            $table->string('status', 20)->default('draft')->index();
            $table->boolean('is_featured')->default(false);
            $table->timestamp('published_at')->nullable()->index();
            $table->timestamps();
        });

        Schema::create('faq_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('meet_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('question', 255);
            $table->text('answer');
            $table->string('category', 80)->default('General')->index();
            $table->unsignedInteger('display_order')->default(0);
            $table->string('status', 20)->default('draft')->index();
            $table->boolean('is_featured')->default(false);
            $table->timestamp('published_at')->nullable();
            $table->timestamps();
        });

        Schema::create('gallery_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('meet_id')->constrained()->cascadeOnDelete();
            $table->foreignId('meet_sport_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('sport_event_id')->nullable()->constrained('events')->nullOnDelete();
            $table->foreignId('file_upload_id')->constrained('file_uploads')->restrictOnDelete();
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('title', 180)->nullable();
            $table->text('caption')->nullable();
            $table->text('description')->nullable();
            $table->date('capture_date')->index();
            $table->string('status', 20)->default('draft')->index();
            $table->boolean('is_featured')->default(false);
            $table->unsignedInteger('display_order')->nullable();
            $table->string('file_hash', 64)->nullable()->index();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('published_at')->nullable()->index();
            $table->timestamp('rejected_at')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->timestamps();

            $table->index(['meet_sport_id', 'capture_date', 'status'], 'gallery_sport_day_status');
        });

        Schema::table('announcements', function (Blueprint $table) {
            $table->string('status', 20)->default('draft')->after('body')->index();
            $table->string('priority', 20)->default('normal')->after('status');
            $table->string('audience', 40)->default('public')->after('priority')->index();
            $table->timestamp('starts_at')->nullable()->after('audience');
            $table->timestamp('ends_at')->nullable()->after('starts_at');
        });

        DB::table('announcements')->where('is_published', true)->update(['status' => 'published']);
    }

    public function down(): void
    {
        Schema::table('announcements', function (Blueprint $table) {
            $table->dropColumn(['status', 'priority', 'audience', 'starts_at', 'ends_at']);
        });
        Schema::dropIfExists('gallery_items');
        Schema::dropIfExists('faq_items');
        Schema::dropIfExists('news_items');
    }
};
