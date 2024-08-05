<?php

declare(strict_types=1);

use App\Models\Suggestion;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('suggestions', static function (Blueprint $table) {
            $table->boolean('is_closed')->default(false)
                ->after('rights_violation');
        });

        DB::beginTransaction();

        Suggestion::with('deskTask')->each(static function (Suggestion $suggestion) {
            if ($suggestion->deskTask !== null && $suggestion->deskTask->column_id !== 0) {
                $suggestion->forceFill(['is_closed' => true])->save();
            }
        });

        DB::commit();
    }

    public function down(): void
    {
        Schema::table('suggestions', static function (Blueprint $table) {
            $table->dropColumn(['is_closed']);
        });
    }
};
