<?php

declare(strict_types=1);

use App\Models\ChatConversation;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Migrations\Migration;

class CreateChatsWithSupport extends Migration
{
    public function up(): void
    {
        $supportID = config('app.organizations.support');

        if ($supportID === null) {
            return;
        }

        /** @var Organization|null $organization */
        $organization = Organization::find($supportID);

        if ($organization === null) {
            return;
        }

        $supportUser = $organization->members()->first();

        if ($supportUser === null) {
            return;
        }

        $query = ChatConversation::query()->where('is_direct', true);
        $query->whereHas('chatParticipants', static function (Builder $q) use ($supportID) {
            $q->where('organization_id', $supportID);
        });
        $query->each(static function (ChatConversation $chatConversation) {
            $chatConversation->delete();
        });

        User::query()->each(static function (User $user) use ($supportID, $supportUser) {
            $conversation = new ChatConversation();
            $conversation->is_direct = true;
            $conversation->save();

            $participantSupport = $conversation->chatParticipants()->make();
            $participantSupport->user_id = $supportUser->id;
            $participantSupport->organization_id = $supportID;
            $participantSupport->save();

            $participant = $conversation->chatParticipants()->make();
            $participant->user_id = $user->id;
            $participant->organization_id = null;
            $participant->save();
        });
    }

    public function down(): void
    {
        // Nothing
    }
}
