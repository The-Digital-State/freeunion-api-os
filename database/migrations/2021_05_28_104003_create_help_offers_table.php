<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateHelpOffersTable extends Migration
{
    public function up(): void
    {
        Schema::create('help_offers', static function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('text');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('help_offers');
    }
}
