<?php

namespace ONToolkit\Tests\Modules\LinkScanner;

use PHPUnit\Framework\TestCase;
use ONToolkit\Modules\LinkScanner\Services\HttpVerifier;

class HttpVerifierTest extends TestCase
{
    public function testHttpVerifierInstantiation(): void
    {
        $verifier = new HttpVerifier();
        $this->assertInstanceOf(HttpVerifier::class, $verifier);
    }
}
