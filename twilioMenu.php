<?php
/*
Template Name: twilioMenu
*/
?>
<?php
require_once '/opt/bitnami/php/composer/vendor/autoload.php';
require_once '/opt/bitnami/php/composer/vendor/t-conf.php';
use Twilio\Rest\Client;
use Twilio\Twiml;

global $wpdb;

chdir($ROOT_LOC);

function logTwil($str){
	global $LOG;
	//time at utc +0
	chdir($LOG);
	$date = getdate();
	$file = $date['month'].$date["mday"].$date['year']."twilio";
	$handle = fopen($file, "a");
	fwrite($handle, $date['hours']."-".$date["minutes"]."-".$date['seconds']."=>\t".$str."\n");
	fclose($handle);
}

function noResponseMsg($order_id){
    global $TWIL_ACC_SID;
	global $TWIL_TOKEN;
    global $TWIL_NUM;
	
	$client = new Client($TWIL_ACC_SID, $TWIL_TOKEN);

	try {
		$message = $client->messages->create("+18034791475", array('From' => $TWIL_NUM, 'Body' => "Restaurant gave no response for order ".$order_id."."));
		logTwil("Restaurant no response: Order- ".$order_id.", TwilioSid- ". $message->sid);
	} 
	catch (Exception $e) {
		logTwil("Restaurant no response message error: " . $e->getMessage());
	}
}

$sql = 'SELECT '.$STAT_id.', '.$STAT_count.' FROM '.$STAT.' WHERE '.$STAT_tsid.' = "'.$_REQUEST['CallSid'].'"';
$result = $wpdb->get_results($sql, "ARRAY_A");
$order = $result[0][$STAT_id];
$count = $result[0][$STAT_count];

if (is_null($order)){
    logTwil('TwilioMenu: No CallSid provided or matched');
    die();
}

header('content-type: text/xml');
if ($_REQUEST['Digits'] == 1){
    // continue to say menu
    $order= substr($order, 0,15).'Menu.xml';
}
elseif ($_REQUEST['Digits'] == 99){
	// initial instruction will repeat twice, if no response then taken as voicemail and hangs up
	if ($count >= 1){
		echo '<Response><Hangup/></Response>';
		$wpdb->update($STAT, array($STAT_count => ++$count), array($STAT_tsid => $_REQUEST['CallSid']));
		noResponseMsg($order);
		die();
	}
	else{
		$wpdb->update($STAT, array($STAT_count => ++$count), array($STAT_tsid => $_REQUEST['CallSid']));
	}
	$order= substr($order, 0,15).'.xml';
}
else {
    // repeat initial message
    $order= substr($order, 0,15).'.xml';
}

$output = new TwiML();
$output->redirect('https://www.swipetobites.com/wp-content/uploads/twilio/'.$order, ['method'=>'POST']);
echo $output;
?>