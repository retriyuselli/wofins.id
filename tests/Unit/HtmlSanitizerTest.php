<?php

namespace Tests\Unit;

use App\Support\HtmlSanitizer;
use PHPUnit\Framework\TestCase;

class HtmlSanitizerTest extends TestCase
{
    public function test_it_removes_scripts_event_handlers_and_unsafe_urls(): void
    {
        $clean = HtmlSanitizer::sanitize(
            '<p onclick="evil()">A</p><script>alert(1)</script><a href="javascript:evil()">B</a>'
        );

        $this->assertSame('<p>A</p><a>B</a>', $clean);
    }
}
