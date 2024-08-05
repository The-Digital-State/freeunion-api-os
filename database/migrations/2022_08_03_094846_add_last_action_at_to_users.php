<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', static function (Blueprint $table) {
            $table->timestamp('last_action_at')->nullable()
                ->after('settings');
        });

        DB::beginTransaction();

        User::all()->each(static function (User $user) {
            $user->timestamps = false;
            $user->last_action_at = $user->updated_at;
            $user->save();
        });

        DB::commit();
    }

    public function down(): void
    {
        Schema::table('users', static function (Blueprint $table) {
            $table->dropColumn(['last_action_at']);
        });
    }
};
