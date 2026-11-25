<?php

namespace App\Support;

final class CourseProgress
{
    public function __construct(public readonly array $cells)
    {
    }

    public function done(): int
    {
        return count(array_filter($this->cells));
    }

    public function total(): int
    {
        return count($this->cells);
    }

    public function percent(): int
    {
        return $this->total() === 0 ? 0 : intdiv($this->done() * 100, $this->total());
    }

    public function isComplete(): bool
    {
        return $this->total() > 0 && $this->done() === $this->total();
    }
}
