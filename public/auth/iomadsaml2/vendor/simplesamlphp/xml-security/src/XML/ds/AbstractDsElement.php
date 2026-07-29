<?php

declare(strict_types=1);

namespace SimpleSAML\XMLSecurity\XML\ds;

use SimpleSAML\XML\AbstractElement;
use SimpleSAML\XMLSecurity\Constants as C;

/**
 * Abstract class to be implemented by all the classes in this namespace
 *
 * @package simplesamlphp/xml-security
 */
abstract class AbstractDsElement extends AbstractElement
{
    /** @var string */
    public const NS = C::NS_XDSIG;

    /** @var string */
    public const NS_PREFIX = 'ds';

    /** @var string */
    public const SCHEMA = 'resources/schemas/xmldsig-core-schema.xsd';
}
