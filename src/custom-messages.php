<?php
/*
 * This file is part of the Stomp package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */


require __DIR__ . '/../vendor/autoload.php';

use Stomp\Client;
use Stomp\Network\Connection;
use Stomp\StatefulStomp;
use Stomp\Transport\Message;

/**
 * Class MyCustomMessage
 *
 * - Will be used for send and receive type safe messages.
 * - It's important that the `body` property is up to date when the message is going to be transmitted.
 *   Overriding `__toString` assures that.
 * - If you're only into receiving a type safe message, you should only extend from Frame. It also simplifies the constructor.
 */
class MyCustomMessage extends Message
{
    /**
     * @var string
     */
    private $user;

    /**
     * @var DateTime
     */
    private $time;

    /**
     * MyCustomMessage constructor.
     * @param string $user
     * @param DateTime $time
     */
    public function __construct($user, DateTime $time)
    {
        $this->user = $user;
        $this->time = $time;
        parent::__construct(
            $this->generateBody(),
            ['content-type' => 'text/MyCustomMessage'] // we build our message resolver based on a custom content-type
        );
    }


    /**
     * @return string
     */
    public function getUser()
    {
        return $this->user;
    }

    /**
     * @return DateTime
     */
    public function getTime()
    {
        return $this->time;
    }


    /**
     * Updates the current message body, so that it can be used for transmission.
     *
     * @return string
     */
    private function generateBody()
    {
        return $this->user . '|' . $this->time->getTimestamp() . '|' . $this->time->getTimezone()->getName();
    }

    /**
     * Update the message string representation, as this is what is going to be transmitted.
     *
     * @return string
     */
    public function __toString()
    {
        // we just update the body property and leave the default logic
        $this->body = $this->generateBody();
        return parent::__toString();
    }
}

// setup a connection
$connection = new Connection('tcp://127.0.0.1:61010');

// the frame parser is part of the connection logic, it makes uses of a FrameFactory
// we inject a custom frame resolver inside that factory
$connection->getParser()->getFactory()->registerResolver(
    function ($command, array $headers, $body) {
        // we get the current command, the frame headers and the content
        // if we see our specific content-type this resolver takes over and returns a specific frame instance
        if ($command === 'MESSAGE' && isset($headers['content-type']) && $headers['content-type'] == 'text/MyCustomMessage') {
            if (preg_match('/^(.+)\|(\d+)\|(.+)$/', $body, $matches)) {
                $date = DateTime::createFromFormat('U', intval($matches[2]), new DateTimeZone($matches[3]));
                $date->setTimezone(new DateTimeZone($matches[3]));
                $user = $matches[1];
                return new MyCustomMessage($user, $date);
            }
        }
        // not needed - just to clarify that another (or the default) resolver will be used than
        return null;
    }
);

// all clients (even the simple client) will make use of our frame resolver
$stomp = new StatefulStomp(new Client($connection));
$stomp->subscribe('/queue/examples');

// send a custom message (the resolver is not used for this, the message itself contains the logic for transmitting)
$stomp->send(
    '/queue/examples',
    new MyCustomMessage('grumpy stompy', new DateTime('yesterday 18:00', new DateTimeZone('Africa/Windhoek')))
);

// here our resolver will be used to initialize a new instance of "MyCustomMessage" otherwise we would receive only "Message"
$message = $stomp->read();

/** @var $message MyCustomMessage */
echo get_class($message), PHP_EOL;
echo sprintf('Message from %s (%s)', $message->getUser(), $message->getTime()->format('Y-m-d H:i:s e'));

$stomp->unsubscribe();



