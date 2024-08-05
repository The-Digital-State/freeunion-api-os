<?php

declare(strict_types=1);

use App\Models\Material;
use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('materials', static function (Blueprint $table) {
            $table->foreignIdFor(User::class)->default(1)
                ->after('m_section_id')
                ->constrained()
                ->cascadeOnDelete();
        });

        DB::beginTransaction();

        foreach (Material::with('organization')->get() as $material) {
            $material->user_id = $material->organization->user_id;
            $material->save();
        }

        DB::commit();
    }

    public function down(): void
    {
        Schema::table('materials', static function (Blueprint $table) {
            $table->dropColumn(['user_id']);
        });
    }
};
