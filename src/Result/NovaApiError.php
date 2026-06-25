<?php

namespace OrcaServices\NovaApi\Result;

/**
 * Data.
 */
final class NovaApiError
{
    /**
     * @var string
     */
    private $code;

    /**
     * @var string
     */
    private $message;

    /**
     * @var array|null
     */
    private $details;

    /**
     * Constructor.
     *
     * @param string $code The code
     * @param string $message The message
     * @param array|null $details The details
     */
    public function __construct(string $code, string $message, ?array $details = null)
    {
        $this->code = $code;
        $this->message = $message;
        $this->details = $details;
    }

    /**
     * Get value.
     *
     * @return string code
     */
    public function getCode(): string
    {
        return $this->code;
    }

    /**
     * Get value.
     *
     * @return string message
     */
    public function getMessage(): string
    {
        return $this->message;
    }

    /**
     * Get value.
     *
     * @return array|null details
     */
    public function getDetails()
    {
        return $this->details;
    }
}
