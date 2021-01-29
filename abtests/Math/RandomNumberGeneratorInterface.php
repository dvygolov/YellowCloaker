<?php
declare(strict_types=1);

namespace Offdev\Bandit\Math;

interface RandomNumberGeneratorInterface
{
    public function integer(int $min = 0, int $max = PHP_INT_MAX): int;

    public function float(float $min = 0.0, float $max = 1.0): float;
}
