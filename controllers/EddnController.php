<?php

use Eddn\MarketData;
use JsonSchema\Validator;

require_once('vendor/autoload.php');
require_once('helpers/validate_functions.php');

/**
 *  Configuration
 */
$relayEDDN              = 'tcp://eddn.edcd.io:9500';
$timeoutEDDN            = 600000;

/**
 * START
 */

$context    = new ZMQContext();
$subscriber = $context->getSocket(ZMQ::SOCKET_SUB);

$subscriber->setSockOpt(ZMQ::SOCKOPT_SUBSCRIBE, "");
$subscriber->setSockOpt(ZMQ::SOCKOPT_RCVTIMEO, $timeoutEDDN);

while (true) {
    try {
        $subscriber->connect($relayEDDN);

        while (true) {
            $message = $subscriber->recv();

            if ($message === false) {
                $subscriber->disconnect($relayEDDN);
                break;
            }

            $message    = zlib_decode($message);
            $json       = $message;

            // Validate
            $validator = new Validator();
            $market_data = new MarketData();

            if (validate_commodities($validator, $json)) {
                $market_data->addMarketData($json);
            }

            //fwrite(STDOUT, $json . PHP_EOL);
        }
    } catch (ZMQSocketException $e) {
        fwrite(STDOUT, 'ZMQSocketException: ' . $e . PHP_EOL);
        sleep(10);
    }
}

// Exit correctly
exit(0);
