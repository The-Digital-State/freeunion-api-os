<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateInterestScopesTable extends Migration
{
    public function up(): void
    {
        Schema::create('interest_scopes', static function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('name');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('interest_scopes');
    }
}
