<?php

declare(strict_types=1);

namespace SimpleSAML\XML\Exception;

use SimpleSAML\Assert\AssertionFailedException;

/**
 * This exception may be raised when a violation of the SAML2 schema is detected
 *
 * @package simplesamlphp/xml-common
 */
class SchemaViolationException extends AssertionFailedException
{
}
