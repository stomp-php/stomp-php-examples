<?php
/*
 * This file is part of the Stomp package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

require __DIR__ . '/../vendor/autoload.php';

use Stomp\Client;
use Stomp\Network\Observer\HeartbeatEmitter;
use Stomp\StatefulStomp;
use Stomp\Transport\Message;

// first we define a typical client.
$client = new Client('tcp://127.0.0.1:61010');

// we want to signal the server that we're going to send alive signals within an interval of 500ms
$client->setHeartbeat(500);

// in order to simplify the process of sending such signals we use a heartbeat emitter
$emitter = new HeartbeatEmitter($client->getConnection());
// and add it to our connection observers
$client->getConnection()->getObservers()->addObserver($emitter);
// we must assure that no operation blocks longer than 500 ms
// in fact we must assure that we'll send data within less than 500ms so our read timeout must be lower as well
$connection->setReadTimeout(0, 250000); // 250ms


/**
 * Now the client is ready to be used, you can use it directly or use StatefulStomp as a wrapper.
 *
 * The following lines show how the emitter works.
 */
$delayed = function () use ($emitter) {
	echo 'Client delayed: ', ($emitter->isDelayed() ? 'Yes' : 'No'), PHP_EOL;
};

// code works as usual (as long we don't delay)
$stomp = new StatefulStomp($client);
$stomp->subscribe('/queue/examples');

// we can even see if heartbeats are enabled now, and what interval is used
if (!$emitter->isEnabled()) {
	echo 'The Server is not supporting hearbeats.';
	exit(1);
} else {
	// it could be that the server requests a lower interval, the lowest always succeeds.
	// the target interval is lower than the agreement, this helps us to send the signal inside the interval
	echo sprintf('The Client tries to send heart beats every %d ms.', $emitter->getInterval() * 1000), PHP_EOL;

}

$stomp->begin();
$stomp->send('/queue/examples', new Message('Hello World!'));
$stomp->commit();

for ($i = 0; $i < 10; $i++) {
	// show if we're already delayed, could happen from time to time
    $delayed();
    // now we do only passive operations (this code only listens, never sends data)
    $stomp->read();
    // and sleep some time...
	echo 'Sleeping 50 ms...', PHP_EOL;
    usleep(50000); // 50ms
}

$delayed();
echo 'Now we simulate a code execution on client side that takes longer than the heartbeat interval.', PHP_EOL;
echo 'Sleeping 800 ms...', PHP_EOL;

// now we sleep for 800ms which results in a delay and missing signals to server side
// (server will now close connection, as it expects the client is dead or unreachable)
usleep(800000);

try {
    // this should fail with an exception
	$delayed();
    $stomp->read();
    // here the emitter could change back to "not delayed" but the server most likely already decided to drop the client
    $delayed();
    $stomp->unsubscribe();
    echo "Test failed, maybe the server has a huge grace time? Try increasing the usleep call.";
    exit(1);
} catch (\Exception $exception) {
    echo 'Read operation failed. This is caused by the missing client signal.', PHP_EOL;
	echo 'As a follow up the server closes the connection at SOME POINT. ', PHP_EOL;
    echo get_class($exception), PHP_EOL;
    echo $exception->getMessage(), PHP_EOL;
    exit(0);
}

