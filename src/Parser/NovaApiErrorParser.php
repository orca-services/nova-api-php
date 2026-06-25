<?php

namespace OrcaServices\NovaApi\Parser;

use Exception;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\ServerException;
use OrcaServices\NovaApi\Exception\NovaApiUnauthorizedException;
use OrcaServices\NovaApi\Result\NovaApiErrorList;
use OrcaServices\NovaApi\Xml\XmlDocument;
use UnexpectedValueException;

/**
 * Class.
 */
final class NovaApiErrorParser
{
    /**
     * @var NovaApiSoapErrorParser
     */
    private $novaSoapError;

    /**
     * Constructor.
     *
     * @param NovaApiSoapErrorParser $novaSoapError The novaSoapError
     */
    public function __construct(
        NovaApiSoapErrorParser $novaSoapError
    ) {
        $this->novaSoapError = $novaSoapError;
    }

    /**
     * Create human readable exception from exception.
     *
     * @param Exception $exception The Exception object
     *
     * @return UnexpectedValueException|NovaApiUnauthorizedException
     */
    public function createGeneralException(Exception $exception)
    {
        $message = $this->getExceptionMessage($exception);

        if ($exception instanceof ServerException && $exception->getCode() === 401) {
            return new NovaApiUnauthorizedException($message, $exception->getCode(), $exception);
        }

        return new UnexpectedValueException($message, $exception->getCode(), $exception);
    }

    /**
     * Get Client Exception Message as string.
     *
     * @param Exception $exception Exception
     *
     * @return string message as string
     */
    public function getExceptionMessage(Exception $exception): string
    {
        $errors = new NovaApiErrorList();

        if ($exception instanceof ClientException) {
            $message = sprintf('Client error [%s] %s', $exception->getCode(), $exception->getMessage());
            $errors = $errors->withError('0', $message);
        }

        if ($exception instanceof ServerException) {
            $message = sprintf('Server error [%s] %s', $exception->getCode(), $exception->getMessage());
            $errors = $errors->withError('0', $message);

            $response = $exception->getResponse();
            $body = (string)$response->getBody();

            if (strpos($body, '<?xml') === 0 || strpos($body, '<SOAP') === 0) {
                $xmlError = XmlDocument::createFromXmlString($body);
                $errors = $this->novaSoapError->getSoapErrors($xmlError, $errors);
            }
        }

        // Default exceptions
        $errors = $errors->withError($exception->getCode(), $exception->getMessage());

        $message = '';
        foreach ($errors->getErrors() as $index => $error) {
            $message .= sprintf("%s. %s\n", (int)$index + 1, $error->getMessage());
        }

        return trim($message);
    }
}
