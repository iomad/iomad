<?php

declare(strict_types=1);

namespace SimpleSAML\XMLSecurity\XML\dsig11;

use SimpleSAML\XML\AbstractElement;
use SimpleSAML\XMLSecurity\Constants as C;

/**
 * Abstract class to be implemented by all the classes in this namespace
 *
 * @package simplesamlphp/xml-security
 */
abstract class AbstractDsig11Element extends AbstractElement
{
    /** @var string */
    public const NS = C::NS_XDSIG11;

    /** @var string */
    public const NS_PREFIX = 'dsig11';

    /** @var string */
    public const SCHEMA = 'resources/schemas/xmldsig11-schema.xsd';
}
