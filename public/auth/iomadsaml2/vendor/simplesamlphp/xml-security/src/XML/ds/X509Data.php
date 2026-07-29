<?php

declare(strict_types=1);

namespace SimpleSAML\XMLSecurity\XML\ds;

use DOMElement;
use SimpleSAML\Assert\Assert;
use SimpleSAML\XML\Chunk;
use SimpleSAML\XML\Exception\InvalidDOMElementException;
use SimpleSAML\XML\SchemaValidatableElementInterface;
use SimpleSAML\XML\SchemaValidatableElementTrait;
use SimpleSAML\XMLSecurity\Constants as C;
use SimpleSAML\XMLSecurity\Exception\InvalidArgumentException;
use SimpleSAML\XMLSecurity\XML\dsig11\X509Digest;

/**
 * Class representing a ds:X509Data element.
 *
 * @package simplesamlphp/xml-security
 */
final class X509Data extends AbstractDsElement implements SchemaValidatableElementInterface
{
    use SchemaValidatableElementTrait;

    /**
     * Initialize a X509Data.
     *
     * @param (\SimpleSAML\XML\Chunk|
     *         \SimpleSAML\XMLSecurity\XML\ds\X509Certificate|
     *         \SimpleSAML\XMLSecurity\XML\ds\X509IssuerSerial|
     *         \SimpleSAML\XMLSecurity\XML\ds\X509SubjectName|
     *         \SimpleSAML\XMLSecurity\XML\dsig11\X509Digest)[] $data
     */
    public function __construct(
        protected array $data,
    ) {
        Assert::maxCount($data, C::UNBOUNDED_LIMIT);
        Assert::allIsInstanceOfAny(
            $data,
            [Chunk::class, X509Certificate::class, X509IssuerSerial::class, X509SubjectName::class, X509Digest::class],
            InvalidArgumentException::class,
        );
    }


    /**
     * Collect the value of the data-property
     *
     * @return (\SimpleSAML\XML\Chunk|
     *          \SimpleSAML\XMLSecurity\XML\ds\X509Certificate|
     *          \SimpleSAML\XMLSecurity\XML\ds\X509IssuerSerial|
     *          \SimpleSAML\XMLSecurity\XML\ds\X509SubjectName|
     *          \SimpleSAML\XMLSecurity\XML\dsig11\X509Digest)[]
     */
    public function getData(): array
    {
        return $this->data;
    }


    /**
     * Convert XML into a X509Data
     *
     * @param \DOMElement $xml The XML element we should load
     * @return static
     *
     * @throws \SimpleSAML\XML\Exception\InvalidDOMElementException
     *   If the qualified name of the supplied element is wrong
     */
    public static function fromXML(DOMElement $xml): static
    {
        Assert::same($xml->localName, 'X509Data', InvalidDOMElementException::class);
        Assert::same($xml->namespaceURI, X509Data::NS, InvalidDOMElementException::class);

        $data = [];

        for ($n = $xml->firstChild; $n !== null; $n = $n->nextSibling) {
            if (!($n instanceof DOMElement)) {
                continue;
            } elseif ($n->namespaceURI === self::NS) {
                $data[] = match ($n->localName) {
                    'X509Certificate' => X509Certificate::fromXML($n),
                    'X509IssuerSerial' => X509IssuerSerial::fromXML($n),
                    'X509SubjectName' => X509SubjectName::fromXML($n),
                    default => new Chunk($n),
                };
            } elseif ($n->namespaceURI === C::NS_XDSIG11) {
                $data[] = match ($n->localName) {
                    'X509Digest' => X509Digest::fromXML($n),
                    default => new Chunk($n),
                };
            } else {
                $data[] = new Chunk($n);
                continue;
            }
        }

        return new static($data);
    }


    /**
     * Convert this X509Data element to XML.
     *
     * @param \DOMElement|null $parent The element we should append this X509Data element to.
     * @return \DOMElement
     */
    public function toXML(?DOMElement $parent = null): DOMElement
    {
        $e = $this->instantiateParentElement($parent);

        foreach ($this->getData() as $n) {
            $n->toXML($e);
        }

        return $e;
    }
}
