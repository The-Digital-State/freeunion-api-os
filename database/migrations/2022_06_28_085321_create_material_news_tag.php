<?php

declare(strict_types=1);

use App\Models\Material;
use App\Models\NewsTag;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (App::environment('production')) {
            DB::statement('SET SQL_REQUIRE_PRIMARY_KEY = OFF;');
        }

        Schema::create('material_news_tag', static function (Blueprint $table) {
            $table->foreignIdFor(Material::class)->constrained()
                ->cascadeOnDelete();
            $table->foreignIdFor(NewsTag::class)->constrained()
                ->cascadeOnDelete();

            $table->primary(['material_id', 'news_tag_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('material_news_tag');
    }
};
