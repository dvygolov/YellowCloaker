<?php
declare(strict_types=1);

namespace Offdev\Bandit;

class Lever
{
    private string $id;

    private int $tries;

    private int $rewards;

    public function __construct(string $id, int $tries = 0, int $rewards = 0)
    {
        $this->id = $id;
        $this->tries = $tries;
        $this->rewards = $rewards;
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getTries(): int
    {
        return $this->tries;
    }

    public function getRewards(): int
    {
        return $this->rewards;
    }

    public function getConversion(): float
    {
        return $this->tries > 0 ? $this->rewards / $this->tries : 0.0;
    }
}
