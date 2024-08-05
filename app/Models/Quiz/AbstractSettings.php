<?php

declare(strict_types=1);

namespace App\Models\Quiz;

abstract class AbstractSettings
{
    public static function canQuiz(): bool
    {
        return false;
    }

    public function calculatePoints(mixed $answer): int
    {
        return 0;
    }
}
