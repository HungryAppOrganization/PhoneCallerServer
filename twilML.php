<?php
/*
Template Name: twilML
*/
?>
<?php
require_once '/opt/bitnami/php/composer/vendor/autoload.php';
require_once '/opt/bitnami/php/composer/vendor/t-conf.php';
use Twilio\Twiml;

chdir($ROOT_LOC);

function getSID(){
	// $file = "messageRepeatTrack";
	// $handle = fopen($file, "r");
	// if ($handle) {
	// 	while (($buffer = fgets($handle)) !== false) {
	// 		list($sid, $goToFile) = explode("=>", $buffer);
	// 		if ($sid === $_REQUEST['CallSid']){
	// 			fclose($handle);
	// 			return array($sid,$goToFile);
	// 		}
	// 	}
	// 	fclose($handle);
	// }

	global $wpdb;
	global $STAT;
    global $STAT_id;
	global $STAT_tsid;
    
    $sql = 'SELECT '.$STAT_id.' FROM '.$STAT.' WHERE '.$STAT_tsid.' = "'.$_REQUEST['CallSid'].'"';
    $result = $wpdb->get_results($sql, "ARRAY_A");
    return $result[0][$STAT_id];
}

//get dtmf response and accordingly run action
if (!empty($_REQUEST['Digits'])){
	header('content-type: text/xml');
	if($_REQUEST['Digits'] == '1')
	{	
		//business wants to confirm receipt of order
		$output = new TwiML();
		$output->say('When should the customer expect to come pick up the food?',['voice' => 'alice']);
		$gather = $output->gather(['action'=> 'https://www.swipetobites.com/twilioesttime/', 'method'=>'POST', 'timeout' => '15', 'numDigits'=>'1']);
		$gather->say('Press 1 if in 15 minutes,, 2 if 20 to 30 minutes,, 3 for 35 to 45 minutes,, or 4 if roughly an hour or more.',['voice' => 'alice']);
		$output->redirect('https://www.swipetobites.com/twilioesttime',['method'=>'POST']);
		echo $output;
	}
	else{
		$sql = 'SELECT '.$STAT_id.' FROM '.$STAT.' WHERE '.$STAT_tsid.' = "'.$_REQUEST['CallSid'].'"';
		$result = $wpdb->get_results($sql, "ARRAY_A");
		$order = getSID();

		//business want menu repeated or button 2 not pressed 
		$output = new TwiML();
		$output->redirect('https://www.swipetobites.com/wp-content/uploads/twilio/'.$order.'Menu.xml', ['method'=>'POST']);
		echo $output;
	}
}
else{
	echo '<p>Digits empty</p>';
}
?>