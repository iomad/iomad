<?php

declare(strict_types=1);

namespace SimpleSAML\XMLSecurity\XML\xenc11;

/**
 * A class implementing the xenc11:PBKDF2-params element.
 *
 * @package simplesamlphp/xml-security
 */
final class PBKDF2params extends AbstractPBKDF2ParameterType
{
    /** @var string */
    public const LOCALNAME = 'PBKDF2-params';
}
