<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\InviteLink\StoreRequest;
use App\Http\Resources\InviteLinkResource;
use App\Http\Resources\OrganizationShortResource;
use App\Http\Resources\UserResource;
use App\Http\Response;
use App\Models\InviteLink;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class InviteLinkController extends Controller
{
    public function generate(StoreRequest $request): JsonResource|JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $links = $user->inviteLinks()->withoutGlobalScope(new SoftDeletingScope())
            ->orderByDesc('created_at')->limit(InviteLink::MAX_LINKS)->get();
        $last = $links->last();
        $canCreate = $links->count() !== InviteLink::MAX_LINKS || $last === null || $last->isExpired();

        if (! $canCreate) {
            return Response::error(__('errors.invite_link_max'));
        }

        $link = $user->inviteLinks()->make();
        $link->organization_id = $request->get('organization');
        $link->save();

        return new InviteLinkResource($link);
    }

    public function show(Request $request): JsonResponse
    {
        $link = InviteLink::withTrashed()->where('id', $request->get('id', 0))->first();

        if (! $link || $link->code !== $request->get('code')) {
            throw new ModelNotFoundException();
        }

        return Response::success(
            [
                'data' => [
                    'user' => new UserResource($link->user),
                    'organization' => $link->organization === null ? null
                        : new OrganizationShortResource($link->organization),
                    'is_member' => $link->organization?->members->pluck('id')->contains($link->user_id),
                ],
            ]
        );
    }
}
