<?php

declare(strict_types=1);

namespace SimpleSAML\XMLSecurity\Key;

/**
 * An interface for keys
 *
 * This interface can be implemented in order to implement specific types of keys.
 *
 * @package simplesamlphp/xml-security
 */
interface KeyInterface
{
    /**
     * Return the key material associated with this key.
     *
     * @return string The key material.
     */
    public function getMaterial(): string;
}
