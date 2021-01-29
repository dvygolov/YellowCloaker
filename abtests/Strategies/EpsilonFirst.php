<?php
declare(strict_types=1);

namespace Offdev\Bandit\Strategies;

use Offdev\Bandit\Exceptions\RuntimeException;
use Offdev\Bandit\Lever;
use Offdev\Bandit\Machine;
use Offdev\Bandit\StrategyInterface;

/**
 * Represents the epsilon-first strategy to solve the multi armed bandit problem.
 *
 * From wikipedia (2016-08-13):
 * A pure exploration phase is followed by a pure exploitation phase. For N trials
 * in total, the exploration phase occupies eN trials and the exploitation phase
 * 1-eN trials. During the exploration phase, a lever is randomly selected (with
 * uniform probability); during the exploitation phase, the best lever is always
 * selected.
 *
 * @url https://en.wikipedia.org/wiki/Multi-armed_bandit#Bandit_strategies
 */
class EpsilonFirst implements StrategyInterface
{
    private float $e;

    private int $maxTries;

    public function __construct(int $maxTries, float $proportion = 0.1)
    {
        if ($maxTries <= 0) {
            throw new RuntimeException('Amount of total maximum tries must be greater than 0!');
        }

        if ($proportion < 0.0) {
            throw new RuntimeException('Proportion must be greater than or equal to 0!');
        }

        if ($proportion > 1.0) {
            throw new RuntimeException('Proportion must be less than or equal to 1!');
        }

        $this->e = $proportion;
        $this->maxTries = $maxTries;
    }

    public function solve(Machine $machine): Lever
    {
        $tries = 0;
        $leverList = $machine->getLeverList();
        foreach ($leverList as $lever) {
            $tries += $lever->getTries();
        }

        $r = $tries / $this->maxTries;
        if ($r <= $this->e) {
            return $machine->getRandomLever();
        }

        return $machine->getBestLever();
    }
}
