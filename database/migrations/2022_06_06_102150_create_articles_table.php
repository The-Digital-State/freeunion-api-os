<?php

declare(strict_types=1);

use App\Models\Organization;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateArticlesTable extends Migration
{
    public function up(): void
    {
        Schema::create('articles', static function (Blueprint $table) {
            $table->id();

            $table->foreignIdFor(Organization::class)
                ->constrained()
                ->cascadeOnDelete();
            $table->foreignId('a_section_id')->nullable()
                ->constrained()
                ->nullOnDelete();
            $table->string('title');
            $table->longText('content');
            $table->string('preview')->nullable();
            $table->unsignedTinyInteger('visible')->default(0);
            $table->boolean('published')->default(false);

            $table->timestamps();
            $table->timestamp('published_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('articles');
    }
}
