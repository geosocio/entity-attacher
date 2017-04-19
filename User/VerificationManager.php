<?php

namespace GeoSocio\Core\Utils\User;

/**
 * Verification Manager.
 */
class VerificationManager implements VerificationManagerInterface
{
    /**
     * @var VerificationInterface[]
     */
    protected $verifications = [];

    /**
     * {@inheritdoc}
     */
    public function hasVerification(string $type) : bool
    {
        return array_key_exists($type, $this->verifications);
    }

    /**
     * {@inheritdoc}
     */
    public function getVerification(string $type) : VerificationInterface
    {
        if (!array_key_exists($type, $this->verifications)) {
            throw new \LogicException('Verification does not exist.');
        }

        return $this->verifications[$type];
    }

    /**
     * {@inheritdoc}
     */
    public function addVerification(VerificationInterface $verification, string $type) : self
    {
        $this->verifications[$type] = $verification;

        return $this;
    }
}
