<?php

namespace OrcaServices\NovaApi\Xml;

use Cake\Chronos\Chronos;
use DOMAttr;
use DOMDocument;
use DOMElement;
use DOMException;
use DOMNameSpaceNode;
use DOMNode;
use DOMNodeList;
use DOMXPath;
use InvalidArgumentException;
use OrcaServices\NovaApi\Exception\InvalidXmlException;

/**
 * XML DOM reader.
 */
final class XmlDocument
{
    /**
     * @var DOMXPath The xpath
     */
    private $xpath;

    /**
     * The constructor.
     *
     * @param DOMXPath $xpath The DOM xpath
     */
    public function __construct(DOMXPath $xpath)
    {
        $this->xpath = $xpath;
    }

    /**
     * Create instance.
     *
     * @param string $xmlContent The xml content
     *
     * @throws DOMException
     *
     * @return self The document
     */
    public static function createFromXmlString(string $xmlContent): self
    {
        $dom = new DOMDocument();
        $dom->preserveWhiteSpace = false;
        $dom->formatOutput = true;
        $success = $dom->loadXML($xmlContent);

        if ($success === false) {
            throw new DOMException('The XML content is not well-formed');
        }

        return new self(new DOMXPath($dom));
    }

    /**
     * Create instance.
     *
     * @param DOMDocument $dom The dom document
     *
     * @return self The document
     */
    public static function createFromDom(DOMDocument $dom): self
    {
        $dom->preserveWhiteSpace = false;
        $dom->formatOutput = true;

        return new self(new DOMXPath($dom));
    }

    /**
     * Check if namespace exists.
     *
     * @param string $namespace The namespace name
     *
     * @return bool Status
     */
    public function existsNamespace(string $namespace): bool
    {
        $nodes = $this->xpath->query('//namespace::' . $namespace);

        return !empty($nodes) && $nodes->length !== 0;
    }

    /**
     * Get value of the first node.
     *
     * @param string $expression The xpath expression
     * @param DOMElement|DOMNode|null $contextNode The optional context node
     *
     * @throws InvalidXmlException
     *
     * @return string The node value
     */
    public function getNodeValue(string $expression, $contextNode = null): string
    {
        $nodes = $this->queryNodes($expression, $contextNode);

        if ($nodes->length === 0
            || !($nodes->item(0) instanceof DOMElement)
            || !($nodes->item(0) instanceof DOMNode)
        ) {
            throw new InvalidXmlException(sprintf('XML DOM node [%s] not found.', $expression));
        }

        return $nodes->item(0)->nodeValue ?? '';
    }

    /**
     * Get value of the first node.
     *
     * @param string $expression The xpath expression
     * @param DOMXPath $xpath The xpath object
     * @param DOMElement|DOMNode|null $contextNode The optional context node
     *
     * @return string|null The node value
     */
    private function findSingleNodeValue(string $expression, DOMXPath $xpath, $contextNode = null): ?string
    {
        if ($contextNode === null) {
            $node = $xpath->query($expression);
        } else {
            $node = $xpath->query($expression, $contextNode);
        }

        if (empty($node)
            || $node->length === 0
            || !($node->item(0) instanceof DOMElement)
            || !($node->item(0) instanceof DOMNode)
        ) {
            return null;
        }

        return $node->item(0)->nodeValue;
    }

    /**
     * Get value of the first node.
     *
     * @param string $expression The xpath expression
     * @param DOMElement|DOMNode|null $contextNode The optional context node
     *
     * @return string|null The node value
     */
    public function findNodeValue(string $expression, $contextNode = null): ?string
    {
        $nodes = $this->queryNodes($expression, $contextNode);

        if ($nodes->length === 0
            || !($nodes->item(0) instanceof DOMElement)
            || !($nodes->item(0) instanceof DOMNode)
        ) {
            return null;
        }

        return $nodes->item(0)->nodeValue;
    }

    /**
     * Get value of the first node.
     *
     * @param string $expression The xpath expression
     * @param DOMElement|DOMNode|null $contextNode The optional context node
     *
     * @throws InvalidXmlException
     *
     * @return string The node value
     */
    public function getAttributeValue(string $expression, $contextNode = null): string
    {
        $nodes = $this->queryNodes($expression, $contextNode);

        if ($nodes->length === 0) {
            throw new InvalidXmlException(sprintf('XML DOM attribute [%s] not found.', $expression));
        }

        $attribute = $nodes->item(0);
        if (!$attribute instanceof DOMAttr) {
            throw new InvalidXmlException(sprintf('XML DOM attribute [%s] not found.', $expression));
        }

        return $attribute->nodeValue ?? '';
    }

