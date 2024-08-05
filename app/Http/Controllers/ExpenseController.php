<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Organization;
use Illuminate\Http\Resources\Json\JsonResource;

class ExpenseController extends Controller
{
    public function index(Organization $organization): JsonResource
    {
        if ($organization->expense === null) {
            return new JsonResource($organization->expense);
        }

        return new JsonResource($organization->expense->data);
    }
}
