<?php

declare(strict_types=1);

use App\Models\Suggestion;
use App\Models\User;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        if (method_exists(Suggestion::class, 'voted')) {
            DB::beginTransaction();

            Suggestion::each(static function (Suggestion $suggestion) {
                /** @phpstan-ignore-next-line */
                $suggestion->voted()->each(fn (User $user) => $suggestion->setReaction($user, 0));
            });

            DB::commit();
        }
    }

    public function down(): void
    {
        // Nothing
    }
};
