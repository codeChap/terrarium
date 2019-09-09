<?php
header('Content-Type: text/event-stream');
header('Cache-Control: no-cache'); // recommended to prevent caching of event data.
header('Access-Control-Allow-Origin: *');
header('Access-Control-Expose-Headers: *');
header('Access-Control-Allow-Credentials: true');


/**
 * Constructs the SSE data format and flushes that data to the client.
 *
 * @param string $id Timestamp/id of this connection.
 * @param string $msg Line of text that should be transmitted.
 */

/*
function sendMsg($id, $msg) {
  ob_end_clean();
  echo "id: $id" . PHP_EOL;
  echo "data: $msg" . PHP_EOL;
  echo PHP_EOL;
  ob_flush();
  flush();
}

$serverTime = time();
$d = file_get_contents('/var/develop/ter/web/ht.txt');
sendMsg($serverTime, $d);
*/

$id = time();

while(true) {
	$d = file_get_contents('/var/develop/ter/web/ht.txt');
	echo "id: $id" . PHP_EOL;
	echo "data:".$d.PHP_EOL;
	echo PHP_EOL;
	ob_end_flush();
	flush();
	sleep(1);
}

?>