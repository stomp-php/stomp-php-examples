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
use Stomp\SimpleStomp;
use Stomp\Transport\Bytes;

// make a connection
$stomp = new SimpleStomp(new Client('tcp://localhost:61613'));

// send a message to the queue
$body = 'test';
$bytesMessage = new Bytes($body);
$stomp->send('/queue/test', $bytesMessage);
echo 'Sending message: ';
print_r($body . "\n");

$stomp->subscribe('/queue/test', 'binary-sub-test', 'client-individual');
$msg = $stomp->read();

// extract
if ($msg != null) {
    echo 'Received message: ';
    print_r($msg->body . "\n");
    // mark the message as received in the queue
    $stomp->ack($msg);
} else {
    echo "Failed to receive a message\n";
}

$stomp->unsubscribe('/queue/test', 'binary-sub-test');