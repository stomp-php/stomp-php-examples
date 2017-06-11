<?php
/*
 * This file is part of the Stomp package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

require __DIR__ . '/../vendor/autoload.php';

use Stomp\Client;
use Stomp\Exception\ConnectionException;
use Stomp\Network\Connection;
use Stomp\Network\Observer\Heartbeat\Emitter;
use Stomp\StatefulStomp;
use Stomp\Transport\Message;

$connection = new Connection('tcp://127.0.0.1:61010');

// add a heartbeat emitter to our connection
$emitter = new Emitter($connection);
$connection->getObservers()->addObserver($emitter);

// configure client
$client = new Client($connection);

// we will send heartbeats or frames every 500 ms
$client->setHeartbeat(500);
// so we must assure that no operation blocks longer than 500 ms
// in fact we must assure that we'll send data within less than 500 ms
$connection->setReadTimeout(0, 250000);



// code works as usual (as long we don't delay)
$stomp = new StatefulStomp($client);
$stomp->subscribe('/queue/examples');

// we can even see if heartbeats are enabled now, and what interval is used
var_dump($emitter->isEnabled());
var_dump($emitter->getInterval()); // calculated interval


$stomp->begin();
$stomp->send('/queue/examples', new Message('Hello World!'));
$stomp->commit();

// check for last beat time
 var_dump($emitter->getLastbeat());
// now we do only passive operations
for ($i = 0; $i < 10; $i++) {
    // beat timestamp will change
    var_dump($emitter->getLastbeat());
    $stomp->read();
    // and sleep some time...
    usleep(50000);
}

var_dump($emitter->getLastbeat());

// now we sleep - we don't send any signal
usleep(800000);
try {
    // this should fail with an exception
    $stomp->read();
    $stomp->unsubscribe();
    echo "Test failed, maybe the server don't support heartbeats?";
} catch (ConnectionException $exception) {
    echo "Read operation failed as expected, due connection (beat timeout) issue.";
}

