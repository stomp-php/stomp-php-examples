<?php
/*
 * This file is part of the Stomp package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

require __DIR__ . '/../vendor/autoload.php';

use Stomp\Client;
use Stomp\Network\Observer\Exception\HeartbeatException;
use Stomp\Network\Observer\ServerAliveObserver;
use Stomp\StatefulStomp;
use Stomp\Transport\Message;

// first we define a typical client.
$client = new Client('tcp://127.0.0.1:61010');

// we want the server to send us signals every 500ms
$client->setHeartbeat(0, 500);

// in order to simplify the process of checking for server signals we use the ServerAliveObserver
$observer = new ServerAliveObserver();
$client->getConnection()->getObservers()->addObserver($observer);

/**
 * Now the client is ready to be used, you can use it directly or use StatefulStomp as a wrapper.
 *
 * The following lines show how the emitter works.
 */
$delayed = function () use ($observer) {
	echo 'Server delayed: ', ($observer->isDelayed() ? 'Yes' : 'No'), PHP_EOL;
};

// code works as usual
$stomp = new StatefulStomp($client);
$stomp->subscribe('/queue/examples');

// we can even see if heartbeats are enabled now, and what interval is used
if (!$observer->isEnabled()) {
	echo 'The Server is not supporting hearbeats.';
	exit(1);
} else {
	// it could be that the server requests a lower interval, the lowest always succeeds.
	// when waiting for signals the grace time is added, that's the reason you see a higher value here
	echo sprintf('The Server should send us signals every %d ms.', $observer->getInterval() * 1000), PHP_EOL;

}

$stomp->begin();
$stomp->send('/queue/examples', new Message('Hello World!'));
$stomp->commit();

echo 'The client will now try to read messages from server for 10 seconds.', PHP_EOL;
echo 'Stop the server or drop the connection to see the expected exception.', PHP_EOL;
$started = @time();

try {
	while (@time() - $started < 10) {
		// show if we're already delayed, could happen from time to time
	    $delayed();
	    // now we do only passive operations (this code only listens, never sends data)
	    $stomp->read();
	}
	$stomp->unsubscribe();
	echo 'Nothing special happened, the server was sending signals as requested.', PHP_EOL;
} catch (HeartbeatException $heartbeatException) {
	echo 'The server failed to send us heartbeats within the defined interval.', PHP_EOL;
	echo $heartbeatException->getMessage(), PHP_EOL;
}


