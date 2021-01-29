<?php
declare(strict_types=1);

namespace Offdev\Bandit;

/**
 * The Strategy interface defines a way to solve a multi
 * armed bandit problem. For more information about
 * existing strategies, please refer to following link:
 *
 * https://en.wikipedia.org/wiki/Multi-armed_bandit#Bandit_strategies
 */
interface StrategyInterface
{
    /**
     * Solves the puzzle, and returns the winning lever.
     */
    public function solve(Machine $machine): Lever;
}
