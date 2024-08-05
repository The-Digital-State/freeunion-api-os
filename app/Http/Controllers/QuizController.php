<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Resources\QuizQuestionResource;
use App\Http\Resources\QuizResource;
use App\Http\Resources\QuizShortResource;
use App\Http\Response;
use App\Models\Organization;
use App\Models\Quiz;
use App\Models\QuizQuestion;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class QuizController extends Controller
{
    public const FILTERS = [
        'published_at',
    ];

    public function index(Request $request, Organization $organization): JsonResource
    {
        /** @var User $user */
        $user = $request->user();

        if (! $user->membership->pluck('id')->contains($organization->id)) {
            throw new ModelNotFoundException();
        }

        $query = $organization->quizzes();
        $query->withCount([
            'quizQuestions',
            'quizQuestions as quiz_questions_answered_count' => static function (Builder $query) use ($user) {
                $query->whereHas('quizAnswers', static function (Builder $query) use ($user) {
                    $query->where('user_id', $user->id);
                });
            },
        ]);

        self::filterQuery($query, $request->only(self::FILTERS));

        $isClosed = $request->get('is_closed', false);

        if ($isClosed) {
            $query->closed();
        } else {
            $query->active();
        }

        $sortBy = $request->get('sortBy', 'published_at');
        $sortDirection = $request->get('sortDirection', 'asc');

        if (in_array($sortBy, self::FILTERS, true)) {
            $query->orderBy($sortBy, $sortDirection);
        }

        $limit = (int) $request->get('limit', 0);

        if ($limit > 0) {
            return QuizShortResource::collection($query->paginate($limit));
        }

        return QuizShortResource::collection($query->get());
    }

    public function show(Request $request, Organization $organization, Quiz $quiz): JsonResource
    {
        /** @var User $user */
        $user = $request->user();

        if (! $quiz->published || ! $user->membership->pluck('id')->contains($organization->id)) {
            throw new ModelNotFoundException();
        }

        return new QuizResource($quiz);
    }

    public function question(Request $request, Organization $organization, Quiz $quiz): JsonResource|JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        if (! $quiz->isActive() || ! $user->membership->pluck('id')->contains($organization->id)) {
            throw new ModelNotFoundException();
        }

        $question = $this->getQuestion($quiz, $user);

        if ($question) {
            $answer = $request->input('answer');

            if ($answer !== null) {
                $settings = $question->settings ?? [];

                switch ($question->type) {
                    case QuizQuestion::TYPE_ONE_ANSWER:
                        $answer = (int) $answer;

                        /** @phpstan-ignore-next-line */
                        if ($answer < 0 || $answer >= count($settings['answers'])) {
                            return Response::error(['Wrong answer']);
                        }

                        $question->quizAnswers()->create([
                            'user_id' => $user->id,
                            'answer' => $answer,
                        ]);

                        break;

                    case QuizQuestion::TYPE_MULTIPLE_ANSWERS:
                        if (! is_array($answer)) {
                            return Response::error(['Wrong answer']);
                        }

                        $answer = array_filter($answer,
                            /** @phpstan-ignore-next-line */
                            static fn ($item) => $item >= 0 && $item < count($settings['answers']));

                        if (count($answer) === 0) {
                            return Response::error(['Wrong answer']);
                        }

                        $question->quizAnswers()->create([
                            'user_id' => $user->id,
                            'answer' => implode(',', $answer),
                        ]);

                        break;

                    case QuizQuestion::TYPE_TEXT:
                        $question->quizAnswers()->create([
                            'user_id' => $user->id,
                            'answer' => (string) $answer,
                        ]);

                        break;

                    case QuizQuestion::TYPE_SCALE:
                        $answer = (int) $answer;

                        if ($answer < $settings['min_value'] || $answer > $settings['max_value']) {
                            return Response::error(['Wrong answer']);
                        }

                        $question->quizAnswers()->create([
                            'user_id' => $user->id,
                            'answer' => $answer,
                        ]);

                        break;
                }

                $question = $this->getQuestion($quiz, $user);
            }

            if ($question) {
                return (new QuizQuestionResource($question))
                    ->additional([
                        'meta' => [
                            /** @phpstan-ignore-next-line */
                            'current' => $quiz->quizQuestions
                                    ->search(static fn ($item) => $item->id === $question->id) + 1,
                            'total' => $quiz->quizQuestions->count(),
                        ],
                    ]);
            }
        }

        return Response::success();
    }

    private function getQuestion(Quiz $quiz, User $user): ?QuizQuestion
    {
        return $quiz->quizQuestions()
            ->whereDoesntHave('quizAnswers', static function (Builder $query) use ($user) {
                $query->where('user_id', $user->id);
            })
            ->first();
    }
}
