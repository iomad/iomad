<?php

declare(strict_types=1);

namespace SimpleSAML\XML;

use DateTimeImmutable;
use DateTimeInterface;
use DOMElement;
use SimpleSAML\XML\Assert\Assert;

use function trim;

/**
 * Helper functions for the XML library.
 *
 * @package simplesamlphp/xml-common
 */
class Utils
{
    /**
     * @deprecated Create elements that extend AbstractElement and use it's interface to retrieve attributes instead.
     *
     * Extract localized strings from a set of nodes.
     *
     * @param \DOMElement $parent The element that contains the localized strings.
     * @param string $namespaceURI The namespace URI the localized strings should have.
     * @param string $localName The localName of the localized strings.
     * @return array<string, string> Localized strings.
     */
    public static function extractLocalizedStrings(DOMElement $parent, string $namespaceURI, string $localName): array
    {
        $ret = [];
        foreach ($parent->childNodes as $node) {
            if ($node->namespaceURI !== $namespaceURI || $node->localName !== $localName) {
                continue;
            } elseif (!($node instanceof DOMElement)) {
                continue;
            }

            if ($node->hasAttribute('xml:lang')) {
                $language = $node->getAttribute('xml:lang');
            } else {
                $language = 'en';
            }
            $ret[$language] = trim($node->textContent);
        }

        return $ret;
    }


    /**
     * @deprecated Create elements that extend AbstractElement and use <element>::getChildrenOfClass() instead.
     *
     * Extract strings from a set of nodes.
     *
     * @param \DOMElement $parent The element that contains the localized strings.
     * @param string $namespaceURI The namespace URI the string elements should have.
     * @param string $localName The localName of the string elements.
     * @return string[] The string values of the various nodes.
     */
    public static function extractStrings(DOMElement $parent, string $namespaceURI, string $localName): array
    {
        $ret = [];
        foreach ($parent->childNodes as $node) {
            if ($node->namespaceURI !== $namespaceURI || $node->localName !== $localName) {
                continue;
            }
            $ret[] = trim($node->textContent);
        }

        return $ret;
    }


    /**
     * @deprecated Create elements that extend AbstractElement and use <element>::toXML() instead.
     *
     * Append string element.
     *
     * @param \DOMElement $parent The parent element we should append the new nodes to.
     * @param string $namespace The namespace of the created element.
     * @param string $name The name of the created element.
     * @param string $value The value of the element.
     * @return \DOMElement The generated element.
     */
    public static function addString(
        DOMElement $parent,
        string $namespace,
        string $name,
        string $value,
    ): DOMElement {
        $doc = $parent->ownerDocument;
        Assert::notNull($doc);

        $n = $doc->createElementNS($namespace, $name);
        $n->appendChild($doc->createTextNode($value));
        $parent->appendChild($n);

        return $n;
    }


    /**
     * @deprecated Create elements that extend AbstractElement and use <element>::toXML() instead.
     *
     * Append string elements.
     *
     * @param \DOMElement $parent The parent element we should append the new nodes to.
     * @param string $namespace The namespace of the created elements
     * @param string $name The name of the created elements
     * @param bool $localized Whether the strings are localized, and should include the xml:lang attribute.
     * @param string[] $values The values we should create the elements from.
     */
    public static function addStrings(
        DOMElement $parent,
        string $namespace,
        string $name,
        bool $localized,
        array $values,
    ): void {
        $doc = $parent->ownerDocument;
        Assert::notNull($doc);

        foreach ($values as $index => $value) {
            $n = $doc->createElementNS($namespace, $name);
            $n->appendChild($doc->createTextNode($value));
            if ($localized) {
                $n->setAttribute('xml:lang', $index);
            }
            $parent->appendChild($n);
        }
    }


    /**
     * @deprecated Use DateTime objects instead
     *
     * This function converts a SAML2 timestamp on the form
     * yyyy-mm-ddThh:mm:ss(\.s+)?Z to a UNIX timestamp. The sub-second
     * part is ignored.
     *
     * Note that we always require a 'Z' timezone for the dateTime to be valid.
     * This is not in the SAML spec but that's considered to be a bug in the
     * spec. See https://github.com/simplesamlphp/saml2/pull/36 for some
     * background.
     *
     * @param string $time The time we should convert.
     * @throws \Exception
     * @return int Converted to a unix timestamp.
     */
    public static function xsDateTimeToTimestamp(string $time): int
    {
        Assert::validDateTime($time);

        $dateTime1 = DateTimeImmutable::createFromFormat(DateTimeInterface::ISO8601, $time);
        $dateTime2 = DateTimeImmutable::createFromFormat(DateTimeInterface::RFC3339_EXTENDED, $time);

        $dateTime = $dateTime1 ?: $dateTime2;
        Assert::isInstanceOf($dateTime, DateTimeImmutable::class);
        Assert::same($dateTime->getTimeZone()->getName(), 'Z');

        return $dateTime->getTimestamp();
    }
}
