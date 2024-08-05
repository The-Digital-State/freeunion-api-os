<?php

declare(strict_types=1);

namespace App\Models\Quiz;

use Illuminate\Support\Collection;

class MultipleOptions extends AbstractSettings
{
    /**
     * @var Collection<int, string>
     */
    public Collection $answers;

    /**
     * @var Collection<int, int>
     */
    public Collection $rightAnswer;

    public static function canQuiz(): bool
    {
        return true;
    }

    public function calculatePoints(mixed $answer): int
    {
        $answerCollection = new Collection();

        foreach (is_array($answer) ? $answer : [$answer] as $item) {
            $answerCollection->add((int) $item);
        }

        $points = $answerCollection->intersect($this->rightAnswer)->count()
            - $answerCollection->diff($this->rightAnswer)->count();

        return max($points, 0);
    }
}
