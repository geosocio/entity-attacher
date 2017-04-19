<?php

namespace GeoSocio\Core\Utils\Dispatcher;

use GeoSocio\Core\Entity\Message\MessageInterface;

/**
 * Dispatcher Interface
 */
interface DispatcherInterface
{

    /**
     * Send an Email message.
     *
     * @param MessageInterface $message
     */
    public function send(MessageInterface $message) : bool;
}
