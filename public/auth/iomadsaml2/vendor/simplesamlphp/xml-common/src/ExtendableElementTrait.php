<?php

declare(strict_types=1);

namespace SimpleSAML\XML;

use DOMElement;
use RuntimeException;
use SimpleSAML\Assert\Assert;
use SimpleSAML\XML\Chunk;
use SimpleSAML\XML\Constants as C;
use SimpleSAML\XML\Registry\ElementRegistry;
use SimpleSAML\XML\XsNamespace as NS;

use function array_diff;
use function array_map;
use function array_search;
use function defined;
use function implode;
use function is_array;
use function rtrim;
use function sprintf;

/**
 * Trait grouping common functionality for elements implementing the xs:any element.
 *
 * @package simplesamlphp/xml-common
 */
trait ExtendableElementTrait
{
    /** @var \SimpleSAML\XML\SerializableElementInterface[] */
    protected array $elements = [];


    /**
     * Parse an XML document and get the child elements from the specified namespace(s).
     * The namespace defaults to the XS_ANY_ELT_NAMESPACE constant on the element.
     * NOTE: In case the namespace is ##any, this method will also return local non-namespaced elements!
     *
     * @param \DOMElement $xml
     * @param \SimpleSAML\XML\XsNamespace|array|null $namespace
     *
     * @return list<\SimpleSAML\XML\SerializableElementInterface> $elements
     */
    protected static function getChildElementsFromXML(DOMElement $xml, NS|array|null $namespace = null): array
    {
        $namespace = $namespace ?? self::XS_ANY_ELT_NAMESPACE;
        $exclusionList = self::getElementExclusions();
        $registry = ElementRegistry::getInstance();
        $elements = [];

        // Validate namespace value
        if (!is_array($namespace)) {
            // Must be one of the predefined values
            Assert::oneOf($namespace, NS::cases());

            foreach ($xml->childNodes as $elt) {
                if (!($elt instanceof DOMElement)) {
                    continue;
                } elseif (in_array([$elt->namespaceURI, $elt->localName], $exclusionList, true)) {
                    continue;
                } elseif ($namespace === NS::OTHER && in_array($elt->namespaceURI, [self::NS, null], true)) {
                    continue;
                } elseif ($namespace === NS::TARGET && $elt->namespaceURI !== self::NS) {
                    continue;
                } elseif ($namespace === NS::LOCAL && $elt->namespaceURI !== null) {
                    continue;
                }

                $handler = $registry->getElementHandler($elt->namespaceURI, $elt->localName);
                $elements[] = ($handler === null) ? Chunk::fromXML($elt) : $handler::fromXML($elt);
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

            foreach ($xml->childNodes as $elt) {
                if (!($elt instanceof DOMElement)) {
                    continue;
                } elseif (in_array([$elt->namespaceURI, $elt->localName], $exclusionList, true)) {
                    continue;
                } elseif (!in_array($elt->namespaceURI, $namespace, true)) {
                    continue;
                }

                $handler = $registry->getElementHandler($elt->namespaceURI, $elt->localName);
                $elements[] = ($handler === null) ? Chunk::fromXML($elt) : $handler::fromXML($elt);
            }
        }

        return $elements;
    }


    /**
     * Set an array with all elements present.
     *
     * @param \SimpleSAML\XML\SerializableElementInterface[] $elements
     * @return void
     */
    protected function setElements(array $elements): void
    {
        Assert::maxCount($elements, C::UNBOUNDED_LIMIT);
        Assert::allIsInstanceOf($elements, SerializableElementInterface::class);
        $namespace = $this->getElementNamespace();

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

        // Get namespaces for all elements
        $actual_namespaces = array_map(
            /**
             * @param \SimpleSAML\XML\SerializableElementInterface $elt
             * @return string|null
             */
            function (SerializableElementInterface $elt) {
                return ($elt instanceof Chunk) ? $elt->getNamespaceURI() : $elt::getNamespaceURI();
            },
            $elements,
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
                    'Elements from namespaces [ %s ] are not allowed inside a %s element.',
                    rtrim(implode(', ', $diff)),
                    self::NS,
                ),
            );
        } elseif ($namespace === NS::OTHER) {
            // Must be any namespace other than the parent element, excluding elements with no namespace
            Assert::notInArray(null, $actual_namespaces);
            Assert::allNotSame($actual_namespaces, self::NS);
        } elseif ($namespace === NS::TARGET) {
            // Must be the same namespace as the one of the parent element
            Assert::allSame($actual_namespaces, self::NS);
        } else {
            // XS_ANY_NS_ANY
        }

        $exclusionList = self::getElementExclusions();
        foreach ($elements as $i => $elt) {
            if (in_array([$elt->getNamespaceURI(), $elt->getLocalName()], $exclusionList, true)) {
                unset($elements[$i]);
            }
        }

        $this->elements = $elements;
    }


    /**
     * Get an array with all elements present.
     *
     * @return \SimpleSAML\XML\SerializableElementInterface[]
     */
    public function getElements(): array
    {
        return $this->elements;
    }


    /**
     * @return array|\SimpleSAML\XML\XsNamespace
     */
    public function getElementNamespace(): array|NS
    {
        Assert::true(
            defined('self::XS_ANY_ELT_NAMESPACE'),
            self::getClassName(self::class)
            . '::XS_ANY_ELT_NAMESPACE constant must be defined and set to the namespace for the xs:any element.',
            RuntimeException::class,
        );

        return self::XS_ANY_ELT_NAMESPACE;
    }


    /**
     * Get the exclusions list for getChildElementsFromXML.
     *
     * @return array<string, string>
     */
    public static function getElementExclusions(): array
    {
        if (defined('self::XS_ANY_ELT_EXCLUSIONS')) {
            return self::XS_ANY_ELT_EXCLUSIONS;
        }

        return [];
    }
}
