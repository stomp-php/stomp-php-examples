# Stomp PHP - Examples

This project contains examples how to work with the [stomp-php client](https://github.com/stomp-php/stomp-php).

This examples should help you finding a good entry point how to use the library. For production code you might need to 
combine different examples and for sure rewrite the code.    

## Running Examples

- All examples include comments, please read them and modify them as required.  
- Examples are located in `src` folder. 
- Before running them, be sure you have installed this library properly (`composer install`).
- You need running brokers.
   - You can use the docker based brokers from the stomp-php travis setup
      - Start `(cd vendor/stomp-php/stomp-php; travisci/bin/start.sh;)`
      - Stop `(cd vendor/stomp-php/stomp-php; travisci/bin/stop.sh;)` 
   - Or go the long way and configure a broker by your own ex. ActiveMQ broker (recommended version 5.5.0 or above) with [Stomp connector enabled] (http://activemq.apache.org/stomp.html).

## FAQ Examples

Here you find examples how to solve typical problems.

### Connection Probing

How can I determine if the connection is still usable?

- We recommend using the server heartbeat approach. 
  The server is requested to send signals within a defined interval. 
  The client checks for those signals and can close the connection when the delay exceeds the interval.
  [ServerAliveObserver Example](src/heartbeats_server.php)
- You can also use the client heartbeat emitter to send signals towards the server. 
  This is a little bit more complicated, because you need to ensure that the message processing logic is fast enough.
  [HeartbeatEmitter Example](src/heartbeats_client.php)
  
It's also possible to use them both at the same time.

### Signal Processing (PCNTL)

How can I interrupt the process of waiting for new messages?

- You can use `pcntl_signal_dispatch` but there are some special things to consider. [PCNTL Signal Example](src/pcntl_signal_handling.php) 

### Step by Step: Certificate based Authentication

https://github.com/rethab/php-stomp-cert-example

## Other Examples

- [Stateful Client](src/stateful.php)
- [Binary Data](src/binary.php)
- [Failover Connecting](src/connectivity.php)
- [Type Safe Messages / Custom Messages](src/custom-messages.php)
- [Durable Subscriber](src/durable.php)
- [Authentication (Login)](src/security.php)
- [Transactions](src/transactions.php)
- [Map](src/transformation.php)


## Licence

[Apache License Version 2.0](http://www.apache.org/licenses/LICENSE-2.0)
