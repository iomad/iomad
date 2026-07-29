<?php

declare(strict_types=1);

namespace SimpleSAML\SOAP\XML\env_200106;

use SimpleSAML\Assert\Assert;
use SimpleSAML\XML\AbstractElement;
use SimpleSAML\XML\Exception\SchemaViolationException;
use SimpleSAML\XML\StringElementTrait;

/**
 * Class representing a faultactor element.
 *
 * @package simplesaml/xml-soap
 */
final class FaultActor extends AbstractElement
{
    use StringElementTrait;

    /** @var string */
    public const LOCALNAME = 'faultactor';

    /** @var null */
    public const NS = null;

    /** @var null */
    public const NS_PREFIX = null;


    /**
     * Initialize an faultactor
     *
     * @param string $faultActor
     */
    public function __construct(string $faultActor)
    {
        $this->setContent($faultActor);
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
