<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sports', function (Blueprint $table) {
            $table->string('code', 40)->nullable()->after('id');
            $table->string('slug', 140)->nullable()->after('name');
            $table->string('classification', 20)->default('regular')->after('slug')->index();
            $table->string('icon_key', 80)->nullable()->after('description');
            $table->string('competition_format', 80)->nullable()->after('icon_key');
            $table->boolean('is_team_sport')->default(false)->after('competition_format');
            $table->unsignedSmallInteger('display_order')->default(0)->after('active')->index();
        });

        $aliases = [
            'Billiard' => 'Billiards',
            'Paragames - Boccee' => 'Bocce',
            'Paragames - Goal Ball' => 'Goalball',
            'Paragames - Athletics' => 'Para Athletics',
            'Paragames - Swimming' => 'Para Swimming',
        ];

        foreach ($aliases as $old => $new) {
            DB::table('sports')->where('name', $old)->update(['name' => $new]);
        }

        DB::table('sports')->orderBy('id')->get()->each(function ($sport): void {
            DB::table('sports')->where('id', $sport->id)->update([
                'code' => strtoupper(str_replace('-', '_', Str::slug($sport->name, '_'))),
                'slug' => Str::slug($sport->name),
                'classification' => str_starts_with($sport->name, 'Para ') || in_array($sport->name, ['Bocce', 'Goalball'], true)
                    ? 'paragames' : 'regular',
            ]);
        });

        Schema::table('sports', function (Blueprint $table) {
            $table->unique('code');
            $table->unique('slug');
        });

        Schema::table('sport_categories', function (Blueprint $table) {
            $table->string('name', 120)->nullable()->after('sport_id');
            $table->string('slug', 160)->nullable()->after('name');
            $table->string('classification', 40)->nullable()->after('discipline');
            $table->string('competition_format', 80)->nullable()->after('event_type');
            $table->unsignedSmallInteger('team_size')->nullable()->after('competition_format');
            $table->unsignedSmallInteger('min_players')->nullable()->after('team_size');
            $table->unsignedSmallInteger('max_players')->nullable()->after('min_players');
            $table->text('participation_notes')->nullable()->after('max_players');
            $table->unsignedSmallInteger('display_order')->default(0)->after('display_name');
            $table->index(['sport_id', 'slug']);
        });

        DB::table('sport_categories')->orderBy('id')->get()->each(function ($category): void {
            DB::table('sport_categories')->where('id', $category->id)->update([
                'name' => $category->display_name,
                'slug' => Str::slug($category->display_name),
            ]);
        });

        Schema::table('events', function (Blueprint $table) {
            $table->string('code', 80)->nullable()->after('sport_category_id');
            $table->string('slug', 180)->nullable()->after('name');
            $table->string('event_type', 40)->nullable()->after('slug');
            $table->string('discipline', 80)->nullable()->after('event_type');
            $table->string('weight_class', 80)->nullable()->after('discipline');
            $table->string('distance', 40)->nullable()->after('weight_class');
            $table->unsignedSmallInteger('team_size')->nullable()->after('distance');
            $table->boolean('is_medal_event')->default(true)->after('team_size');
            $table->unsignedSmallInteger('display_order')->default(0)->after('is_medal_event');
            $table->index(['sport_category_id', 'slug']);
        });

        DB::table('events')->orderBy('id')->get()->each(function ($event): void {
            DB::table('events')->where('id', $event->id)->update([
                'slug' => Str::slug($event->name),
                'code' => 'EVENT_'.$event->id,
            ]);
        });

        Schema::table('meet_sports', function (Blueprint $table) {
            $table->string('status', 30)->default('included')->after('sport_id');
            $table->text('description')->nullable()->after('status');
            $table->text('venue_notes')->nullable()->after('description');
            $table->unsignedSmallInteger('display_order')->default(0)->after('notes');
        });
    }

    public function down(): void
    {
        Schema::table('meet_sports', fn (Blueprint $table) => $table->dropColumn(['status', 'description', 'venue_notes', 'display_order']));
        Schema::table('events', function (Blueprint $table) {
            $table->dropIndex(['sport_category_id', 'slug']);
            $table->dropColumn(['code', 'slug', 'event_type', 'discipline', 'weight_class', 'distance', 'team_size', 'is_medal_event', 'display_order']);
        });
        Schema::table('sport_categories', function (Blueprint $table) {
            $table->dropIndex(['sport_id', 'slug']);
            $table->dropColumn(['name', 'slug', 'classification', 'competition_format', 'team_size', 'min_players', 'max_players', 'participation_notes', 'display_order']);
        });
        Schema::table('sports', function (Blueprint $table) {
            $table->dropUnique(['code']);
            $table->dropUnique(['slug']);
            $table->dropColumn(['code', 'slug', 'classification', 'icon_key', 'competition_format', 'is_team_sport', 'display_order']);
        });
    }
};
