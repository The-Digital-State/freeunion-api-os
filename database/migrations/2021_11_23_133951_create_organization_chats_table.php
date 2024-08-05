<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateOrganizationChatsTable extends Migration
{
    public function up(): void
    {
        Schema::create('organization_chats', static function (Blueprint $table) {
            $table->bigIncrements('id');

            $table->foreignId('organization_id')->constrained()
                ->cascadeOnDelete();
            $table->tinyInteger('type')->default(0);
            $table->string('value')->default('');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('organization_chats');
    }
}
