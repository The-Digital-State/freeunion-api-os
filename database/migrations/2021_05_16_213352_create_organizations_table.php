<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateOrganizationsTable extends Migration
{
    public function up(): void
    {
        Schema::create('organizations', static function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedSmallInteger('type');
            $table->text('name');
            $table->string('avatar')->nullable();
            $table->text('short_description');

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('organizations');
    }
}
