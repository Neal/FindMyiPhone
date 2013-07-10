<?php

require __dir__ . '/../include/classes/FindMyiPhone.php';

try {

	$FindMyiPhone = new FindMyiPhone('APPLEID-USERNAME', 'APPLEID-PASSWORD');

	// get the device id for first device found
	$device_id = $FindMyiPhone->devices[0]->id;

	echo 'Sending notification... ';
	echo ($FindMyiPhone->send_message($device_id, 'Hi.', false, 'FindMyiPhone')->statusCode == 200) ? 'Sent!' : 'Failed!';
	echo PHP_EOL;

} catch (exception $e) {
	echo 'ERROR: ' . $e->getMessage() . PHP_EOL;
}

?>
