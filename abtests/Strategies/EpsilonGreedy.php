<?php
declare(strict_types=1);

namespace Offdev\Bandit\Strategies;

use Offdev\Bandit\Exceptions\RuntimeException;
use Offdev\Bandit\Lever;
use Offdev\Bandit\Machine;
use Offdev\Bandit\Math\MersenneTwister;
use Offdev\Bandit\Math\RandomNumberGeneratorInterface;
use Offdev\Bandit\StrategyInterface;

/**
 * Represents the epsilon-greedy strategy to solve the multi armed bandit problem.
 *
 * From wikipedia (2016-08-13):
 * The best lever is selected for a proportion 1-e of the trials, and a lever is selected at
 * random (with uniform probability) for a proportion e. A typical parameter value might
 * be e=0.1, but this can vary widely depending on circumstances and predilections.
 *
 * @url https://en.wikipedia.org/wiki/Multi-armed_bandit#Bandit_strategies
 */
class EpsilonGreedy implements StrategyInterface
{
    private RandomNumberGeneratorInterface $rng;

    private float $e;

    public function __construct(float $uniformProbability = 0.1, ?RandomNumberGeneratorInterface $rng = null)
    {
        if ($uniformProbability < 0.0) {
            throw new RuntimeException('Probability must be greater than or equal to 0!');
        }

        if ($uniformProbability > 1.0) {
            throw new RuntimeException('Probability must be less than or equal to 1!');
        }

        $this->rng = $rng ?? new MersenneTwister();
        $this->e = $uniformProbability;
    }

    public function solve(Machine $machine): Lever
    {
        $r = $this->rng->float();
        if ($r <= $this->e) {
            return $machine->getRandomLever();
        }

        return $machine->getBestLever();
    }
}
