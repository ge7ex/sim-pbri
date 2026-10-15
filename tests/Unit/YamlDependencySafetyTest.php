<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Yaml\Exception\ParseException;
use Symfony\Component\Yaml\Yaml;

final class YamlDependencySafetyTest extends TestCase
{
    public function test_valid_yaml_remains_supported_and_excessive_inline_nesting_is_rejected(): void
    {
        $this->assertSame(['services' => ['demo' => ['image' => 'example:test']]], Yaml::parse("services:\n  demo:\n    image: example:test\n"));

        $this->expectException(ParseException::class);
        Yaml::parse(str_repeat('[', 512).'0'.str_repeat(']', 512));
    }
}
