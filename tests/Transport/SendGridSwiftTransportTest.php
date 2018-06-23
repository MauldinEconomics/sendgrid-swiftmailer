<?php

namespace Wilczynski\Mailer\Tests;

use SendGrid\Response;
use PHPUnit\Framework\TestCase;
use Mockery;
use Swift_Message;
use Swift_TransportException;
use Wilczynski\Mailer\Transport\SendGridSwiftTransport;

/**
 * Class SendGridTransportSwiftTest
 * @package Wilczynski\Mailer\Tests
 */
class SendGridTransportSwiftTest extends TestCase
{
    /**
     * @throws \Swift_TransportException
     */
    public function testMessageSent()
    {
        $sendGrid = $this->createSendGrid();
        $sendGrid
            ->shouldReceive('send')
            ->once()
            ->andReturn(new Response(202));

        $eventDispatcher = $this->createEventDispatcher();

        $message = $this->createMessage();

        $transport = $this->createTransport($sendGrid, $eventDispatcher);

        $send = $transport->send($message);

        $this->assertEquals(6, $send);
    }

    /**
     * @throws \Swift_TransportException
     */
    public function testMessagesSentFail()
    {
        $this->expectException(Swift_TransportException::class);

        $sendGrid = $this->createSendGrid();
        $sendGrid
            ->shouldReceive('send')
            ->once()
            ->andReturn(new Response(400));

        $eventDispatcher = $this->createEventDispatcher();

        $message = $this->createMessage();

        $transport = $this->createTransport($sendGrid, $eventDispatcher);

        $transport->send($message);
    }

    /**
     * @return Mockery\Mock
     */
    private function createSendGrid()
    {
        return $this->getMockery('SendGrid')->shouldIgnoreMissing();
    }

    /**
     * @param $sendGrid
     * @param $eventDispatcher
     * @return SendGridSwiftTransport
     */
    private function createTransport($sendGrid, $eventDispatcher)
    {
        return new SendGridSwiftTransport($sendGrid, $eventDispatcher);
    }

    /**
     * @return Swift_Message
     */
    private function createMessage()
    {
        $message = (new Swift_Message())
            ->setSubject('...')
            ->setFrom(['martin@example.com' => 'Martin Wolf'])
            ->setTo($to = [
                'ben-to@example.com' => 'Ben (To)',
                'john-to@example.com' => 'John (To)',
            ])
            ->setCc($cc = [
                'ben-cc@example.com' => 'Ben (Cc)',
                'john-cc@example.com' => 'John (Cc)',
            ])
            ->setBcc($bcc = [
                'ben-bcc@example.com' => 'Ben (Bcc)',
                'john-bcc@example.com' => 'John (Bcc)',
            ])
            ->setBody('...');

        return $message;
    }

    /**
     * @return Mockery\Mock
     */
    protected function createEventDispatcher()
    {
        return $this->getMockery('Swift_Events_EventDispatcher')->shouldIgnoreMissing();
    }

    /**
     * @param $class
     * @return Mockery\MockInterface
     */
    protected function getMockery($class)
    {
        return Mockery::mock($class);
    }

    protected function tearDown()
    {
        Mockery::close();
    }
}
