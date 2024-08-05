<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddPreviewsToDocTemplates extends Migration
{
    public function up(): void
    {
        Schema::table('doc_templates', static function (Blueprint $table) {
            $table->text('previews')->nullable()
                ->after('fields');
        });
    }

    public function down(): void
    {
        Schema::table('doc_templates', static function (Blueprint $table) {
            $table->dropColumn(['previews']);
        });
    }
}
