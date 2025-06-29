<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            // Update the enum to include the new status
            $table->enum('status', [
                'uploaded',
                'extracting_text',
                'text_extracted',
                'converting_to_cat',
                'formatting',
                'generating_pdf',
                'completed',
                'failed'
            ])->default('uploaded')->change();
        });
    }

    public function down(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            // Revert to original enum values
            $table->enum('status', [
                'uploaded',
                'extracting_text',
                'converting_to_cat',
                'formatting',
                'generating_pdf',
                'completed',
                'failed'
            ])->default('uploaded')->change();
        });
    }
};