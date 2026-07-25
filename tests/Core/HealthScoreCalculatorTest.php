<?php

namespace ONToolkit\Tests\Core;

use PHPUnit\Framework\TestCase;
use ONToolkit\Core\Services\HealthScoreCalculator;

class HealthScoreCalculatorTest extends TestCase
{
    public function testCalculateScoreReturnsValidStructure(): void
    {
        $calculator = new HealthScoreCalculator();
        $result = $calculator->calculateScore();

        $this->assertArrayHasKey('score', $result);
        $this->assertArrayHasKey('pillars', $result);
        $this->assertGreaterThanOrEqual(0, $result['score']);
        $this->assertLessThanOrEqual(100, $result['score']);

        $pillars = $result['pillars'];
        $this->assertArrayHasKey('performance', $pillars);
        $this->assertArrayHasKey('database', $pillars);
        $this->assertArrayHasKey('links', $pillars);
        $this->assertArrayHasKey('media', $pillars);
    }
}
