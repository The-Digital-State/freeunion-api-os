<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateFundraisingsTable extends Migration
{
    public function up(): void
    {
        Schema::create('fundraisings', static function (Blueprint $table) {
            $table->bigIncrements('id');

            $table->foreignId('organization_id')->constrained()
                ->cascadeOnDelete();
            $table->string('title');
            $table->string('description')->nullable();
            $table->string('image')->nullable();
            $table->integer('ammount')->nullable();
            $table->string('currency');
            $table->boolean('is_subscription')->default(false);
            $table->date('date_end')->nullable();
            $table->string('payment_system');
            $table->string('payment_link');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::dropIfExists('fundraisings');
    }
}
