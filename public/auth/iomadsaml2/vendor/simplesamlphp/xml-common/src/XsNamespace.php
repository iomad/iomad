<?php

declare(strict_types=1);

namespace SimpleSAML\XML;

/**
 * The namespace-attribute values for xs:any elements
 *
 * @package simplesamlphp/xml-common
 */
enum XsNamespace: string
{
    case ANY = '##any';
    case LOCAL = '##local';
    case OTHER = '##other';
    case TARGET = '##targetNamespace';
}
