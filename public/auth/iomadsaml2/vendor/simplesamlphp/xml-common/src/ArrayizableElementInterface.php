<?php

declare(strict_types=1);

namespace SimpleSAML\XML;

/**
 * interface class to be implemented by all the classes that represent an arrayizable XML element
 *
 * @package simplesamlphp/xml-common
 */
interface ArrayizableElementInterface
{
    /**
     * Create a class from an array
     *
     * @param array<string, mixed> $data
     * @return static
     */
    public static function fromArray(array $data): static;


    /**
     * Create an array from this class
     *
     * @return array<string, mixed>
     */
    public function toArray(): array;
}
