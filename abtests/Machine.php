<?php
declare(strict_types=1);

namespace Offdev\Bandit;

use Offdev\Bandit\Exceptions\RuntimeException;

class Machine
{
    private array $leverList = [];

    public function __construct(Lever ...$levers)
    {
        if (empty($levers)) {
            throw new RuntimeException('Must provide at least one lever!');
        }

        $this->leverList = $levers;
    }

    /**
     * @return Lever[]
     */
    public function getLeverList(): array
    {
        return $this->leverList;
    }

    public function getRandomLever(): Lever
    {
        return $this->leverList[array_rand($this->leverList)];
    }

    public function getBestLever(): Lever
    {
        $bestLever = reset($this->leverList);
        $bestConversion = $this->getConversion($bestLever);
        foreach ($this->leverList as $lever) {
            $c = $this->getConversion($lever);
            if ($c > $bestConversion) {
                $bestConversion = $c;
                $bestLever = $lever;
            }
        }

        return $bestLever;
    }

    private function getConversion(Lever $lever): float
    {
        if ($lever->getTries() === 0) {
            return 1.0;
        }

        return $lever->getRewards() / $lever->getTries();
    }
}
