<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Expense\UpdateRequest;
use App\Models\Organization;
use App\Policies\OrganizationPolicy;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\Resources\Json\JsonResource;

class ExpenseController extends Controller
{
    /**
     * @throws AuthorizationException
     */
    public function index(Organization $organization): JsonResource
    {
        $this->authorize(OrganizationPolicy::FINANCE_MANAGE, $organization);

        if ($organization->expense === null) {
            return new JsonResource($organization->expense);
        }

        return new JsonResource($organization->expense->data);
    }

    /**
     * @throws AuthorizationException
     */
    public function update(UpdateRequest $request, Organization $organization): JsonResource
    {
        $this->authorize(OrganizationPolicy::FINANCE_MANAGE, $organization);

        $expense = $organization->expense ?? $organization->expense()->create();
        $expense->data = $request->validated();
        $expense->save();

        return new JsonResource($expense->data);
    }
}
