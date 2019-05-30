<?php
/**
 * @file: Collect and store site useage stats from pingbacks
 *        Display current stable version
 */
require_once 'vendor/autoload.php';
use GeoIp2\Database\Reader;

// ------ Configuration options ------
$user = $pass = FALSE;
$dbhost = 'localhost';
$dbport = '';
$dbname = 'stats';
$verbose = FALSE;
require_once 'config.php';

// ----- Main ------
$link = @mysqli_connect($dbhost, $user, $pass, $dbname, $dbport);

verbose_log("Received request: " . print_r(['METHOD' => $_SERVER['REQUEST_METHOD'], 'GET' => $_GET, 'POST' => $_POST], 1));

if (!$link) {
  error_log("Cannot record request: Database not available");
}
elseif (!empty($_REQUEST['hash'])) {
  if (flood_control_check()) {
    if (!empty($_POST['hash'])) {
      verbose_log("Record POST request");
      process_post_request();
    }
    else {
      verbose_log("Record GET request");
      process_get_request();
    }
  }
  else {
    verbose_log("Cannot record request: Recent data already recorded.");
  }
}
else {
  // FIXME: Record some kind of nonce/fallback record so we can measure these; then make the log a bit quieter.
  error_log("Cannot record request: No hash provided.");
}

create_response(\Symfony\Component\HttpFoundation\Request::createFromGlobals())->send();

/**
 * Optionally log
 * @param $message
 */
function verbose_log($message) {
  if (!empty($GLOBALS['verbose'])) {
    error_log($message);
  }
}

/**
 * Make sure we don't get pingbacks from a site more than once a day
 *
 * @return bool - true if this site hasn't pinged us today
 */
function flood_control_check() {
  global $link;

  $sql = "SELECT id FROM `stats`
    WHERE `hash` = '" . mysqli_real_escape_string($link, $_REQUEST['hash']) . "'
    AND `time` > '" . date_format(date_create('-1 day'), 'Y-m-d H:i:s') . "'";
  $res = mysqli_query($link, $sql);
  if (mysqli_num_rows($res)) {
    return FALSE;
  }
  return TRUE;
}

/**
 * CiviCRM 4.3 and later sends a POST request in array format
 * Which includes stats on installed components/extensions
 */
function process_post_request() {
  // Save to stats table
  $id = insert_stats();

  // Save to entities and extensions tables
  foreach (array('entities', 'extensions') as $table) {
    if (!empty($_POST[$table])) {
      insert_children($table, $_POST[$table], $id);
    }
  }
}

/**
 * CiviCRM 4.2 and earlier sent all params in the url of a GET request
 * It did not report on installed components/extensions
 */
function process_get_request() {
  // Save to stats table
  $id = insert_stats();

  // Save to entities table
  $entities = array(
    'Activity', 'Case', 'Contact', 'Contribution', 'ContributionPage',
    'ContributionProduct', 'Discount', 'Event', 'Friend', 'Grant', 'Mailing', 'Membership',
    'MembershipBlock', 'Participant', 'Pledge', 'PledgeBlock', 'PriceSetEntity',
    'Relationship', 'UFGroup', 'Widget',
  );
  $params = array();
  // Reformat legacy-style params
  foreach ($entities as $name) {
    if (isset($_GET[$name])) {
      $params[] = array(
        'name' => $name,
        'size' => $_GET[$name],
      );
    }
  }
  // Run query if there is data to insert
  if ($params) {
    insert_children('entities', $params, $id);
  }
}

/**
 * Insert the primary record into the stats table
 * @return int - primary record id
 */
