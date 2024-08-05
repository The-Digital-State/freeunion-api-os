<?php

declare(strict_types=1);

namespace App\Http\Resources\SAdmin;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin User
 *
 * @property int $desk_tasks_count
 * @property int $news_count
 * @property int $suggestions_count
 */
class UserResource extends JsonResource
{
    /**
     * @param  Request  $request
     * @return array
     */
    public function toArray($request): array
    {
        static $day, $week, $month;

        if ($day === null) {
            $day = Carbon::now()->subDay();
            $week = Carbon::now()->subWeek();
            $month = Carbon::now()->subMonth();
        }

        $last = $this->last_action_at ?: $this->created_at;
        $last = $last ?: (new Carbon())->setYear(2000);

        return [
            'id' => $this->id,
            'has_referal' => $this->referal_id !== null,
            'is_admin' => $this->is_admin,
            'public_family' => $this->getPublicFamily(),
            'public_name' => $this->getPublicName(),
            'public_avatar' => $this->public_avatar,
            'is_verified' => $this->is_verified,
            'mfa_enabled' => $this->mfa !== null && $this->mfa->enabled->isNotEmpty(),
            'created_at' => $this->created_at,
            'active_period' => ($last->gt($day) ? 1 : 0) + ($last->gt($week) ? 1 : 0) + ($last->gt($month) ? 1 : 0),
            'stats' => [
                'desk_tasks' => $this->desk_tasks_count,
                'news' => $this->news_count,
                'suggestions' => $this->suggestions_count,
            ],
        ];
    }
}
