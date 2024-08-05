<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateMemberListUserTable extends Migration
{
    public function up(): void
    {
        if (App::environment('production')) {
            DB::statement('SET SQL_REQUIRE_PRIMARY_KEY = OFF;');
        }

        Schema::create('member_list_user', static function (Blueprint $table) {
            $table->foreignId('member_list_id')->constrained()
                ->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()
                ->cascadeOnDelete();

            $table->primary(['member_list_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('member_list_user');
    }
}
