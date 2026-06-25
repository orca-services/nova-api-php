<?php

namespace OrcaServices\NovaApi\Soap;

use Cake\Chronos\Chronos;
use DOMDocument;
use DOMElement;
use OrcaServices\NovaApi\Client\NovaHttpClient;
use OrcaServices\NovaApi\Configuration\NovaApiConfiguration;
use OrcaServices\NovaApi\Factory\NovaHttpClientFactory;
use OrcaServices\NovaApi\Parameter\NovaIdentifierParameter;
use UnexpectedValueException;

/**
 * Class.
 */
final class NovaApiSoapAction
{
    /**
     * @var NovaHttpClientFactory HTTP client factory
     */
    private $httpClientFactory;

    /**
     * @var NovaHttpClient|null HTTP client
     */
    private $httpClient;

    /**
     * @var int Max lifetime of the token. Refresh after this time.
     */
    private $httpClientMaxLifetime = 300;

    /**
     * @var Chronos|null Max lifetime of the token. Refresh after this time.
     */
    private $httpClientLoginAt;

    /**
     * @var NovaApiConfiguration
     */
    private $configuration;

    /**
     * Constructor.
     *
     * @param NovaHttpClientFactory $httpClientFactory The httpClient factory
     * @param NovaApiConfiguration $configuration The novaVersion
     */
    public function __construct(NovaHttpClientFactory $httpClientFactory, NovaApiConfiguration $configuration)
    {
        $this->httpClientFactory = $httpClientFactory;
        $this->configuration = $configuration;
    }

    /**
     * Build the request URI with the given request options.
     *
     * NOVA System: VertriebsService = /novaan/*
     *
     * @return string the request URI with the nova version
     */
    public function getNovaSalesServiceUrl(): string
    {
        return str_replace(
            '{novaVersion}',
            $this->configuration->getNovaApiVersion(),
            '/novaan/vertrieb/public/{novaVersion}/VertriebsService'
        );
    }

    /**
     * Build the request URI with the given request options.
     *
     * NOVA System: SwisspassService = /novaan/*
     *
     * https://confluence-ext.sbb.ch/display/NOVAUG/NOVA+Umgebungsdetails
     *
     * @return string the request URI with the nova version
     */
    public function getNovaSwisspassServiceUrl(): string
    {
        return str_replace(
            '{novaVersion}',
            $this->configuration->getNovaApiVersion(),
            '/novasp/swisspass/public/{novaVersion}/SwissPassService'
        );
    }

    /**
     * Build the request URI with the given request options.
     *
     * NOVA System: GeschaeftspartnerService = /novagp/*
     *
     * @return string the request URI with the nova version
     *
     * @example https://nova-test-ws.sbb.ch/novagp/geschaeftspartner/public/v13/GeschaeftspartnerService
     */
    public function getNovaBusinessPartnerServiceUrl(): string
    {
        return str_replace(
            '{novaVersion}',
            $this->configuration->getNovaApiVersion(),
            '/novagp/geschaeftspartner/public/{novaVersion}/GeschaeftspartnerService'
        );
    }

    /**
     * Build the request URI with the given request options.
     *
     * @param string $resourceUri The resource URI with {novaVersion} placeholder
     *
     * @return string the request URI with the nova version
     */
    public function getNovaVersionUrl($resourceUri): string
    {
        return str_replace(
            '{novaVersion}',
            $this->configuration->getNovaApiVersion(),
            $resourceUri
        );
    }

    /**
     * Return service namespace url.
     *
     * @param string $uriPath The uri path
     *
     * @return string The full namespace url
     */
    public function getServiceNamespace(string $uriPath): string
    {
        // NOVA namespace base url
        $serviceBaseUrl = $this->getNovaVersionUrl('http://nova.voev.ch/services/{novaVersion}');

        return $serviceBaseUrl . '/' . $uriPath;
    }

    /**
     * Get SOAP Webservice schema action (for the HTTP request body).
     *
     * @param string $novaSystem The nova system (vertrieb, geschaeftspartner, ...)
     * @param string $soapMethod Soap method name
     *
     * @return string The action name
     */
    public function getSoapAction(string $novaSystem, string $soapMethod): string
    {
        $namespace = sprintf('http://nova.voev.ch/services/{novaVersion}/%s/%s', $novaSystem, $soapMethod);

        return $this->getNovaVersionUrl($namespace);
    }

    /**
     * Add TU specific parameters to the DOM.
     *
     * @param DOMDocument $dom The dom
     * @param DOMElement $targetElement The target dom element
     * @param NovaIdentifierParameter $parameter The parameter
     * @param string $namespace The namespace
     *
     * @return void
     */
    public function appendDomClientIdentifier(
        DOMDocument $dom,
        DOMElement $targetElement,
        NovaIdentifierParameter $parameter,
        string $namespace = ''
    ) {
        $clientIdentifier = $dom->createElement($namespace . 'clientIdentifier');
        $targetElement->appendChild($clientIdentifier);

        $clientIdentifier->setAttribute('base:leistungsVermittler', $parameter->serviceAgent);
        $clientIdentifier->setAttribute('base:kanalCode', $parameter->channelCode);
        $clientIdentifier->setAttribute('base:verkaufsStelle', $parameter->pointOfSale);
        $clientIdentifier->setAttribute('base:vertriebsPunkt', $parameter->distributionPoint);
        $clientIdentifier->setAttribute('base:verkaufsGeraeteId', $parameter->saleDeviceId);
    }

