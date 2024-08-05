<?php

declare(strict_types=1);

namespace App\Models\Quiz;

use Illuminate\Support\Collection;

class OneOption extends AbstractSettings
{
    /**
     * @var Collection<int, string>
     */
    public Collection $answers;

    public int $rightAnswer;

    public static function canQuiz(): bool
    {
        return true;
    }

    public function calculatePoints(mixed $answer): int
    {
        return (int) ((int) $answer === $this->rightAnswer);
    }
}
