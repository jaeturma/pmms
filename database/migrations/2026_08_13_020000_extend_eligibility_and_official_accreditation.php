<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('meets', function (Blueprint $table) {
            $table->date('eligibility_cutoff_date')->nullable()->after('ends_at');
            $table->boolean('medical_clearance_required')->default(true)->after('eligibility_cutoff_date');
            $table->unsignedTinyInteger('max_events_per_athlete')->nullable()->after('medical_clearance_required');
        });

        Schema::table('sport_categories', function (Blueprint $table) {
            $table->unsignedTinyInteger('min_age')->nullable();
            $table->unsignedTinyInteger('max_age')->nullable();
            $table->unsignedTinyInteger('min_grade')->nullable();
            $table->unsignedTinyInteger('max_grade')->nullable();
        });

        Schema::table('eligibility_documents', function (Blueprint $table) {
            $table->string('status', 20)->default('submitted')->after('document_type')->index();
            $table->foreignId('school_id')->nullable()->after('status')->constrained()->nullOnDelete();
            $table->string('school_year', 20)->nullable()->after('school_id');
            $table->date('examination_date')->nullable()->after('school_year');
            $table->string('guardian_name', 120)->nullable()->after('examination_date');
            $table->string('guardian_relationship', 60)->nullable()->after('guardian_name');
            $table->date('signed_at')->nullable()->after('guardian_relationship');
            $table->foreignId('verified_by')->nullable()->after('signed_at')->constrained('users')->nullOnDelete();
            $table->timestamp('verified_at')->nullable()->after('verified_by');
            $table->text('remarks')->nullable()->after('verified_at');
        });

        Schema::create('technical_official_accreditations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('sport_id')->constrained()->restrictOnDelete();
            $table->foreignId('file_upload_id')->constrained()->restrictOnDelete();
            $table->string('accreditation_type', 100);
            $table->string('certificate_number', 100)->nullable();
            $table->string('issuing_organization', 160)->nullable();
            $table->string('level', 80)->nullable();
            $table->date('issued_at')->nullable();
            $table->date('expires_at')->nullable();
            $table->string('status', 20)->default('submitted')->index();
            $table->foreignId('verified_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('verified_at')->nullable();
            $table->text('remarks')->nullable();
            $table->timestamps();
            $table->unique(['user_id', 'sport_id', 'certificate_number'], 'official_accreditation_identity');
        });

        Schema::create('eligibility_checks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('meet_id')->constrained()->restrictOnDelete();
            $table->string('subject_type', 40);
            $table->unsignedBigInteger('subject_id');
            $table->foreignId('sport_id')->nullable()->constrained()->restrictOnDelete();
            $table->foreignId('sport_category_id')->nullable()->constrained()->restrictOnDelete();
            $table->foreignId('event_id')->nullable()->constrained()->restrictOnDelete();
            $table->string('result', 30)->index();
            $table->foreignId('checked_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('checked_at');
            $table->json('snapshot');
            $table->timestamps();
            $table->index(['subject_type', 'subject_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('eligibility_checks');
        Schema::dropIfExists('technical_official_accreditations');
        Schema::table('eligibility_documents', function (Blueprint $table) {
            $table->dropConstrainedForeignId('verified_by');
            $table->dropConstrainedForeignId('school_id');
            $table->dropColumn(['status', 'school_year', 'examination_date', 'guardian_name', 'guardian_relationship', 'signed_at', 'verified_at', 'remarks']);
        });
        Schema::table('sport_categories', fn (Blueprint $table) => $table->dropColumn(['min_age', 'max_age', 'min_grade', 'max_grade']));
        Schema::table('meets', fn (Blueprint $table) => $table->dropColumn(['eligibility_cutoff_date', 'medical_clearance_required', 'max_events_per_athlete']));
    }
};
