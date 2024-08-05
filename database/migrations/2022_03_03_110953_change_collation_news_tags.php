<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ChangeCollationNewsTags extends Migration
{
    public function up(): void
    {
        Schema::table('', static function () {
            DB::statement('alter table news_tags convert to character set utf8mb4 collate utf8mb4_unicode_ci');
        });
    }

    public function down(): void
    {
        Schema::table('', static function () {
            // Nothing to do
        });
    }
}
