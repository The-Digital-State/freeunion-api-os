<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateDocTemplatesTable extends Migration
{
    public function up(): void
    {
        Schema::create('doc_templates', static function (Blueprint $table) {
            $table->bigIncrements('id');

            $table->string('name')->nullable();
            $table->foreignId('organization_id')->nullable()->constrained()
                ->cascadeOnDelete();
            $table->string('template');
            $table->text('fields')->nullable();

            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('doc_templates');
    }
}
