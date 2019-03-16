<?php
/*
 * This file is part of the Stomp package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

require __DIR__ . '/../vendor/autoload.php';

use Stomp\Client;
use Stomp\StatefulStomp;


if (!extension_loaded('pcntl')) {
	echo 'You need the pcntl extension to run this example.', PHP_EOL;
	exit(1);
}

// was a signal received?
$stopSignalReceived = false;
// at what point was the signal dispatcher called?
$signalDispatchSource = '';


// configure signal handling
$signalHandler = function () use (&$stopSignalReceived) {
	$stopSignalReceived = true;
};
pcntl_signal(SIGUSR1, $signalHandler);
pcntl_signal(SIGINT, $signalHandler);


$client = new Client('tcp://127.0.0.1:61010');
// we set a read timeout of 10 seconds, so that a read would return after 10 seconds
$client->getConnection()->setReadTimeout(10);

// we add a connection wait callback,
// this makes sure we don't need to wait until the read timeout was reached
// -> You can remove this and see that the signal handling will take place delayed
$client->getConnection()->setWaitCallback(
	function () use (&$signalDispatchSource, &$stopSignalReceived) {
		// set source of processing
		$signalDispatchSource = 'connection wait callback';
		// dispatch
		pcntl_signal_dispatch();
		if ($stopSignalReceived) {
			// when stop was requested, we directly return false
			// causing the connection to not wait until the timeout is reached
			return false;
		}
	}
);


// normal client setup...
$stomp = new StatefulStomp($client);
$stomp->subscribe('/queue/tests');

echo 'Now send a signal to the process, ex. SIGUSR1 with CTRL+C.', PHP_EOL;

$time = @time();
while (@time() - $time < 20 && !$stopSignalReceived) {
	$frame = $stomp->read();
	// here we simulate a signal handler inside our loop.
	$signalDispatchSource = 'message processing loop';
	pcntl_signal_dispatch();
}
$stomp->unsubscribe();
echo 'End';

echo 'Stop Signal Received: ', ($stopSignalReceived ? 'Yes' : 'No'), PHP_EOL;
if ($stopSignalReceived) {
	echo 'Stop Signal Processing Triggered from: ', $signalDispatchSource, PHP_EOL;
}


