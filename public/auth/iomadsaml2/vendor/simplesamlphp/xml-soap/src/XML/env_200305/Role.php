<?php

declare(strict_types=1);

namespace SimpleSAML\SOAP\XML\env_200305;

use SimpleSAML\Assert\Assert;
use SimpleSAML\XML\Exception\SchemaViolationException;
use SimpleSAML\XML\StringElementTrait;

/**
 * Class representing a env:Role element.
 *
 * @package simplesaml/xml-soap
 */
final class Role extends AbstractSoapElement
{
    use StringElementTrait;


    /**
     * Initialize a env:Role
     *
     * @param string $role
     */
    public function __construct(string $role)
    {
        $this->setContent($role);
    }


    /**
     * Validate the content of the element.
     *
     * @param string $content  The value to go in the XML textContent
     * @throws \Exception on failure
     * @return void
     */
    protected function validateContent(string $content): void
    {
        Assert::validURI($content, SchemaViolationException::class); // Covers the empty string
    }
}
