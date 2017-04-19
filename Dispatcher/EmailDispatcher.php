<?php

namespace GeoSocio\Core\Utils\Dispatcher;

use GeoSocio\Core\Entity\Message\EmailMessage;
use GeoSocio\Core\Entity\Message\MessageInterface;
use SendGrid\Content;
use SendGrid\Mail;
use SendGrid\Email;

/**
 * Email Dispatcher
 */
class EmailDispatcher implements DispatcherInterface
{

    /**
     * @var \SendGrid
     */
    protected $sendGrid;

    /**
     * Creates an email dispatcher.
     *
     * @param \SendGrid $sendGrid
     */
    public function __construct(\SendGrid $sendGrid)
    {
        $this->sendGrid = $sendGrid;
    }

    /**
     * {@inheritdoc}
     */
    public function send(MessageInterface $message) : bool
    {

        $mail = $this->convertMessage($message);

        $response = $this->sendGrid->client->mail()->send()->post($mail);

        return $response->statusCode() == 202;
    }

    /**
     * Convert Message Entity to SendGrid Mail.
     *
     * @param EmailMessage $message
     */
    protected function convertMessage(EmailMessage $message) : Mail
    {
        // @TODO Make this configurable!
        $from = new Email(null, "geosocio@geosoc.io");
        $to = new Email(null, $message->getTo());
        $content = new Content("text/plain", $message->getTextString());

        return new Mail($from, $message->getSubject(), $to, $content);
    }
}
