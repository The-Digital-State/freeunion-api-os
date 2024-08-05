<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateOrganizationTelepostsTable extends Migration
{
    public function up(): void
    {
        Schema::create('organization_teleposts', static function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()
                ->cascadeOnDelete();
            $table->string('name')->nullable();
            $table->string('channel');
            $table->string('verify_code', 64)->nullable();

            $table->unique(['verify_code']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('organization_teleposts');
    }
}
