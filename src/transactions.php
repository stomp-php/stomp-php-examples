<?php
require __DIR__ . '/../vendor/autoload.php';
/**
 *
 * Copyright (C) 2009 Progress Software, Inc. All rights reserved.
 * http://fusesource.com
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 * http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

use Stomp\Client;
use Stomp\Network\Connection;
use Stomp\StatefulStomp;
use Stomp\Transport\Message;

// make a connection
$connection = new Connection('tcp://localhost:61613');
$connection->setReadTimeout(1);
$stomp = new StatefulStomp(new Client($connection));

// subscribe to the queue
$stomp->subscribe('/queue/transactions', null, 'client');

// try to send some messages
$stomp->begin();
for ($i = 1; $i < 3; $i++) {
    $stomp->send('/queue/transactions', new Message($i));
}
// if we abort transaction, messages will not be sent
$stomp->abort();

// now send some messages for real
$stomp->begin();
echo "Sent messages {\n";
for ($i = 1; $i < 5; $i++) {
    echo "\t$i\n";
    $stomp->send('/queue/transactions', new Message($i));
}
echo "}\n";
// they will be available for consumers after commit
$stomp->commit();

// try to receive some messages
$stomp->begin();
$messages = array();
for ($i = 1; $i < 3; $i++) {
    $msg = $stomp->read();
    array_push($messages, $msg);
    $stomp->ack($msg);
}
// of we abort transaction, we will "rollback" out acks
$stomp->abort();

$stomp->begin();
// so we need to ack received messages again
// before we can receive more (prefetch = 1)
if (count($messages) != 0) {
    foreach ($messages as $msg) {
        $stomp->ack($msg);
    }
}
// now receive more messages
for ($i = 1; $i < 3; $i++) {
    $msg = $stomp->read();
    $stomp->ack($msg);
    array_push($messages, $msg);
}
// commit all acks
$stomp->commit();


echo "Processed messages {\n";
foreach ($messages as $msg) {
    echo "\t$msg->body\n";
}
echo "}\n";

//ensure there are no more messages in the queue
$frame = $stomp->read();

if ($frame === false) {
    echo "No more messages in the queue\n";
} else {
    echo "Warning: some messages still in the queue: $frame\n";
}
