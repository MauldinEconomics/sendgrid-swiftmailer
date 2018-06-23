<?php

namespace Wilczynski\Mailer\Transport;

use SendGrid;
use SendGrid\Mail\Bcc;
use SendGrid\Mail\Cc;
use SendGrid\Mail\From;
use SendGrid\Mail\Mail;
use SendGrid\Mail\ReplyTo;
use SendGrid\Mail\To;
use Swift_Events_EventDispatcher;
use Swift_Events_EventListener;
use Swift_Events_SendEvent;
use Swift_Mime_SimpleMessage;
use Swift_Transport;
use Swift_TransportException;

/**
 * Class SendGridSwiftTransport
 * @package Wilczynski\Mailer\Transport
 */
class SendGridSwiftTransport implements Swift_Transport
{
    /** @var SendGrid $sendGrid */
    private $sendGrid;

    /** @var Swift_Events_EventDispatcher $eventDispatcher */
    private $eventDispatcher;

    /** @var bool $status */
    private $status;

    /**
     * SendGridSwiftTransport constructor.
     * @param SendGrid $sendGrid
     * @param Swift_Events_EventDispatcher $eventDispatcher
     */
    public function __construct(SendGrid $sendGrid, Swift_Events_EventDispatcher $eventDispatcher)
    {
        $this->sendGrid = $sendGrid;
        $this->eventDispatcher = $eventDispatcher;
    }

    /**
     * Test if this Transport mechanism has started.
     *
     * @return bool
     */
    public function isStarted()
    {
        return $this->status;
    }

    /**
     * Start this Transport mechanism.
     */
    public function start()
    {
        $this->status = true;
    }

    /**
     * Stop this Transport mechanism.
     */
    public function stop()
    {
        $this->status = false;
    }

    /**
     * @return bool
     */
    public function ping()
    {
        return true;
    }

    /**
     * @param Swift_Mime_SimpleMessage $message
     * @param $failedRecipients
     * @return int
     * @throws Swift_TransportException
     */
    public function send(Swift_Mime_SimpleMessage $message, &$failedRecipients = null)
    {
        $failedRecipients = (array)$failedRecipients;

        $event = $this->eventDispatcher->createSendEvent($this, $message);

        if ($event) {
            $this->eventDispatcher->dispatchEvent($event, 'beforeSendPerformed');

            if ($event->bubbleCancelled()) {
                return 0;
            }
        }

        $email = $this->createMail($message);

        $response = $this->sendGrid->send($email);

        if ($response->statusCode() !== 202) {
            $this->throwException(new Swift_TransportException($response->body(), $response->statusCode()));
        }

        if ($event) {
            $event->setResult(Swift_Events_SendEvent::RESULT_SUCCESS);
            $event->setFailedRecipients($failedRecipients);
            $this->eventDispatcher->dispatchEvent($event, 'sendPerformed');
        }

        $count = count((array)$message->getTo())
            + count((array)$message->getCc())
            + count((array)$message->getBcc());

        return $count;
    }

    /**
     * @todo Add attachment support
     *
     * @param Swift_Mime_SimpleMessage $message
     * @return Mail
     */
    private function createMail(Swift_Mime_SimpleMessage $message)
    {
        $emailAddressesFrom = $this->mapEmailAddresses((array)$message->getFrom(), From::class);
        $emailAddressesTo = $this->mapEmailAddresses((array)$message->getTo(), To::class);
        $emailAddressesCc = $this->mapEmailAddresses((array)$message->getCc(), Cc::class);
        $emailAddressesBcc = $this->mapEmailAddresses((array)$message->getBcc(), Bcc::class);
        $emailAddressesReplayTo = $this->mapEmailAddresses((array)$message->getReplyTo(), ReplyTo::class);

        $email = new Mail();
        $email->setSubject($message->getSubject());
        $email->setFrom(reset($emailAddressesFrom));
        $email->addTos($emailAddressesTo);
        $email->addCcs($emailAddressesCc);
        $email->addBccs($emailAddressesBcc);
        $email->addContent($message->getContentType(), $message->getBody());

        if ($emailAddressesReplayTo) {
            $email->setReplyTo(reset($emailAddressesReplayTo));
        }

        return $email;
    }

    /**
     * @param array $emailAddresses
     * @param string $className
     * @return array
     */
    private function mapEmailAddresses(array $emailAddresses, $className)
    {
        return array_map(function ($emailAddress, $name) use ($className) {
            return new $className($emailAddress, $name);
        }, array_keys($emailAddresses), $emailAddresses);
    }

    /**
     * Register a plugin in the Transport.
     *
     * @param Swift_Events_EventListener $plugin
     */
    public function registerPlugin(
        Swift_Events_EventListener $plugin
    ) {
        $this->eventDispatcher->bindEventListener($plugin);
    }

    /**
     * @param Swift_TransportException $e
     * @throws Swift_TransportException
     */
    protected function throwException(Swift_TransportException $e)
    {
        $event = $this->eventDispatcher->createTransportExceptionEvent($this, $e);

        if ($event) {
            $this->eventDispatcher->dispatchEvent($event, 'exceptionThrown');
            if (!$event->bubbleCancelled()) {
                throw $e;
            }
        } else {
            throw $e;
        }
    }
}
