<?php

declare(strict_types=1);

namespace SimpleSAML\SOAP\XML\env_200305;

use DOMElement;
use SimpleSAML\Assert\Assert;
use SimpleSAML\XML\DOMDocumentFactory;
use SimpleSAML\XML\Exception\InvalidDOMElementException;
use SimpleSAML\XML\Exception\MissingElementException;
use SimpleSAML\XML\Exception\TooManyElementsException;
use SimpleSAML\XML\SchemaValidatableElementInterface;
use SimpleSAML\XML\SchemaValidatableElementTrait;

/**
 * Class representing a env:Fault element.
 *
 * @package simplesaml/xml-soap
 */
final class Fault extends AbstractSoapElement implements SchemaValidatableElementInterface
{
    use SchemaValidatableElementTrait;

    /**
     * Initialize a env:Fault
     *
     * @param \SimpleSAML\SOAP\XML\env_200305\Code $code
     * @param \SimpleSAML\SOAP\XML\env_200305\Reason $reason
     * @param \SimpleSAML\SOAP\XML\env_200305\Node|null $node
     * @param \SimpleSAML\SOAP\XML\env_200305\Role|null $role
     * @param \SimpleSAML\SOAP\XML\env_200305\Detail|null $detail
     */
    public function __construct(
        protected Code $code,
        protected Reason $reason,
        protected ?Node $node = null,
        protected ?Role $role = null,
        protected ?Detail $detail = null,
    ) {
    }


    /**
     * @return \SimpleSAML\SOAP\XML\env_200305\Code
     */
    public function getCode(): Code
    {
        return $this->code;
    }


    /**
     * @return \SimpleSAML\SOAP\XML\env_200305\Reason
     */
    public function getReason(): Reason
    {
        return $this->reason;
    }


    /**
     * @return \SimpleSAML\SOAP\XML\env_200305\Node|null
     */
    public function getNode(): ?Node
    {
        return $this->node;
    }


    /**
     * @return \SimpleSAML\SOAP\XML\env_200305\Role|null
     */
    public function getRole(): ?Role
    {
        return $this->role;
    }


    /**
     * @return \SimpleSAML\SOAP\XML\env_200305\Detail|null
     */
    public function getDetail(): ?Detail
    {
        return $this->detail;
    }


    /**
     * Convert XML into an Fault element
     *
     * @param \DOMElement $xml The XML element we should load
     * @return static
     *
     * @throws \SimpleSAML\XML\Exception\InvalidDOMElementException
     *   If the qualified name of the supplied element is wrong
     */
    public static function fromXML(DOMElement $xml): static
    {
        Assert::same($xml->localName, 'Fault', InvalidDOMElementException::class);
        Assert::same($xml->namespaceURI, Fault::NS, InvalidDOMElementException::class);

        $code = Code::getChildrenOfClass($xml);
        Assert::count($code, 1, 'Must contain exactly one Code', MissingElementException::class);

        $reason = Reason::getChildrenOfClass($xml);
        Assert::count($reason, 1, 'Must contain exactly one Reason', MissingElementException::class);

        $node = Node::getChildrenOfClass($xml);
        Assert::maxCount($node, 1, 'Cannot process more than one Node element.', TooManyElementsException::class);

        $role = Role::getChildrenOfClass($xml);
        Assert::maxCount($role, 1, 'Cannot process more than one Role element.', TooManyElementsException::class);

        $detail = Detail::getChildrenOfClass($xml);
        Assert::maxCount($detail, 1, 'Cannot process more than one Detail element.', TooManyElementsException::class);

        return new self(
            array_pop($code),
            array_pop($reason),
            empty($node) ? null : array_pop($node),
            empty($role) ? null : array_pop($role),
            empty($detail) ? null : array_pop($detail),
        );
    }


    /**
     * Convert this Fault to XML.
     *
     * @param \DOMElement|null $parent The element we should add this fault to.
     * @return \DOMElement This Fault-element.
     */
    public function toXML(?DOMElement $parent = null): DOMElement
    {
        $e = $this->instantiateParentElement($parent);

        $this->getCode()->toXML($e);
        $this->getReason()->toXML($e);

        $this->getNode()?->toXML($e);
        $this->getRole()?->toXML($e);

        if ($this->getDetail() !== null && !$this->getDetail()->isEmptyElement()) {
            $this->getDetail()->toXML($e);
        }

        // Dirty hack to get the namespaces in the right place. They cannot be in the env:Value element
        $doc = DOMDocumentFactory::create();
        $doc->appendChild($doc->importNode($e, true));

        return $doc->documentElement;
    }
}
