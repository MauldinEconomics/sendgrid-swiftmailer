<?php

namespace Wilczynski\Mailer\Tests;

use PHPUnit\Framework\TestCase;
use Wilczynski\Mailer\SendGridTransport;
use Wilczynski\Mailer\Transport\SendGridSwiftTransport;

/**
 * Class SendGridTransportTest
 * @package Wilczynski\Mailer\Tests
 */
class SendGridTransportTest extends TestCase
{
    public function testSendGridTransportFactory()
    {
        $apiKey = 'test_0123456789';

        $object = SendGridTransport::create($apiKey);

        $this->assertInstanceOf(SendGridSwiftTransport::class, $object);
    }
}