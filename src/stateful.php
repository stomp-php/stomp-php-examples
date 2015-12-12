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

$connection = new Connection('tcp://127.0.0.1:61010');

$stomp = new StatefulStomp(new Client($connection));


$stomp->subscribe('/queue/examples');
$stomp->begin();
$stomp->send('/queue/examples', new Message('Hello World!'));
$stomp->commit();

echo $stomp->read()->body, PHP_EOL;

$stomp->unsubscribe();



