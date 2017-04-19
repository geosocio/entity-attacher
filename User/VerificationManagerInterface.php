<?php

namespace GeoSocio\Core\Utils\User;

/**
 * Verification Manager Interface.
 */
interface VerificationManagerInterface
{
    /**
     * Determines if verification exists.
     *
     * @param string $type
     *
     * @return bool
     */
    public function hasVerification(string $type) : bool;

    /**
     * Gets the verification by type.
     *
     * @param string $type
     *
     * @throws \LogicException
     *
     * @return VerificationInterface
     */
    public function getVerification(string $type) : VerificationInterface;

    /**
     * Adds a verification to the manager.
     *
     * @param VerificationInterface $verification
     * @param string $type
     *
     * @return VerificationManagerInterface
     */
    public function addVerification(VerificationInterface $verification, string $type);
}
