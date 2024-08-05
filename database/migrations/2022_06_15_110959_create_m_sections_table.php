<?php

declare(strict_types=1);

use App\Models\Organization;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateMSectionsTable extends Migration
{
    public function up(): void
    {
        Schema::create('m_sections', static function (Blueprint $table) {
            $table->id();

            $table->foreignIdFor(Organization::class)
                ->constrained()
                ->cascadeOnDelete();
            $table->string('name');
            $table->string('description')->default('');
            $table->string('cover')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('m_sections');
    }
}
