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
    Schema::table('donations', function (Blueprint $table) {
        $table->string('title');
        $table->text('description');
        $table->string('category');
        $table->string('condition');
        $table->string('location');
        $table->string('image')->nullable();
    });
}

    /**
     * Reverse the migrations.
     */
   public function down(): void
{
    Schema::table('donations', function (Blueprint $table) {
        $table->dropColumn([
            'title',
            'description',
            'category',
            'condition',
            'location',
            'image',
        ]);
    });
}
};
