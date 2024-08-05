<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateBannersTable extends Migration
{
    public function up(): void
    {
        Schema::create('banners', static function (Blueprint $table) {
            $table->bigIncrements('id');

            $table->foreignId('organization_id')->nullable()->constrained()
                ->cascadeOnDelete();
            $table->string('large')->nullable();
            $table->string('small')->nullable();
            $table->tinyInteger('index')->default(1);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('banners');
    }
}
