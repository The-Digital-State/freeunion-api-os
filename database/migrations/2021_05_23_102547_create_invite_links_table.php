<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateInviteLinksTable extends Migration
{
    public function up(): void
    {
        Schema::create('invite_links', static function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->foreignId('user_id')->constrained()
                ->cascadeOnDelete();
            $table->string('code', 64);
            $table->integer('invites')->nullable();
            $table->timestamp('limit')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invite_links');
    }
}
