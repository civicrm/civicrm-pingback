<?php
/*
 * ATTENTION: You need to copy this file to 'config.php'
 * and personalize it for your environment!
 */

$dbhost = 'mysql_host';
$dbport = 3306;              /* NOTE: If you want to omit, then NULL better than '', but check for yourself. */
$dbname = 'pingbackcm_71whg';
$user   = 'mysql_user';
$pass   = 'mysql_pass';

/**
 * @var $verbose
 *   Enable detailed logging. To track request handling, follow the
 *   PHP error log.
 */
$verbose = FALSE;

// How should we notify web-frontend about new releases
global $clearCache;
$clearCache = 'echo "FIXME: Define clearCache options in config.php"';
// $clearCache = 'curl -H "Content-Type: application/json" -X POST --data \'{"key": "FIXME"}\' https://civicrm.org/flushcache';
