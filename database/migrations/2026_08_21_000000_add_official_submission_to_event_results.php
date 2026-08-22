<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('event_results', function (Blueprint $table) {
            $table->unsignedInteger('version')->default(1)->after('status');
            $table->unsignedInteger('form_generated_version')->nullable()->after('version');
            $table->timestamp('form_generated_at')->nullable()->after('form_generated_version');
            $table->foreignId('submitted_by')->nullable()->after('validated_at')->constrained('users')->nullOnDelete();
            $table->timestamp('submitted_at')->nullable()->after('submitted_by');
            $table->foreignId('returned_by')->nullable()->after('submitted_at')->constrained('users')->nullOnDelete();
            $table->timestamp('returned_at')->nullable()->after('returned_by');
            $table->text('return_reason')->nullable()->after('returned_at');
            $table->foreignId('official_by')->nullable()->after('return_reason')->constrained('users')->nullOnDelete();
            $table->timestamp('official_at')->nullable()->after('official_by');
        });

        Schema::create('result_attachments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_result_id')->constrained()->cascadeOnDelete();
            $table->foreignId('file_upload_id')->constrained()->restrictOnDelete();
            $table->string('attachment_type', 40)->default('signed_result_form');
            $table->unsignedInteger('result_version');
            $table->string('checksum_sha256', 64);
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->boolean('is_current')->default(true);
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['event_result_id', 'attachment_type', 'is_current'], 'result_attachment_current_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('result_attachments');

        Schema::table('event_results', function (Blueprint $table) {
            $table->dropConstrainedForeignId('submitted_by');
            $table->dropConstrainedForeignId('returned_by');
            $table->dropConstrainedForeignId('official_by');
            $table->dropColumn([
                'version', 'form_generated_version', 'form_generated_at', 'submitted_at',
                'returned_at', 'return_reason', 'official_at',
            ]);
        });
    }
};
