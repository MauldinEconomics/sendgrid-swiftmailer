<?php

namespace Wilczynski\Mailer;

use ReflectionClass;
use Swift_DependencyContainer;
use Wilczynski\Mailer\Transport\SendGridSwiftTransport;

/**
 * Class SendGridTransport
 * @package Wilczynski\Mailer
 */
class SendGridTransport
{
    /**
     * @param string $apiKey
     * @return object
     * @throws \ReflectionException
     */
    public static function create(string $apiKey)
    {
        Swift_DependencyContainer::getInstance()
            ->register('sendgrid')
            ->asNewInstanceOf('SendGrid')
            ->addConstructorValue($apiKey)
            ->register('transport.sendgrid')
            ->withDependencies(['sendgrid', 'transport.eventdispatcher']);


        $args = Swift_DependencyContainer::getInstance()->createDependenciesFor('transport.sendgrid');
        $rc = new ReflectionClass(SendGridSwiftTransport::class);
        $instance = $rc->newInstanceArgs($args);

        return $instance;
    }
}
