<?php

declare(strict_types=1);

namespace SimpleSAML\XML\Exception;

/**
 * This exception may be raised when the passed DOMElement is missing a mandatory attribute
 *
 * @package simplesamlphp/xml-common
 */
class MissingAttributeException extends SchemaViolationException
{
}
