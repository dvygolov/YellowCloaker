<?php

namespace BenTools\SplitTestAnalyzer;

final class Variation
{
    /**
     * @var string|int
     */
    private $key;

    /**
     * @var int
     */
    private $nbTotalActions;

    /**
     * @var int
     */
    private $nbSuccessfulActions;

    /**
     * Version constructor.
     * @param string $key
     * @param int    $nbTotalActions
     * @param int    $nbSuccessfulActions
     * @throws \InvalidArgumentException
     */
    public function __construct(string $key, int $nbTotalActions, int $nbSuccessfulActions)
    {
        $this->key = $key;
        $this->nbTotalActions = max(0, $nbTotalActions);
        $this->nbSuccessfulActions = max(0, $nbSuccessfulActions);
        if ($this->nbSuccessfulActions > $this->nbTotalActions) {
            throw new \InvalidArgumentException("The number of successful actions should not exceed the total number of actions.");
        }
    }

    /**
     * @return string|int
     */
    public function getKey(): string
    {
        return $this->key;
    }

    /**
     * @return int
     */
    public function getNbSuccesses(): int
    {
        return $this->nbSuccessfulActions;
    }

    /**
     * @return int
     */
    public function getNbFailures(): int
    {
        $result = $this->nbTotalActions - $this->nbSuccessfulActions;
        return $result;
    }

    public function __toString()
    {
        return $this->key;
    }
}
