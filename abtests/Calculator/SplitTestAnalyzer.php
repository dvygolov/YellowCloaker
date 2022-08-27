<?php

namespace BenTools\SplitTestAnalyzer;

final class SplitTestAnalyzer implements \IteratorAggregate
{

    const DEFAULT_SAMPLES = 5000;

    /**
     * @var int
     */
    private $numSamples;

    /**
     * @var Variation[]
     */
    private $variations = [];

    /**
     * @var array
     */
    private $result;

    /**
     * BayesianPerformance constructor.
     * @param int       $numSamples
     */
    private function __construct(int $numSamples = self::DEFAULT_SAMPLES)
    {
        $this->numSamples = $numSamples;
    }

    /**
     * @param Variation[] ...$variations
     * @throws \InvalidArgumentException
     */
    private function setVariations(Variation ...$variations)
    {
        foreach ($variations as $variation) {
            if (array_key_exists($variation->getKey(), $this->variations)) {
                throw new \InvalidArgumentException(sprintf('Variation %s already exists into the stack.', $variation->getKey()));
            }

            $this->variations[$variation->getKey()] = $variation;
        }
    }

    /**
     * @param int       $numSamples
     * @return SplitTestAnalyzer
     */
    public static function create(int $numSamples = self::DEFAULT_SAMPLES): self
    {
        return new self($numSamples);
    }

    /**
     * @param Variation[] ...$variations
     * @return SplitTestAnalyzer
     * @throws \InvalidArgumentException
     */
    public function withVariations(Variation ...$variations): self
    {
        if (count($variations) < 2) {
            throw new \InvalidArgumentException("At least 2 variations to compare are expected.");
        }
        $object = ([] === $this->variations) ? $this : clone $this;
        $object->reset();
        $object->setVariations(...$variations);
        return $object;
    }

    /**
     * @param Variation $variation
     * @return float
     */
    public function getVariationProbability(Variation $variation): float
    {
        $this->check($variation);
        $this->computeProbabilities();
        return $this->result[$variation->getKey()];
    }

    /**
     * @param int $sort
     * @return Variation[]
     */
    public function getOrderedVariations(int $sort = SORT_DESC): array
    {
        $this->computeProbabilities();
        $variations = $this->variations;
        uasort($variations, function (Variation $variationA, Variation $variationB) use ($sort) {
            if (SORT_DESC === $sort) {
                return $this->getVariationProbability($variationB) <=> $this->getVariationProbability($variationA);
            } else {
                return $this->getVariationProbability($variationA) <=> $this->getVariationProbability($variationB);
            }
        });

        return $variations;
    }

    /**
     * @return Variation|null
     */
    public function getBestVariation(): ?Variation
    {
        $variations = $this->getOrderedVariations(SORT_DESC);
        $best = reset($variations);
        if ($best instanceof Variation && 0 === $best->getNbSuccesses()) {
            return null;
        }
        return $best;
    }

    /**
     * @return array
     */
    public function getResult()
    {
        $this->computeProbabilities();
        return $this->result;
    }
    
    #[\ReturnTypeWillChange]
    public function getIterator()
    {
        $result = $this->getResult();
        yield from $result;
    }

    /**
     * @return mixed
     * @throws \Exception
     * @throws \InvalidArgumentException
     */
    private function computeProbabilities()
    {
        if (count($this->variations) < 2) {
            throw new \InvalidArgumentException("At least 2 variations to compare are expected.");
        }

        if (null !== $this->result) {
            return;
        }
        $variations = $this->variations;
        $winnerIndex = null;
        $numRows = count($variations);
        $winCount = array_combine(array_keys($variations), array_fill(0, $numRows, 0));

        for ($i = 0; $i < $this->numSamples; $i++) {
            $winnerValue = 0;

            foreach ($variations as $v => $variation) {
                $x = $this->getSample($variation->getNbSuccesses(), $variation->getNbFailures());
                if ($x > $winnerValue) {
                    $winnerIndex = $v;
                    $winnerValue = $x;
                }
            }

            if (null !== $winnerIndex) {
                $winCount[$winnerIndex]++;
            }
        }

        foreach ($variations as $v => $variation) {
            $this->result[$v] = round($winCount[$v] / ($this->numSamples / 100));
        }
    }

    /**
     * @param Variation[] ...$variations
     * @throws \InvalidArgumentException
     */
    private function check(Variation ...$variations)
    {
        foreach ($variations as $variation) {
            if (!in_array($variation, $this->variations)) {
                throw new \InvalidArgumentException(sprintf('Variation %s not found in the stack.', $variation->getKey()));
            }
        }
    }

    /**
     * @return float
     * @throws \Exception
     */
    private function getMathRandom(): float
    {
        return (float) random_int(0, PHP_INT_MAX) / PHP_INT_MAX;
    }

    /**
     * @return float
     * @throws \Exception
     */
    private function getRandn(): float
    {
        $u = $v = $x = $y = $q = null;
        do {
            $u = $this->getMathRandom();
            $v = 1.7156 * ($this->getMathRandom() - 0.5);
            $x = $u - 0.449871;
            $y = abs($v) + 0.386595;
            $q = $x * $x + $y * (0.19600 * $y - 0.25472 * $x);
        } while ($q > 0.27597 && ($q > 0.27846 || $v * $v > -4 * log($u) * $u * $u));
        return $v / $u;
    }

    /**
     * @param float $shape
     * @return float
     * @throws \Exception
     */
    private function getRandg(float $shape): float
    {
        if (0.0 === $shape) {
            return 0;
        }

        $oalph = $shape;
        $a1 = $a2 = $u = $v = $x = null;

        if (!$shape) {
            $shape = 1;
        }
        if ($shape < 1) {
            $shape += 1;
        }

        $a1 = $shape - 1 / 3;
        $a2 = 1 / sqrt(9 * $a1);
        do {
            do {
                $x = $this->getRandn();
                $v = 1 + $a2 * $x;
            } while ($v <= 0);
            $v = $v * $v * $v;
            $u = $this->getMathRandom();
        } while ($u > 1 - 0.331 * pow($x, 4) &&
        log($u) > 0.5 * $x * $x + $a1 * (1 - $v + log($v)));

        // alpha > 1
        if ($shape == $oalph) {
            return $a1 * $v;
        }
        // alpha < 1
        do {
            $u = $this->getMathRandom();
        } while ($u === 0);
        return pow($u, 1 / $oalph) * $a1 * $v;
    }

    /**
     * @param float $alpha
     * @param float $beta
     * @return float
     * @throws \Exception
     */
    private function getSample(float $alpha, float $beta): float
    {
        if (0.0 === $alpha) {
            return 0.0;
        }
        $u = $this->getRandg($alpha);
        return $u / ($u + $this->getRandg($beta));
    }

    private function reset()
    {
        $this->variations = [];
        $this->result = null;
    }
}
