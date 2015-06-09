<?php

#Classes load
require_once __DIR__ . '/vendor/autoload.php';

#Constants
define ('CHECK_EXIST', 'CHECK_EXIST');
define ('CHECK_NOT_NULL', 'CHECK_NOT_NULL');
define ('CHECK_POSITIVE', 'CHECK_POSITIVE');
define ('CHECK_NOT_NEGATIVE', 'CHECK_NOT_NEGATIVE');
define ('CHECK_NOT_ZERO', 'CHECK_NOT_ZERO');
define ('CHECK_STRING_NOT_EMPTY', 'CHECK_STRING_NOT_EMPTY');
define ('CHECK_ARRAY_EMPTY', 'CHECK_ARRAY_EMPTY');
define ('CHECK_ARRAY_NOT_EMPTY', 'CHECK_ARRAY_NOT_EMPTY');
define ('CHECK_DATETIME_FORMAT', 'CHECK_DATETIME_FORMAT');
define ('CHECK_DATE_FORMAT', 'CHECK_DATE_FORMAT');

#Config load
$GLOBALS['config'] = require_once(dirname(__FILE__) . '/config/config.php');
$GLOBALS['server'] = require_once(dirname(__FILE__) . '/config/server.php');

#Command line arguments load
$GLOBALS['args'] = simple_scan_args($_SERVER['argv'], array_keys($GLOBALS['server']));

#Replace data in server config with cli arguments if necessary
foreach($GLOBALS['server'] as $key => $value)
{
    if(isset($GLOBALS['args'][$key]))
        $GLOBALS['server'][$key] = $GLOBALS['args'][$key];
}

#Greeting
echo "\n\n( ͡° ͜ʖ ͡°) starting 2fingers...\n\n";
