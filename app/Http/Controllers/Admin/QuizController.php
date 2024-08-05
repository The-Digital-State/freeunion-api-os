<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Quiz\StoreRequest;
use App\Http\Requests\Admin\Quiz\UpdateRequest;
use App\Http\Resources\QuizFullResource;
use App\Http\Resources\QuizListResource;
use App\Http\Response;
use App\Models\Organization;
use App\Models\Quiz;
use App\Models\User;
use App\Policies\OrganizationPolicy;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Throwable;

class QuizController extends Controller
{
    public const FILTERS = [
        'id',
        'published',
        'created_at',
        'updated_at',
        'published_at',
    ];

    public function index(Request $request, Organization $organization): JsonResource
    {
        $query = $organization->quizzes();

        self::filterQuery($query, $request->only(self::FILTERS));

        if (($request->input('is_active') !== null)) {
            $query->active((bool) $request->input('is_active'));
        }

        if (($request->input('is_closed') !== null)) {
            $query->closed((bool) $request->input('is_closed'));
        }

        $sortBy = $request->get('sortBy', 'id');
        $sortDirection = $request->get('sortDirection', 'asc');

        if (in_array($sortBy, self::FILTERS, true)) {
            $query->orderBy($sortBy, $sortDirection);
        }

        $limit = (int) $request->get('limit', 0);

        if ($limit > 0) {
            return QuizListResource::collection($query->paginate($limit));
        }

        return QuizListResource::collection($query->get());
    }

    /**
     * @throws AuthorizationException
     */
    public function store(StoreRequest $request, Organization $organization): JsonResource
    {
        /** @var User $user */
        $user = $request->user();

        $this->authorize(OrganizationPolicy::QUIZ_STORE, $organization);

        /** @var Quiz $quiz */
        $quiz = $organization->quizzes()->make($request->validated());
        $quiz->organization_id = $organization->id;
        $quiz->user_id = $user->id;
        $quiz->published = false;
        $quiz->save();

        $quiz->updateMedia($request->input('images', []));

        return new QuizFullResource($quiz);
    }

    /**
     * @throws AuthorizationException
     */
    public function show(Organization $organization, Quiz $quiz): JsonResource
    {
        $this->checkInOrganization($organization, $quiz);

        if (! $quiz->published) {
            $this->authorize(OrganizationPolicy::QUIZ_VIEW_UNPUBLISH, $organization);
        }

        return new QuizFullResource($quiz);
    }

    /**
     * @throws AuthorizationException
     */
    public function update(UpdateRequest $request, Organization $organization, Quiz $quiz): JsonResource|JsonResponse
    {
        $this->authorize(OrganizationPolicy::QUIZ_UPDATE, $organization);
        $this->checkInOrganization($organization, $quiz);

        if ($quiz->published) {
            return Response::error(['Quiz already published']);
        }

        $quiz->fill($request->validated());
        $quiz->save();

        if ($request->input('images') !== null) {
            $quiz->updateMedia($request->input('images', []));
        }

        return new QuizFullResource($quiz);
    }

    /**
     * @throws AuthorizationException
     */
    public function publish(UpdateRequest $request, Organization $organization, Quiz $quiz): JsonResource
    {
        $this->authorize(OrganizationPolicy::QUIZ_PUBLISH, $organization);
        $this->checkInOrganization($organization, $quiz);

        try {
            $this->update($request, $organization, $quiz);
        } catch (Throwable) {
        }

        $quiz->published = true;
        $quiz->save();

        return new QuizFullResource($quiz);
    }

    /**
     * @throws AuthorizationException
     */
    public function close(Organization $organization, Quiz $quiz): JsonResource
    {
        $this->authorize(OrganizationPolicy::QUIZ_UPDATE, $organization);
        $this->checkInOrganization($organization, $quiz);

        $quiz->date_end = now()->startOfDay();
        $quiz->save();

        return new QuizFullResource($quiz);
    }

    /**
     * @throws AuthorizationException
     */
    public function destroy(Organization $organization, Quiz $quiz): JsonResponse
    {
        $this->authorize(OrganizationPolicy::QUIZ_DESTROY, $organization);
        $this->checkInOrganization($organization, $quiz);

        $quiz->delete();

        return Response::noContent();
    }

    private function checkInOrganization(Organization $organization, Quiz $quiz): void
    {
        if ($organization->id !== $quiz->organization_id) {
            throw new ModelNotFoundException();
        }
    }
}
