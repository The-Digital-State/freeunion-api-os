<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddFieldsToSuggestions extends Migration
{
    public function up(): void
    {
        Schema::table('suggestions', static function (Blueprint $table) {
            $table->text('solution')->nullable()
                ->after('description');
            $table->text('goal')->nullable()
                ->after('solution');
            $table->text('urgency')->nullable()
                ->after('goal');
            $table->text('budget')->nullable()
                ->after('urgency');
            $table->text('legal_aid')->nullable()
                ->after('budget');
            $table->text('rights_violation')->nullable()
                ->after('legal_aid');
        });
    }

    public function down(): void
    {
        Schema::table('suggestions', static function (Blueprint $table) {
            $table->dropColumn(['solution', 'goal', 'urgency', 'budget', 'legal_aid', 'rights_violation']);
        });
    }
}
