<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddOrgLinkToHelpOffers extends Migration
{
    public function up(): void
    {
        Schema::table('help_offers', static function (Blueprint $table) {
            $table->foreignId('organization_id')->nullable()->constrained()
                ->cascadeOnDelete();
            $table->boolean('enabled')->default(true);
        });
    }

    public function down(): void
    {
        Schema::table('help_offers', static function (Blueprint $table) {
            $table->dropConstrainedForeignId('organization_id');
            $table->dropColumn(['enabled']);
        });
    }
}
