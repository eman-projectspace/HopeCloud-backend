<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('donations', function (Blueprint $table) {
            $table->text('description')->nullable()->change();
            $table->string('condition')->nullable()->change();
            $table->string('location')->nullable()->change();
            $table->string('status')->default('submitted');
        });
    }

    public function down(): void
    {
        DB::table('donations')->whereNull('description')->update(['description' => '']);
        DB::table('donations')->whereNull('condition')->update(['condition' => '']);
        DB::table('donations')->whereNull('location')->update(['location' => '']);

        Schema::table('donations', function (Blueprint $table) {
            $table->dropColumn('status');
            $table->text('description')->change();
            $table->string('condition')->change();
            $table->string('location')->change();
        });
    }
};
