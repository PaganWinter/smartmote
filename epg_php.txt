<?php

require_once("bootstrap.php");

if ($db->connect_errno > 0){
  die('Unable to connect to database [' . $db->connect_error . ']');
}

$out_data = array();

if(!isset($_GET['chl_id']) && !isset($_GET['chl_code'])) {
  send_error("No Channel Id found in request.");
  exit();
}


$chl_id = $_GET['chl_id'];
//$chl_code = $_GET['chl_code'];
$timestamp = time();
$ddmmyyyy = date('dmY', $timestamp);


/* Check if EPG for this channel for today is already loaded in DB */
$sel_stmt = $db->prepare("SELECT id, prog_name, prog_desc, start_time FROM remote_epg WHERE chl_id = ? AND start_time >= ? ORDER BY start_time ASC");
if ($sel_stmt === false) {
  send_error($db->error);
  exit();
}
$sel_stmt -> bind_param("ii", $chl_id, $timestamp);
$sel_stmt->execute();
$sel_stmt->store_result();


$i = 0;
/* Checking if info for at least 2 programs from current time is available, else refresh DB */
if ($sel_stmt->num_rows > 2) {
    $out_data['status'] = 1;
    $out_data['chl_id'] = $chl_id;
    $out_data['extra'] = 'From DB';

  $sel_stmt->bind_result($id, $prog_name, $prog_desc, $start_time);
  while($sel_stmt->fetch()) {
    $out_data['data']['prog'][$i]['prog_name'] = htmlspecialchars($prog_name);
    $out_data['data']['prog'][$i]['prog_desc'] = htmlspecialchars($prog_desc);
    $out_data['data']['prog'][$i]['start_time'] = $start_time;
//    $out_data['data']['prog'][$i]['start_time_fmt'] = date('d M H:i', $start_time);
    $out_data['data']['prog'][$i]['start_time_fmt'] = date('H:i', $start_time);
    $i++;
  }
  $sel_stmt->close();

  print json_encode($out_data);
  $out_data = array();

  exit();
}

$out_data = array();
$sel_stmt->close();

/* Not enough programs in DB, fetch from API */
/* Get ITPG Channel Code from Channel Id */
$chl_code = get_chl_code($chl_id, $db);

$url = "http://indian-television-guide.appspot.com/indian_television_guide?channel=" . $chl_code . "&date=".$ddmmyyyy;
/*
date: 22022015
channelName: comedy-central
listOfShows:
  showTitle
  showTime (15:00:00)
  showThumb
  showDetails:
    [Show Description]
    [Show Type] (Talk Show)
    [Language]
    [Genre] (Comedy,Drama)
    [Release Date] (22 February 2007)
    [IMDB Rating] (8.1/10)
    [Created By]
    [Hosted By]
    [Actor] (James Roday, Dule Hill, Timothy Omundson, Maggie Lawson, Kirsten Nelson)
    [Trivia]
*/

$ch = curl_init();
curl_setopt ( $ch, CURLOPT_URL, $url );
curl_setopt ( $ch, CURLOPT_HEADER, 0 );
curl_setopt ( $ch, CURLOPT_RETURNTRANSFER, 1 );
curl_setopt ( $ch, CURLOPT_USERAGENT, "User-Agent: Some-Agent/1.0" );
curl_setopt ( $ch, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4 );

$api_json = curl_exec($ch);
if(curl_errno($ch)) {
  send_error("Curl error: " . curl_error($ch));
  curl_close ($ch);
  exit();
}


$api_prog_list = json_decode($api_json);
if (!isset($api_prog_list->listOfShows)) {
  send_error("No data found for Channel Id: $chl_id ($chl_code) from the API. ($api_json)");
  exit();
}


/* Found data from API. Insert into DB */
/* Clear existing entries in DB for this channel id */

$del_stmt = $db->prepare("DELETE FROM remote_epg WHERE chl_id = ?");
//var_dump($sel_stmt); //print $db->error;
if ($del_stmt === false) {
  send_error($db->error);
  exit();
}
$del_stmt -> bind_param("i", $chl_id);
$del_stmt->execute();
$del_stmt->close();

/* Insert progs into DB */
$showDesc = "Show Description";
$progs_list = array();
$j = 0;

$out_data['status'] = 1;
$out_data['channel'] = $chl_code;
$out_data['extra'] = 'From API';

foreach ($api_prog_list->listOfShows as $prog) {
  /*
  $str = "abqwrešđčžsff";
  $res = preg_replace('/[^\x20-\x7E]/','', $str);
  echo "($str)($res)";
  */
  $progs_list[$j]['showTitle'] = $prog->showTitle;
  $progs_list[$j]['showDesc'] = $prog->showDetails->$showDesc;
  $progs_list[$j]['showTime'] = $prog->showTime;
  $progs_list[$j]['showThumb'] = $prog->showThumb;

  $prog_name = mysqli_real_escape_string( $db, substr($prog->showTitle, 0, 100) );;
  $prog_desc = mysqli_real_escape_string( $db, substr($prog->showDetails->$showDesc, 0, 200) );
  $start_time = strtotime( date('Y-m-d', $timestamp) . " " . $prog->showTime);

  $out_data['data']['prog'][$j]['prog_name'] = htmlspecialchars($prog_name);
  $out_data['data']['prog'][$j]['prog_desc'] = htmlspecialchars($prog_desc);
  $out_data['data']['prog'][$j]['start_time'] = $start_time;
//  $out_data['data']['prog'][$j]['start_time_fmt'] = date('d M H:i', $start_time);
  $out_data['data']['prog'][$j]['start_time_fmt'] = date('H:i', $start_time);

  $ins_stmt = $db->prepare("INSERT INTO remote_epg(chl_id, prog_name, prog_desc, start_time) VALUES(?, ?, ?, ?)");
  if ($ins_stmt === false) {
    var_dump($ins_stmt);
    send_error($db->error);
    exit();
  }
  $ins_stmt->bind_param("issi", $chl_id, $prog_name, $prog_desc, $start_time);
  $ins_stmt->execute();
  $ins_stmt->close();

  $j++;
}


print json_encode($out_data);
$db->close();
exit();


function send_error($err_txt) {
  $out_data['status'] = 0;
  $out_data['data'] = $err_txt;
  print json_encode($out_data);
}

function get_chl_code($chl_id, $db) {
  $sel_stmt = $db->prepare("SELECT prog_itpg FROM remote_channel WHERE chl_id = ?");
  if ($sel_stmt === false) {
    send_error($db->error);
    exit();
  }
  $sel_stmt -> bind_param("i", $chl_id);
  $sel_stmt->bind_result($chl_code);
  $sel_stmt->execute();
  $sel_stmt->fetch();
  //hp($chl_code);
  $sel_stmt->close();
  return $chl_code;
}


?>