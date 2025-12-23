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
        Schema::create('articles', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('content')->nullable();
            $table->text('full_content')->nullable();
            $table->string('link')->nullable();
            $table->dateTime('date')->nullable();
            $table->string('source_url')->nullable();
            $table->boolean('is_updated')->default(false);
            $table->unsignedBigInteger('original_article_id')->nullable();
            $table->json('reference_articles')->nullable(); // Store citations/references
            $table->timestamps();

            $table->foreign('original_article_id')->references('id')->on('articles')->onDelete('cascade');
            $table->index('is_updated');
            $table->index('date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('articles');
    }
};

