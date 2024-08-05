<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateMemberListsTable extends Migration
{
    public function up(): void
    {
        Schema::create('member_lists', static function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->foreignId('organization_id')->constrained()
                ->cascadeOnDelete();
            $table->string('name');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('member_lists');
    }
}
