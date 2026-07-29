<?php

declare(strict_types=1);

namespace SimpleSAML\XML;

/**
 * Various XML constants.
 *
 * @package simplesamlphp/xml-common
 */
class Constants
{
    /**
     * The namespace for XML.
     */
    public const NS_XML = 'http://www.w3.org/XML/1998/namespace';

    /**
     * The namespace for XML schema.
     */
    public const NS_XS = 'http://www.w3.org/2001/XMLSchema';

    /**
     * The namespace for XML schema instance.
     */
    public const NS_XSI = 'http://www.w3.org/2001/XMLSchema-instance';

    /**
     * The maximum amount of child nodes this library is willing to handle.
     * By specification, this limit is 150K, but that opens up for denial of service.
     */
    public const UNBOUNDED_LIMIT = 10000;

    /**
     * The namespace for the XML Path Language 1.0
     */
    public const XPATH10_URI = 'http://www.w3.org/TR/1999/REC-xpath-19991116';

    /** @var array<string> */
    public const DEFAULT_ALLOWED_AXES = [
        'ancestor',
        'ancestor-or-self',
        'attribute',
        'child',
        'descendant',
        'descendant-or-self',
        'following',
        'following-sibling',
        // 'namespace', // By default, we do not allow using the namespace axis
        'parent',
        'preceding',
        'preceding-sibling',
        'self',
    ];

    /** @var array<string> */
    public const DEFAULT_ALLOWED_FUNCTIONS = [
        // 'boolean',
        // 'ceiling',
        // 'concat',
        // 'contains',
        // 'count',
        // 'false',
        // 'floor',
        // 'id',
        // 'lang',
        // 'last',
        // 'local-name',
        // 'name',
        // 'namespace-uri',
        // 'normalize-space',
        'not',
        // 'number',
        // 'position',
        // 'round',
        // 'starts-with',
        // 'string',
        // 'string-length',
        // 'substring',
        // 'substring-after',
        // 'substring-before',
        // 'sum',
        // 'text',
        // 'translate',
        // 'true',
    ];

    /** @var int */
    public const XPATH_FILTER_MAX_LENGTH = 100;
}
