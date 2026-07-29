<?php

declare(strict_types=1);

namespace SimpleSAML\SOAP;

/**
 * Class holding constants relevant for XML SOAP
 *
 * @package simplesamlphp/xml-soap
 */

class Constants extends \SimpleSAML\XML\Constants
{
    /**
     * The namespace for the SOAP envelope 1.1.
     */
    public const NS_SOAP_ENV_11 = 'http://schemas.xmlsoap.org/soap/envelope/';

    /**
     * The namespace for the SOAP envelope 1.2.
     */
    public const NS_SOAP_ENV_12 = 'http://www.w3.org/2003/05/soap-envelope';

    /**
     * The namespace for SOAP encoding 1.1.
     */
    public const NS_SOAP_ENC_11 = 'https://schemas.xmlsoap.org/soap/encoding/';

    /**
     * The namespace for SOAP encoding 1.2.
     */
    public const NS_SOAP_ENC_12 = 'http://www.w3.org/2003/05/soap-encoding';

    /**
     */
    public const SOAP_ACTOR_NEXT = 'http://schemas.xmlsoap.org/soap/actor/next';
}
