<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('library_items', static function (Blueprint $table) {
            $table->id();

            $table->uuid()->unique();
            $table->string('file_name')->nullable();
            $table->string('mime_type')->nullable();
            $table->string('name');
            $table->unsignedInteger('size');

            $table->timestamp('created_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('library_items');
    }
};
