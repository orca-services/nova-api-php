<?php

namespace OrcaServices\NovaApi\Parameter;

/**
 * Data.
 */
final class NovaCreateServicesParameter extends NovaIdentifierParameter
{
    /**
     * NOVA offer UUID.
     *
     * @var string
     */
    public $novaOfferId = '';

    /**
     * NOVA / SBB customer ID (uuid).
     *
     * @var string
     */
    public $tkId;
}