    /**
     * Query nodes.
     *
     * @param string $expression The xpath expression
     * @param DOMElement|DOMNode|null $contextNode The optional context node
     *
     * @throws InvalidXmlException
     *
     * @return DOMNodeList The node list
     */
    public function queryNodes(string $expression, $contextNode = null): DOMNodeList
    {
        if ($contextNode === null) {
            $nodes = $this->xpath->query($expression);
        } else {
            $nodes = $this->xpath->query($expression, $contextNode);
        }

        // The expression is malformed or the context node is invalid
        if ($nodes === false) {
            throw new InvalidXmlException(sprintf('Invalid Xpath expression: %s', $expression));
        }

        return $nodes;
    }

    /**
     * Query nodes.
     *
     * @param string $expression The xpath expression
     * @param DOMElement|DOMNode|null $contextNode The optional context node
     *
     * @throws InvalidXmlException
     *
     * @return DOMNode The node
     */
    public function queryFirstNode(string $expression, $contextNode = null): DOMNode
    {
        $nodes = $this->queryNodes($expression, $contextNode);

        if ($nodes->length === 0) {
            throw new InvalidXmlException(sprintf('Node not found by expression: %s', $expression));
        }

        return $this->getFirstNode($nodes);
    }

    /**
     * Get value of the first node.
     *
     * @param string $expression The xpath expression
     * @param DOMElement|DOMNode|null $contextNode The optional context node
     *
     * @return string|null The node value
     */
    public function findAttributeValue(string $expression, $contextNode = null)
    {
        $nodes = $this->queryNodes($expression, $contextNode);

        if ($nodes->length === 0 || !($nodes instanceof DOMNodeList)) {
            return null;
        }

        $attribute = $nodes->item(0);
        if (!$attribute instanceof DOMAttr) {
            return null;
        }

        return $attribute->nodeValue;
    }

    /**
     * Get node value as Chronos object.
     *
     * @param string $expression The xpath expression
     * @param DOMElement|DOMNode|null $contextNode The optional context node
     *
     * @throws InvalidXmlException
     *
     * @return Chronos The chronos instance
     */
    public function getNodeValueAsChronos(string $expression, $contextNode = null): Chronos
    {
        $value = $this->findSingleNodeValue($expression, $this->xpath, $contextNode);

        if ($value === null) {
            throw new InvalidXmlException(sprintf('DOM node not found: %s', $expression));
        }

        return $this->createChronosFromXsDateTime($value);
    }

    /**
     * Create Chronos from XML date time string with timezone offset.
     *
     * @param string $dateTime Date Time string
     *
     * @throws InvalidArgumentException
     *
     * @return Chronos The Chronos object
     */
    public function createChronosFromXsDateTime(string $dateTime): Chronos
    {
        $timestamp = strtotime($dateTime);

        if ($timestamp === false) {
            throw new InvalidArgumentException(sprintf('Invalid date: %s', $dateTime));
        }

        return Chronos::createFromTimestamp($timestamp);
    }

    /**
     * Get first node from DOMNodeList.
     *
     * @param DOMNodeList $nodes The DOMNodeList
     *
     * @throws InvalidXmlException
     *
     * @return DOMNode The first node
     */
    public function getFirstNode(DOMNodeList $nodes): DOMNode
    {
        if (empty($nodes->length)) {
            throw new InvalidXmlException('No DOM nodes found');
        }
        $node = $nodes->item(0);

        if ($node === null) {
            throw new InvalidXmlException('First DOM node not found');
        }

        return $node;
    }

    /**
     * Get DOMDocument.
     *
     * @return DOMDocument The DOMDocument
     */
    public function getDom(): DOMDocument
    {
        return $this->xpath->document;
    }

    /**
     * Get xpath.
     *
     * @return DOMXPath The xpath
     */
    public function getXpath(): DOMXPath
    {
        return $this->xpath;
    }

    /**
     * Get XML content.
     *
     * @return string The xml content
     */
    public function getXml(): string
    {
        return (string)$this->xpath->document->saveXML();
    }

    /**
     * Remove all namespaces from DOM.
     *
     * @return self The new instance
     */
    public function withoutNamespaces(): self
    {
        $dom = new DOMDocument();
        $dom->formatOutput = true;

        $domSource = clone $this->xpath->document;
        $domSource->formatOutput = true;

        $dom->loadXML(preg_replace('/\sxmlns="(.*?)"/', '', $domSource->saveXML() ?: '') ?: '');
        $xpath = new DOMXPath($dom);

        /** @var DOMNameSpaceNode|DOMAttr $namespaceNode */
        foreach ($xpath->query('//namespace::*') ?: [] as $namespaceNode) {
            if (!isset($namespaceNode->nodeName)) {
                continue;
            }

            $prefix = str_replace('xmlns:', '', $namespaceNode->nodeName);
            $nodes = $xpath->query("//*[namespace::{$prefix}]") ?: [];

            /** @var DOMElement $node */
            foreach ($nodes as $node) {
                $namespaceUri = $node->lookupNamespaceUri($prefix);
                $node->removeAttributeNS($namespaceUri, $prefix);
            }
        }

        // Important: Reload document to remove invalid xpath references from old dom
        $dom->loadXML((string)$dom->saveXML());

        return new self(new DOMXPath($dom));
    }
}
