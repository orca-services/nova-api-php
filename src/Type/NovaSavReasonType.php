<?php

namespace OrcaServices\NovaApi\Type;

/**
 * Type.
 */
final class NovaSavReasonType
{
    /**
     * Return before the 1st day of validity (20 CHF).
     */
    public const RETURN_BEFORE_1VALIDITY = 'RUECKGABE_VOR_1GELTUNGSTAG';

    /**
     * Partly used (10 CHF).
     */
    public const PARTLY_USED = 'TEILWEISE_BENUTZT';
}