    /**
     * Add correlation context to DOM.
     *
     * @param DOMDocument $dom The dom
     * @param DOMElement $targetElement The target dom element
     * @param NovaIdentifierParameter $parameter The parameter
     * @param string $namespace The namespace
     *
     * @return void
     */
    public function appendDomCorrelationContext(
        DOMDocument $dom,
        DOMElement $targetElement,
        NovaIdentifierParameter $parameter,
        string $namespace = ''
    ) {
        $correlationContext = $dom->createElement($namespace . 'correlationKontext');
        $targetElement->appendChild($correlationContext);

        $element = $dom->createElement('base:correlationId');
        $element->appendChild($dom->createTextNode($parameter->correlationId));
        $correlationContext->appendChild($element);
    }

    /**
     * Append default SOAP method namespaces.
     *
     * @param DOMElement $method The method node
     *
     * @return void
     */
    public function appendMethodNamespaces(DOMElement $method)
    {
        $method->setAttribute('xmlns:ns20', 'http://nova.voev.ch/services/internal/leistungnotification');
        $method->setAttribute('xmlns:ns19', $this->getServiceNamespace('inkasso'));
        $method->setAttribute('xmlns:ns18', $this->getServiceNamespace('vertrieb'));
        $method->setAttribute('xmlns:nova-leistungnotiz', $this->getServiceNamespace('leistungnotiz'));
        $method->setAttribute('xmlns:ns16', $this->getServiceNamespace('vertrag'));
        $method->setAttribute('xmlns:vertriebsbase', $this->getServiceNamespace('vertriebsbase'));
        $method->setAttribute('xmlns:ns14', 'http://nova.voev.ch/services/internal');
        $method->setAttribute('xmlns:vs', $this->getServiceNamespace('vertrieb/vertriebsstammdaten'));
        $method->setAttribute('xmlns:nova-protokoll', $this->getServiceNamespace('vertrieb/protokoll'));
        $method->setAttribute(
            'xmlns:nova-erneuerungsinfo',
            $this->getServiceNamespace('vertrieb/erneuerungsinfo')
        );
        $method->setAttribute(
            'xmlns:offlinemanagement',
            $this->getServiceNamespace('vertrieb/offlinemanagement')
        );
        $method->setAttribute(
            'xmlns:nova-monitoring',
            $this->getServiceNamespace('internal/monitoring')
        );
        $method->setAttribute('xmlns:nova-preisauskunft', $this->getServiceNamespace('preisauskunft'));
        $method->setAttribute('xmlns:base', $this->getServiceNamespace('base'));
        $method->setAttribute('xmlns:ns6', $this->getServiceNamespace('fachlichervertrag'));
        $method->setAttribute('xmlns:novasp-swisspass', $this->getServiceNamespace('vertragskonto'));
        $method->setAttribute('xmlns:nova-vertragskonto', $this->getServiceNamespace('swisspass'));
        $method->setAttribute('xmlns:novasp-swisspass', $this->getServiceNamespace('swisspass'));
        $method->setAttribute('xmlns:novagp', $this->getServiceNamespace('geschaeftspartner'));
        $method->setAttribute('xmlns', $this->getServiceNamespace('vertrieb'));
        $method->setAttribute('xmlns:novavt-vertrag', $this->getServiceNamespace('vertragbase'));
    }

    /**
     * Invoke SOAP request.
     *
     * @param string $url The endpoint url
     * @param string $soapAction The SOAP action method
     * @param string $body The xml payload
     *
     * @throws UnexpectedValueException
     *
     * @return string The SOAP response body
     */
    public function invokeSoapRequest(string $url, string $soapAction, string $body): string
    {
        $this->loginClient();

        if (!$this->httpClient) {
            throw new UnexpectedValueException('The http client is not defined');
        }

        $response = $this->httpClient->post(
            $url,
            [
                'body' => $body,
                'headers' => [
                    'SOAPAction' => $soapAction,
                ],
            ]
        );

        return (string)$response->getBody();
    }

    /**
     * Login the http client. Creates a new login after maxLifetime seconds.
     *
     * @return void
     */
    private function loginClient()
    {
        $now = Chronos::now();

        $loginTimestamp = $this->httpClientLoginAt ? $this->httpClientLoginAt->getTimestamp() : 0;

        if ($this->httpClient === null
            || $now->getTimestamp() - $loginTimestamp >= $this->httpClientMaxLifetime
        ) {
            $this->httpClientLoginAt = $now;
            $this->httpClient = $this->httpClientFactory->createLoggedInHttpClient();
        }
    }
}
