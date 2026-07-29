<?php

declare(strict_types=1);

namespace SimpleSAML\XMLSecurity\XML\xenc11;

/**
 * Class representing <xenc11:AbstractPRFAlgorithmIdentifierType>.
 *
 * @package simplesamlphp/xml-security
 */
abstract class AbstractPRFAlgorithmIdentifierType extends AbstractAlgorithmIdentifierType
{
    /**
     * AlgorithmPRFIdentifierType constructor.
     *
     * @param string $Algorithm
     */
    public function __construct(
        string $Algorithm,
    ) {
        parent::__construct($Algorithm, null);
    }
}
