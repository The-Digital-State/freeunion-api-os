<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateActivityScopesTable extends Migration
{
    public function up(): void
    {
        Schema::create('activity_scopes', static function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('name');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('activity_scopes');
    }
}
