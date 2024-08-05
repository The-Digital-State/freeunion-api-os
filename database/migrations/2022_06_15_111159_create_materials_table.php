<?php

declare(strict_types=1);

use App\Models\MSection;
use App\Models\Organization;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateMaterialsTable extends Migration
{
    public function up(): void
    {
        Schema::create('materials', static function (Blueprint $table) {
            $table->id();

            $table->foreignIdFor(Organization::class)
                ->constrained()
                ->cascadeOnDelete();
            $table->foreignIdFor(MSection::class)
                ->constrained()
                ->cascadeOnDelete();
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
        Schema::dropIfExists('materials');
    }
}
