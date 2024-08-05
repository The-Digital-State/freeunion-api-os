<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\QuizQuestion\StoreRequest;
use App\Http\Requests\Admin\QuizQuestion\UpdateRequest;
use App\Http\Resources\QuizQuestionFullResource;
use App\Http\Resources\QuizQuestionListResource;
use App\Http\Response;
use App\Models\Organization;
use App\Models\Quiz;
use App\Models\QuizQuestion;
use App\Policies\OrganizationPolicy;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\DB;

class QuizQuestionController extends Controller
{
    public const FILTERS = [
        'id',
        'index',
    ];

    public function index(Request $request, Organization $organization, Quiz $quiz): JsonResource
    {
        $this->checkInOrganization($organization, $quiz);

        $query = $quiz->quizQuestions();

        self::filterQuery($query, $request->only(self::FILTERS));

        $sortBy = $request->get('sortBy', 'index');
        $sortDirection = $request->get('sortDirection', 'asc');

        if (in_array($sortBy, self::FILTERS, true)) {
            $query->orderBy($sortBy, $sortDirection);
        }

        $limit = (int) $request->get('limit', 0);

        if ($limit > 0) {
            return QuizQuestionListResource::collection($query->paginate($limit));
        }

        return QuizQuestionListResource::collection($query->get());
    }

    /**
     * @throws AuthorizationException
     */
    public function store(StoreRequest $request, Organization $organization, Quiz $quiz): JsonResource|JsonResponse
    {
        $this->checkInOrganization($organization, $quiz);

        $this->authorize(OrganizationPolicy::QUIZ_UPDATE, $organization);

        if ($quiz->published) {
            return Response::error(['Quiz already published']);
        }

        /** @var QuizQuestion $question */
        $question = $quiz->quizQuestions()->make($request->validated());
        $question->index = $this->getLastIndex($quiz);
        $question->save();

        $question->updateMedia([$request->input('image') ?? '']);

        return new QuizQuestionFullResource($question);
    }

    /**
     * @throws AuthorizationException
     */
    public function show(Organization $organization, Quiz $quiz, QuizQuestion $question): JsonResource
    {
        $this->checkInOrganization($organization, $quiz);
        $this->checkInQuiz($quiz, $question);

        if (! $quiz->published) {
            $this->authorize(OrganizationPolicy::QUIZ_VIEW_UNPUBLISH, $organization);
        }

        return new QuizQuestionFullResource($question);
    }

    /**
     * @throws AuthorizationException
     */
    public function update(
        UpdateRequest $request,
        Organization $organization,
        Quiz $quiz,
        QuizQuestion $question,
    ): JsonResource|JsonResponse {
        $this->checkInOrganization($organization, $quiz);
        $this->checkInQuiz($quiz, $question);

        $this->authorize(OrganizationPolicy::QUIZ_UPDATE, $organization);

        if ($quiz->published) {
            return Response::error(['Quiz already published']);
        }

        $question->fill($request->validated());
        $question->save();

        if ($request->input('image') !== null) {
            $question->updateMedia([$request->input('image')]);
        }

        return new QuizQuestionFullResource($question);
    }

    /**
     * @throws AuthorizationException
     */
    public function drag(Organization $organization, Quiz $quiz, QuizQuestion $question, int $after): JsonResource
    {
        $this->checkInOrganization($organization, $quiz);
        $this->checkInQuiz($quiz, $question);

        $this->authorize(OrganizationPolicy::QUIZ_UPDATE, $organization);

        $this->moveAfter($quiz, $question, $after);

        return new QuizQuestionFullResource($question);
    }

    /**
     * @throws AuthorizationException
     */
    public function destroy(Organization $organization, Quiz $quiz, QuizQuestion $question): JsonResponse
    {
        $this->checkInOrganization($organization, $quiz);
        $this->checkInQuiz($quiz, $question);

        $this->authorize(OrganizationPolicy::QUIZ_UPDATE, $organization);

        $question->delete();

        return Response::noContent();
    }

    private function checkInOrganization(Organization $organization, Quiz $quiz): void
    {
        if ($organization->id !== $quiz->organization_id) {
            throw new ModelNotFoundException();
        }
    }

    private function checkInQuiz(Quiz $quiz, QuizQuestion $question): void
    {
        if ($quiz->id !== $question->quiz_id) {
            throw new ModelNotFoundException();
        }
    }

    private function getLastIndex(Quiz $quiz): int
    {
        /** @var ?int $lastIndex */
        $lastIndex = $quiz->quizQuestions()
            ->orderBy('index', 'desc')->first()?->index;

        return $lastIndex !== null ? $lastIndex + 1 : 0;
    }

    private function moveAfter(Quiz $quiz, QuizQuestion $question, int $after): void
    {
        DB::beginTransaction();

        $after = max($after, -1);

        $counter = $after + 2;

        $quiz->quizQuestions()->where('index', '>', $after)
            ->orderBy('index')->get()
            ->each(static function (QuizQuestion $question) use (&$counter) {
                $question->index = $counter++;
                $question->save();
            });

        $question->index = $after + 1;
        $question->save();

        DB::commit();
    }
}
