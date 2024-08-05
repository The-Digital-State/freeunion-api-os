<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Schema;

class CreatePivotTableFundraisingPaymentSystem extends Migration
{
    public function up(): void
    {
        if (App::environment('production')) {
            DB::statement('SET SQL_REQUIRE_PRIMARY_KEY = OFF;');
        }

        Schema::create('fundraising_payment_system', static function (Blueprint $table) {
            $table->foreignId('fundraising_id')->constrained()
                ->cascadeOnDelete();
            $table->foreignId('payment_system_id')->constrained()
                ->cascadeOnDelete();
            $table->string('product_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('fundraising_payment_system');
    }
}