function insert_stats() {
  global $link;

  $fields = get_fields('stats');
  try {
    $reader = new Reader('/usr/share/GeoIP/GeoLite2-Country.mmdb');
    $geoloc = $reader->country($_SERVER['REMOTE_ADDR']);
    $_REQUEST['geoip_isoCode'] = $geoloc->country->isoCode;
  }
  catch (Exception $e) {
    // Nothing to do, we just don't want a fatal error.
  }
  $params = format_params($fields, $_REQUEST);
  $sql = insert_clause('stats', $params) . 'VALUES (' . implode(', ', $params) . ')';
  if (!mysqli_query($link, $sql)) {
    file_put_contents('/tmp/latest.log', mysqli_error($link), FILE_APPEND);
  }
  return mysqli_insert_id($link);
}

/**
 * Insert the child records
 * @param string $table - table name
 * @param array $data
 * @param int $id - primary record id
 */
function insert_children($table, $data, $id) {
  global $link;

  $fields = get_fields($table);
  $sql = insert_clause($table, $fields);
  $prefix = 'VALUES';
  foreach ($data as $input) {
    $input['stat_id'] = $id;
    $sql .= "$prefix (" . implode(', ', format_params($fields, $input, TRUE)) . ')';
    $prefix = ',';
  }
  if (!mysqli_query($link, $sql)) {
    file_put_contents('/tmp/latest.log', mysqli_error($link), FILE_APPEND);
  }
}

/**
 * Returns available fields and their data type from table schema
 * @param string $table
 * @return array
 */
function get_fields($table) {
  global $link;

  $info = array();
  $res = mysqli_query($link, "DESCRIBE $table");
  while ($row = mysqli_fetch_array($res)) {
    // Skip autofilled fields
    if ($row['Extra'] == 'auto_increment' || $row['Default'] == 'CURRENT_TIMESTAMP') {
      continue;
    }
    $info[$row['Field']] = strpos($row['Type'], 'int') !== FALSE ? 'int' : 'text';
  }
  return $info;
}

/**
 * Build a list of sanitized params ready for insert
 *
 * @param array $fields
 * @param array $input
 * @param bool $pad
 * @return array
 */
function format_params($fields, $input, $pad = FALSE) {
  global $link;

  $params = array();
  foreach ($fields as $field => $type) {
    if (isset($input[$field])) {
      if ($type == 'int') {
        $params[$field] = (int) $input[$field];
      }
      else {
        $params[$field] = "'" . mysqli_real_escape_string($link, $input[$field]) . "'";
      }
    }
    elseif ($pad) {
      $params[$field] = 'NULL';
    }
  }
  return $params;
}

/**
 * Build the insert clause of the sql query
 * @param string $table
 * @param array $fields
 * @return string
 */
function insert_clause($table, $fields) {
  return "INSERT INTO `$table` (`" . implode('`, `', array_keys($fields)) . '`) ';
}

/**
 * Output version info in requested format
 *
 * @param \Symfony\Component\HttpFoundation\Request $request
 * @return \Symfony\Component\HttpFoundation\Response
 */
function create_response($request) {
  \Pingback\Date::set($request->query->get('today', date('Y-m-d')));

  $format = $request->query->getAlnum('format', 'plain');
  $formatHandlers = [
    'json' => ['\Pingback\Report\FullJsonReport', 'generate'],
    'summary' => ['\Pingback\Report\SummaryReport', 'generate'],
    'devPreview' => ['\Pingback\Report\DevPreviewReport', 'generate'],
    'devPreviewHtml' => ['\Pingback\Report\DevPreviewReport', 'generate'],
    'devPreviewCsv' => ['\Pingback\Report\DevPreviewReport', 'generate'],
    'devPreviewJson' => ['\Pingback\Report\DevPreviewReport', 'generate'],
    'plain' => ['\Pingback\Report\BasicReport', 'generate'],
    '' => ['\Pingback\Report\BasicReport', 'generate'],
  ];

  $response = NULL;
  if (isset($formatHandlers[$format])) {
    $response = call_user_func($formatHandlers[$format], $request);
  }
  if (!$response) {
    $response = \Symfony\Component\HttpFoundation\Response::create('Internal server error: failed to prepare response.', 500);
  }

  return $response;
}
