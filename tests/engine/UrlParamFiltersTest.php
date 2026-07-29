<?php

use PHPUnit\Framework\TestCase;

class UrlParamFiltersTest extends TestCase
{
    private function makeUrlParamFilter(string $operator, array|string $value): array
    {
        return [
            'condition' => 'AND',
            'rules' => [[
                'id' => 'urlparam',
                'field' => 'urlparam',
                'type' => 'string',
                'input' => 'text',
                'operator' => $operator,
                'value' => $value,
            ]]
        ];
    }

    public function testUrlParamExistsMatchesWhenParameterIsPresent(): void
    {
        $clkr = new FiltrationCore(['tds_qs' => 'fbclid=abc123&utm_source=fb']);

        $result = $clkr->click_matches_filters($this->makeUrlParamFilter('param_exists', ['fbclid']));

        $this->assertTrue($result);
    }

    public function testUrlParamExistsDoesNotMatchWhenParameterIsMissing(): void
    {
        $clkr = new FiltrationCore(['tds_qs' => 'utm_source=fb']);

        $result = $clkr->click_matches_filters($this->makeUrlParamFilter('param_exists', ['fbclid']));

        $this->assertFalse($result);
    }

    public function testUrlParamNotExistsMatchesWhenParameterIsMissing(): void
    {
        $clkr = new FiltrationCore(['tds_qs' => 'utm_source=fb']);

        $result = $clkr->click_matches_filters($this->makeUrlParamFilter('param_not_exists', ['fbclid']));

        $this->assertTrue($result);
    }

    public function testUrlParamValueMatchingStillWorks(): void
    {
        $clkr = new FiltrationCore(['tds_qs' => 'fbclid=abc123&utm_source=fb']);

        $result = $clkr->click_matches_filters($this->makeUrlParamFilter('param_in', ['utm_source', 'fb,tt']));

        $this->assertTrue($result);
    }
}
