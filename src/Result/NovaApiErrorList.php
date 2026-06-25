<?php

namespace OrcaServices\NovaApi\Result;

/**
 * Class.
 */
final class NovaApiErrorList
{
    /**
     * @var NovaApiError[] errors
     */
    private $errors = [];

    /**
     * Add error.
     *
     * @param string|int $code The code
     * @param string $message The message
     * @param array|null $details The details
     *
     * @return NovaApiErrorList self
     */
    public function withError($code, string $message, ?array $details = null): NovaApiErrorList
    {
        $clone = clone $this;

        $clone->errors[] = new NovaApiError((string)$code, $message, $details);

        return $clone;
    }

    /**
     * Get errors.
     *
     * @return NovaApiError[] errors
     */
    public function getErrors(): array
    {
        return $this->errors;
    }
}
