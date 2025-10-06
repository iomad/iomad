<?php

declare(strict_types=1);

namespace SimpleSAML\Test\Utils;

// Declare a simple class
class TestClass
{
    private string $foo;

    public function __construct(string $foo)
    {
        $this->foo = $foo;
    }

    public function __toString(): string
    {
        return $this->foo;
    }
}
