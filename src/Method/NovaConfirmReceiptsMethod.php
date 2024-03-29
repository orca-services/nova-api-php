<?php

namespace OrcaServices\NovaApi\Method;

use DomainException;
use DOMDocument;
use DOMElement;
use Exception;
use OrcaServices\NovaApi\Parameter\NovaConfirmReceiptsParameter;
use OrcaServices\NovaApi\Parser\NovaApiErrorParser;
use OrcaServices\NovaApi\Parser\NovaMessageParser;
use OrcaServices\NovaApi\Result\NovaConfirmReceiptsResult;
use OrcaServices\NovaApi\Result\NovaServiceItem;
use OrcaServices\NovaApi\Soap\NovaApiSoapAction;
use OrcaServices\NovaApi\Xml\XmlDocument;

/**
 * SOAP method.
 *
 * 4.2. Klang
 */
final class NovaConfirmReceiptsMethod implements NovaMethod
{
    /**
     * @var NovaApiSoapAction
     */
    private $novaSoapAction;

    /**
     * @var NovaApiErrorParser
     */
    private $novaErrorParser;

    /**
     * @var NovaMessageParser
     */
    private $novaMessageParser;

    /**
     * NovaSearchPartnerMethod constructor.
     *
     * @param NovaApiSoapAction $novaSoapAction The novaSoapAction
     * @param NovaApiErrorParser $novaErrorParser The novaErrorParser
     * @param NovaMessageParser $novaMessageParser The message parser
     */
    public function __construct(
        NovaApiSoapAction $novaSoapAction,
        NovaApiErrorParser $novaErrorParser,
        NovaMessageParser $novaMessageParser
    ) {
        $this->novaSoapAction = $novaSoapAction;
        $this->novaErrorParser = $novaErrorParser;
        $this->novaMessageParser = $novaMessageParser;
    }

    /**
     * 4.2 Klang.
     *
     * Service: https://confluence-ext.sbb.ch/display/NOVAUG/bestaetigeProduktion
     *
     * @param NovaConfirmReceiptsParameter $parameter The parameters
     *
     * @throws Exception
     *
     * @return NovaConfirmReceiptsResult The result data
     */
    public function confirmReceipts(NovaConfirmReceiptsParameter $parameter): NovaConfirmReceiptsResult
    {
        // The SOAP endpoint url
        $url = $this->novaSoapAction->getNovaSalesServiceUrl();

        // The SOAP action (http header)
        $soapAction = $this->novaSoapAction->getSoapAction('vertrieb', 'bestaetigeProduktion');

        // The SOAP content (http body)
        $body = $this->createRequestBody($parameter);

        try {
            $xmlContent = $this->novaSoapAction->invokeSoapRequest($url, $soapAction, $body);
            $xml = XmlDocument::createFromXmlString($xmlContent);

            return $this->createResult($xml);
        } catch (Exception $exception) {
            throw $this->novaErrorParser->createGeneralException($exception);
        }
    }

    /**
     * Create SOAP body XML content.
     *
     * @param NovaConfirmReceiptsParameter $parameter The parameters
     *
     * @return string The xml content
     */
    private function createRequestBody(NovaConfirmReceiptsParameter $parameter): string
    {
        $dom = new DOMDocument('1.0', 'utf-8');
        $dom->formatOutput = true;

        $dom->appendChild($dom->createComment(' powered by Barakuda '));

        $envelope = $dom->createElement('soapenv:Envelope');
        $dom->appendChild($envelope);
        $envelope->setAttribute('xmlns:soapenv', 'http://schemas.xmlsoap.org/soap/envelope/');

        $soapHeader = $dom->createElement('soapenv:Header');
        $envelope->appendChild($soapHeader);

        $body = $dom->createElement('soapenv:Body');
        $envelope->appendChild($body);

        $method = $dom->createElement('bestaetigeProduktion');
        $body->appendChild($method);

        $this->novaSoapAction->appendMethodNamespaces($method);

        $methodRequest = $dom->createElement('ns18:bestaetigeProduktionRequest');
        $method->appendChild($methodRequest);

        $methodRequest->setAttribute('ns18:transaktionsVerhalten', 'ROLLBACK_ON_ERROR');
        $methodRequest->setAttribute('ns18:fachlogLevel', 'OFF');

        $this->novaSoapAction->appendDomClientIdentifier($dom, $methodRequest, $parameter, 'ns18:');
        $this->novaSoapAction->appendDomCorrelationContext($dom, $methodRequest, $parameter, 'ns18:');

        $serviceRequest = $dom->createElement('ns18:leistungsId', $parameter->novaServiceId);
        $methodRequest->appendChild($serviceRequest);

        return (string)$dom->saveXML();
    }

    /**
     * Create result object.
     *
     * @param XmlDocument $xml The xml document
     *
     * @throws DomainException
     *
     * @return NovaConfirmReceiptsResult The mapped result
     */
    private function createResult(XmlDocument $xml): NovaConfirmReceiptsResult
    {
        $result = new NovaConfirmReceiptsResult();

        $xml = $xml->withoutNamespaces();

        // Find and append all messages
        foreach ($this->novaMessageParser->findNovaMessages($xml) as $message) {
            $result->addMessage($message);
        }

        // Root node
        $serviceResponseNode = $xml->queryFirstNode('/Envelope/Body/bestaetigeProduktionResponse');
        $serviceNodes = $xml->queryNodes('bestaetigeProduktionResponse/leistung', $serviceResponseNode);

        /** @var DOMElement $serviceNode */
        foreach ($serviceNodes as $serviceNode) {
            $serviceItem = new NovaServiceItem();

            $serviceId = $serviceNode->getAttribute('leistungsId');

            if (empty($serviceId)) {
                throw new DomainException(sprintf('SBB-NOVA service ID not found'));
            }

            $serviceItem->serviceId = $serviceId;
            $serviceItem->serviceReference = $serviceNode->getAttribute('leistungsReferenz');
            $serviceItem->serviceStatus = $serviceNode->getAttribute('leistungsStatus');
            $serviceItem->productNumber = $serviceNode->getAttribute('produktNummer');
            $serviceItem->tkId = $xml->findNodeValue('//tkid', $serviceNode);
            $serviceItem->price = $xml->getAttributeValue('verkaufsPreis/geldBetrag/@betrag', $serviceNode);
            $serviceItem->currency = $xml->getAttributeValue('verkaufsPreis/geldBetrag/@waehrung', $serviceNode);
            $serviceItem->vatAmount = $xml->getAttributeValue('verkaufsPreis/mwstAnteil/@betrag', $serviceNode);
            $serviceItem->vatPercent = $xml->getAttributeValue('verkaufsPreis/mwstAnteil/@mwstSatz', $serviceNode);

            $result->services[] = $serviceItem;
        }

        return $result;
    }
}
