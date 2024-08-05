<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateHelpOfferLinksTable extends Migration
{
    public function up(): void
    {
        Schema::create('help_offer_links', static function (Blueprint $table) {
            $table->bigIncrements('id');

            $table->foreignId('organization_id')
                ->constrained('organizations')
                ->cascadeOnDelete();
            $table->foreignId('user_id')
                ->constrained('users')
                ->cascadeOnDelete();
            $table->foreignId('help_offer_id')
                ->constrained('help_offers')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('help_offer_links');
    }
}
