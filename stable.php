<?php
/**
 * @file: Collect and store site useage stats from pingbacks
 *        Display current stable version
 */
require_once 'vendor/autoload.php';
use GeoIp2\Database\Reader;

$user = $pass = false;
require_once 'config.php';
$link = @mysql_connect('localhost', $user, $pass, TRUE);

if ($link && @mysql_select_db('stats', $link) && !empty($_REQUEST['hash'])) {
  if (flood_control_check()) {
    if (!empty($_POST['hash'])) {
      process_post_request();
    }
    else {
      process_get_request();
    }
  }
}
print send_version_info();

/**
 * Make sure we don't get pingbacks from a site more than once a day
 *
 * @return bool - true if this site hasn't pinged us today
 */
function flood_control_check() {
  $sql = "SELECT id FROM `stats`
    WHERE `hash` = '" . mysql_real_escape_string($_REQUEST['hash']) . "'
    AND `time` > '" . date_format(date_create('-1 day'), 'Y-m-d H:i:s')."'";
  $res = mysql_query($sql);
  if (mysql_num_rows($res)) {
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
  } catch (Exception $e) {
    // Nothing to do, we just don't want a fatal error.
  }
  $params = format_params($fields, $_REQUEST);
  $sql = insert_clause('stats', $params) . 'VALUES (' . implode(', ', $params) . ')';
  mysql_query($sql, $link);
  return mysql_insert_id($link);
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
  mysql_query($sql, $link);
}

/**
 * Returns available fields and their data type from table schema
 * @param string $table
 * @return array
 */
function get_fields($table) {
  global $link;
  $info = array();
  $res = mysql_query("DESCRIBE $table", $link);
  while ($row = mysql_fetch_array($res)) {
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
  $params = array();
  foreach ($fields as $field => $type) {
    if (isset($input[$field])) {
      if ($type == 'int') {
        $params[$field] = (int) $input[$field];
      }
      else {
        $params[$field] = "'" . mysql_real_escape_string($input[$field]) . "'";
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
 * @return string
 */
function send_version_info() {
  // Load version info
  $rawJson = file_get_contents('versions.json');
  $versionInfo = json_decode($rawJson, TRUE);
  ksort($versionInfo, SORT_NUMERIC);

  // Supply JSON format if requested
  if (!empty($_GET['format']) && $_GET['format'] == 'json') {
    $output = array();
    $requestVersion = empty($_REQUEST['version']) ? '' : $_REQUEST['version'];
    // If a version has been specified, we only return info >= to that version
    $versionParts = explode('.', $requestVersion);
    if (empty($versionParts[0]) || empty($versionParts[1]) || !is_numeric($versionParts[0]) || !is_numeric($versionParts[1])) {
      // No valid version specified, just return all info
      return $rawJson;
    }
    $version = $versionParts[0] . '.' . $versionParts[1];
    foreach ($versionInfo as $majorVersion => $info) {
      if ($majorVersion >= $version) {
        $output[$majorVersion] = $info;
      }
    }
    return json_encode($output);
  }
  // Legacy support: CiviCRM < 4.6 expect latest release number in plain text
  foreach ($versionInfo as $majorVersion => $info) {
    if ($info['status'] == 'stable') {
      $latest = end($info['releases']);
      return $latest['version'];
    }
  }
}
