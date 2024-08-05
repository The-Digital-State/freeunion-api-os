<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddTimestampToMemberLists extends Migration
{
    public function up(): void
    {
        Schema::table('member_lists', static function (Blueprint $table) {
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::table('member_lists', static function (Blueprint $table) {
            $table->dropTimestamps();
        });
    }
}
