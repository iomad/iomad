<?php

declare(strict_types=1);

namespace SimpleSAML\XML;

use DOMElement;
use RuntimeException;
use SimpleSAML\Assert\Assert;
use SimpleSAML\XML\Attribute;
use SimpleSAML\XML\Constants as C;
use SimpleSAML\XML\XsNamespace as NS;

use function array_diff;
use function array_map;
use function array_search;
use function defined;
use function implode;
use function in_array;
use function is_array;
use function rtrim;
use function sprintf;

/**
 * Trait for elements that can have arbitrary namespaced attributes.
 *
 * @package simplesamlphp/xml-common
 */
trait ExtendableAttributesTrait
{
    /**
     * Extra (namespace qualified) attributes.
     *
     * @var array<int, \SimpleSAML\XML\Attribute>
     */
    protected array $namespacedAttributes = [];


    /**
     * Check if a namespace-qualified attribute exists.
     *
     * @param string|null $namespaceURI The namespace URI.
     * @param string $localName The local name.
     * @return bool true if the attribute exists, false if not.
     */
    public function hasAttributeNS(?string $namespaceURI, string $localName): bool
    {
        foreach ($this->getAttributesNS() as $attr) {
            if ($attr->getNamespaceURI() === $namespaceURI && $attr->getAttrName() === $localName) {
                return true;
            }
        }
        return false;
    }


    /**
     * Get a namespace-qualified attribute.
     *
     * @param string|null $namespaceURI The namespace URI.
     * @param string $localName The local name.
     * @return \SimpleSAML\XML\Attribute|null The value of the attribute, or null if the attribute does not exist.
     */
    public function getAttributeNS(?string $namespaceURI, string $localName): ?Attribute
    {
        foreach ($this->getAttributesNS() as $attr) {
            if ($attr->getNamespaceURI() === $namespaceURI && $attr->getAttrName() === $localName) {
                return $attr;
            }
        }
        return null;
    }


    /**
     * Get the namespaced attributes in this element.
     *
     * @return array<int, \SimpleSAML\XML\Attribute>
     */
    public function getAttributesNS(): array
    {
        return $this->namespacedAttributes;
    }


    /**
     * Parse an XML document and get the namespaced attributes from the specified namespace(s).
     * The namespace defaults to the XS_ANY_ATTR_NAMESPACE constant on the element.
     * NOTE: In case the namespace is ##any, this method will also return local non-namespaced attributes!
     *
     * @param \DOMElement $xml
     * @param \SimpleSAML\XML\XsNamespace|array|null $namespace
     *
     * @return array<int, \SimpleSAML\XML\Attribute> $attributes
     */
    protected static function getAttributesNSFromXML(DOMElement $xml, NS|array|null $namespace = null): array
    {
        $namespace = $namespace ?? self::XS_ANY_ATTR_NAMESPACE;
        $exclusionList = self::getAttributeExclusions();
        $attributes = [];

        // Validate namespace value
        if (!is_array($namespace)) {
            // Must be one of the predefined values
            Assert::oneOf($namespace, NS::cases());

            foreach ($xml->attributes as $a) {
                if (in_array([$a->namespaceURI, $a->localName], $exclusionList, true)) {
                    continue;
                } elseif ($namespace === NS::OTHER && in_array($a->namespaceURI, [self::NS, null], true)) {
                    continue;
                } elseif ($namespace === NS::TARGET && $a->namespaceURI !== self::NS) {
                    continue;
                } elseif ($namespace === NS::LOCAL && $a->namespaceURI !== null) {
                    continue;
                }

                $attributes[] = new Attribute($a->namespaceURI, $a->prefix, $a->localName, $a->nodeValue);
            }
        } else {
            // Array must be non-empty and cannot contain ##any or ##other
            Assert::notEmpty($namespace);
            Assert::allStringNotEmpty($namespace);
            Assert::allNotSame($namespace, NS::ANY);
            Assert::allNotSame($namespace, NS::OTHER);

            // Replace the ##targetedNamespace with the actual namespace
            if (($key = array_search(NS::TARGET, $namespace)) !== false) {
                $namespace[$key] = self::NS;
            }

            // Replace the ##local with null
            if (($key = array_search(NS::LOCAL, $namespace)) !== false) {
                $namespace[$key] = null;
            }

            foreach ($xml->attributes as $a) {
                if (in_array([$a->namespaceURI, $a->localName], $exclusionList, true)) {
                    continue;
                } elseif (!in_array($a->namespaceURI, $namespace, true)) {
                    continue;
                }

                $attributes[] = new Attribute($a->namespaceURI, $a->prefix, $a->localName, $a->nodeValue);
            }
        }

        return $attributes;
    }


