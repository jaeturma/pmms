<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('sports')->where('name', 'Bocce')->update(['name' => 'Paragames - Boccee']);
        DB::table('sports')->where('name', 'Goal Ball')->update(['name' => 'Paragames - Goal Ball']);
    }

    public function down(): void
    {
        DB::table('sports')->where('name', 'Paragames - Boccee')->update(['name' => 'Bocce']);
        DB::table('sports')->where('name', 'Paragames - Goal Ball')->update(['name' => 'Goal Ball']);
    }
};
