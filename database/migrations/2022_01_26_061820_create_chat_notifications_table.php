<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Schema;

class CreateChatNotificationsTable extends Migration
{
    public function up(): void
    {
        if (App::environment('production')) {
            DB::statement('SET SQL_REQUIRE_PRIMARY_KEY = OFF;');
        }

        Schema::create('chat_notifications', static function (Blueprint $table) {
            $table->foreignId('chat_message_id')->constrained()
                ->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()
                ->cascadeOnDelete();
            $table->foreignId('organization_id')->nullable()->constrained()
                ->cascadeOnDelete();
            $table->boolean('is_seen')->default(false);
            $table->timestamps();

            $table->primary(['chat_message_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('chat_notifications');
    }
}
