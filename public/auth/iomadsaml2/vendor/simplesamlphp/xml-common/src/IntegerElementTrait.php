<?php

declare(strict_types=1);

namespace SimpleSAML\XML;

use SimpleSAML\Assert\Assert;
use SimpleSAML\XML\Exception\SchemaViolationException;

/**
 * Trait grouping common functionality for simple elements with just some textContent
 *
 * @package simplesamlphp/xml-common
 */
trait IntegerElementTrait
{
    use StringElementTrait;


    /**
     * Validate the content of the element.
     *
     * @param int $content  The value to go in the XML textContent
     * @throws \Exception on failure
     * @return void
     */
    protected function validateContent(/** @scrutinizer ignore-unused */ string $content): void
    {
        /**
         * Perform no validation by default.
         * Override this method on the implementing class to perform content validation.
         */
        Assert::numeric($content, SchemaViolationException::class);
    }
}
