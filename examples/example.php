<?php

use Wilczynski\Mailer\SendGridTransport;

require_once '../vendor/autoload.php';

$apiKey = 'SENDGRID_API_KEY';

// Create the Transport
$transport = SendGridTransport::create($apiKey);

// Create the Mailer using SendGrid Transport
$mailer = new Swift_Mailer($transport);

// Create a Swift Message
$message = (new Swift_Message())
    ->setSubject('Cheese')
    ->setFrom(['tom@jerry.com' => 'Tom & Jerry'])
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
    ->setReplyTo([])
    ->setContentType('text/html')
    ->setBody('...');

// Send the message
$result = $mailer->send($message);
