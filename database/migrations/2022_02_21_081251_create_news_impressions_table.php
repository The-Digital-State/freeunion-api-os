<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateNewsImpressionsTable extends Migration
{
    public function up(): void
    {
        Schema::create('news_impressions', static function (Blueprint $table) {
            $table->bigIncrements('id');

            $table->foreignId('news_id')->constrained()
                ->onDelete('cascade');
            $table->foreignId('user_id')->nullable()->constrained()
                ->onDelete('set null');
            $table->string('ip', 45);

            $table->index(['news_id', 'user_id']);
            $table->index(['news_id', 'ip']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('news_impressions');
    }
}
