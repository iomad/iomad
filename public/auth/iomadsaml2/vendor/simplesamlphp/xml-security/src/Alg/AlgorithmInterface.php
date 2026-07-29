<?php

declare(strict_types=1);

namespace SimpleSAML\XMLSecurity\Alg;

/**
 * An interface representing any kind of algorithm.
 *
 * @package simplesamlphp/xml-security
 */
interface AlgorithmInterface
{
    /**
     * Get an array with all the identifiers for algorithms supported.
     *
     * @return string[]
     */
    public static function getSupportedAlgorithms(): array;


    /**
     * Get the identifier of this algorithm.
     *
     * @return string The identifier of this algorithm.
     */
    public function getAlgorithmId(): string;
}
