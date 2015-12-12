# Stomp PHP - Examples

This project contains examples how to work with the [stomp-php client](https://github.com/stomp-php/stomp-php).

## Running Examples

Examples are located in `src/` folder. Before running them, be sure
you have installed this library properly (`composer update`) and you have started ActiveMQ broker
(recommended version 5.5.0 or above) with [Stomp connector enabled]
(http://activemq.apache.org/stomp.html).

You can start by running

    cd src
    php connectivity.php

Also, be sure to check comments in the particular examples for some special
configuration steps (if needed).

## Step by Step: Certificate based Authentication

https://github.com/rethab/php-stomp-cert-example

## Licence

[Apache License Version 2.0](http://www.apache.org/licenses/LICENSE-2.0)