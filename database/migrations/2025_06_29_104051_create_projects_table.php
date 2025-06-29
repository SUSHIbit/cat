<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('projects', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('original_filename');
            $table->string('file_path');
            $table->string('file_type'); // docx, pdf, pptx
            $table->integer('file_size');
            $table->enum('status', [
                'uploaded',
                'extracting_text',
                'converting_to_cat',
                'formatting',
                'generating_pdf',
                'completed',
                'failed'
            ])->default('uploaded');
            $table->text('extracted_text')->nullable();
            $table->longText('cat_narrative')->nullable();
            $table->text('formatted_narrative')->nullable();
            $table->string('pdf_path')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('projects');
    }
};