<?php

declare(strict_types=1);

namespace SimpleSAML\XML\Exception;

/**
 * This exception may be raised when the passed DOMElement is missing mandatory child-elements
 *
 * @package simplesamlphp/xml-common
 */
class MissingElementException extends SchemaViolationException
{
}