    /**
     * @param array<int, \SimpleSAML\XML\Attribute> $attributes
     * @throws \SimpleSAML\Assert\AssertionFailedException if $attributes contains anything other than Attribute objects
     */
    protected function setAttributesNS(array $attributes): void
    {
        Assert::maxCount($attributes, C::UNBOUNDED_LIMIT);
        Assert::allIsInstanceOf(
            $attributes,
            Attribute::class,
            'Arbitrary XML attributes can only be an instance of Attribute.',
        );
        $namespace = $this->getAttributeNamespace();

        // Validate namespace value
        if (!is_array($namespace)) {
            // Must be one of the predefined values
            Assert::oneOf($namespace, NS::cases());
        } else {
            // Array must be non-empty and cannot contain ##any or ##other
            Assert::notEmpty($namespace);
            Assert::allNotSame($namespace, NS::ANY);
            Assert::allNotSame($namespace, NS::OTHER);
        }

        // Get namespaces for all attributes
        $actual_namespaces = array_map(
            /**
             * @param \SimpleSAML\XML\Attribute $elt
             * @return string|null
             */
            function (Attribute $attr) {
                return $attr->getNamespaceURI();
            },
            $attributes,
        );

        if ($namespace === NS::LOCAL) {
            // If ##local then all namespaces must be null
            Assert::allNull($actual_namespaces);
        } elseif (is_array($namespace)) {
            // Make a local copy of the property that we can edit
            $allowed_namespaces = $namespace;

            // Replace the ##targetedNamespace with the actual namespace
            if (($key = array_search(NS::TARGET, $allowed_namespaces)) !== false) {
                $allowed_namespaces[$key] = self::NS;
            }

            // Replace the ##local with null
            if (($key = array_search(NS::LOCAL, $allowed_namespaces)) !== false) {
                $allowed_namespaces[$key] = null;
            }

            $diff = array_diff($actual_namespaces, $allowed_namespaces);
            Assert::isEmpty(
                $diff,
                sprintf(
                    'Attributes from namespaces [ %s ] are not allowed inside a %s element.',
                    rtrim(implode(', ', $diff)),
                    self::NS,
                ),
            );
        } else {
            if ($namespace === NS::OTHER) {
                // All attributes must be namespaced, ergo non-null
                Assert::allNotNull($actual_namespaces);

                // Must be any namespace other than the parent element
                Assert::allNotSame($actual_namespaces, self::NS);
            } elseif ($namespace === NS::TARGET) {
                // Must be the same namespace as the one of the parent element
                Assert::allSame($actual_namespaces, self::NS);
            }
        }

        $exclusionList = self::getAttributeExclusions();
        foreach ($attributes as $i => $attr) {
            if (in_array([$attr->getNamespaceURI(), $attr->getAttrName()], $exclusionList, true)) {
                unset($attributes[$i]);
            }
        }

        $this->namespacedAttributes = $attributes;
    }



    /**
     * @return array|\SimpleSAML\XML\XsNamespace
     */
    public function getAttributeNamespace(): array|NS
    {
        Assert::true(
            defined('self::XS_ANY_ATTR_NAMESPACE'),
            self::getClassName(self::class)
            . '::XS_ANY_ATTR_NAMESPACE constant must be defined and set to the namespace for the xs:anyAttribute.',
            RuntimeException::class,
        );

        return self::XS_ANY_ATTR_NAMESPACE;
    }


    /**
     * Get the exclusions list for getAttributeNSFromXML.
     *
     * @return array<string, string>
     */
    public static function getAttributeExclusions(): array
    {
        if (defined('self::XS_ANY_ATTR_EXCLUSIONS')) {
            return self::XS_ANY_ATTR_EXCLUSIONS;
        }

        return [];
    }
}
