<?php

declare(strict_types=1);

namespace App\Http\Resources\SAdmin;

use App\Models\Organization;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Organization
 *
 * @property int $desk_tasks_count
 * @property int $members_count
 * @property int $members_admin_count
 * @property int $news_count
 * @property int $organization_teleposts_count
 * @property int $suggestions_count
 * @property int $fundraisings_count
 */
class OrganizationResource extends JsonResource
{
    /**
     * @param  Request  $request
     * @return array
     */
    public function toArray($request): array
    {
        $isShow = $this->public_status !== Organization::PUBLIC_STATUS_HIDDEN;
        $existExpense = $this->expense !== null && is_array($this->expense->data) && count($this->expense->data) > 0;
        $woFinance = $this->fundraisings_count === 0 && ! $existExpense;

        return [
            'id' => $this->id,
            'type_id' => $isShow ? $this->type_id : null,
            'type_name' => $isShow ? $this->organizationType->name ?? ($this->type_name ?? '') : null,
            'name' => $isShow ? $this->name : null,
            'short_name' => $this->short_name,
            'is_verified' => $this->is_verified,
            'created_at' => $this->created_at,
            'deleted_at' => $this->deleted_at,
            'stats' => [
                'members' => $this->members_count,
                'admins' => $this->members_admin_count,
                'desk_tasks' => $this->desk_tasks_count,
                'news' => $this->news_count,
                'suggestions' => $this->suggestions_count,
                'organization_teleposts' => $this->organization_teleposts_count,
                'used_finance' => ! $woFinance,
            ],
        ];
    }
}
