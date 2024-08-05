<?php

declare(strict_types=1);

use App\Models\LibraryItem;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('library_links', static function (Blueprint $table) {
            $table->id();

            $table->morphs('model');
            $table->foreignIdFor(LibraryItem::class);
            $table->string('collection_name');
            $table->unsignedInteger('order')->index();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('library_links');
    }
};
