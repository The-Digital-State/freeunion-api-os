<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateEnterRequestsTable extends Migration
{
    public function up(): void
    {
        Schema::create('enter_requests', static function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->foreignId('user_id')->constrained()
                ->cascadeOnDelete();
            $table->foreignId('organization_id')->constrained()
                ->cascadeOnDelete();
            $table->text('comment')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'organization_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('enter_requests');
    }
}
