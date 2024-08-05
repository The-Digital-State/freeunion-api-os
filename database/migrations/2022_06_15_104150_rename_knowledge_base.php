<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

class RenameKnowledgeBase extends Migration
{
    public function up(): void
    {
        Schema::drop('articles');
        Schema::drop('a_sections');
    }

    public function down(): void
    {
        // Nothing
    }
}
