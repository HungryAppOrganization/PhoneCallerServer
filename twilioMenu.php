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

function getSID(){
    global $wpdb;
	global $STAT;
    global $STAT_id;
	global $STAT_tsid;
    
    $sql = 'SELECT '.$STAT_id.' FROM '.$STAT.' WHERE '.$STAT_tsid.' = "'.$_REQUEST['CallSid'].'"';
    $result = $wpdb->get_results($sql, "ARRAY_A");
    return $result[0][$STAT_id];
}

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
	logTwil("No response.....");
    global $TWIL_ACC_SID;
	global $TWIL_TOKEN;
    global $TWIL_NUM;
    global $SQL_MSG2;
    global $wpdbb;


    $orderRecord = getSID();

    // there should only be one order per customer, therefore wpdb update should only return 1
    if (0 != $wpdb->update($STAT, array($STAT_etime => $time, $STAT_ack => 'Y', $STAT_ctime => date('Y-m-d H:i:s')), array($STAT_id => $orderRecord))){
        // send confirmtion message to customer once order is complete
        $client = new Client($TWIL_ACC_SID, $TWIL_TOKEN);

        $sqlMes = $SQL_MSG2.$orderRecord.'"';
        $result = $wpdb->get_results($sqlMes, "ARRAY_A");
            
        try {
            $message = $client->messages->create($result[0]["cus_num"], array('From' => $TWIL_NUM,'Body' => "Hey ".$result[0]["cus_name"].", Hungry here. Your order was confirmed by ".$result[0]["res_name"]." and will be ready ".$message.". Your order id is ".$orderRecord."."));
            logTwil("Order confirmation message sent: " . $message->sid);
        } 
        catch (Exception $e) {
            logTwil("Order confirmation message error: " . $e->getMessage());
        }

        //send message to custome of confirmation
        header('content-type: text/xml');
        $output = new TwiML();
        $output->say('Thank you for confirming order. '.$result[0]["cus_name"].' will be expecting their order in '.$time.' minutes. Have a nice day.',['voice' => 'alice']);
        echo $output;
    }
    else{
        logTwil('WPDB access confirmation message error: 0 records affected or returned flase.');
    }


    /*

    logTwil("Order record: " . $orderRecord);
	
	$client = new Client($TWIL_ACC_SID, $TWIL_TOKEN);
	logTwil("beyond client...");
	$sqlMes = $SQL_MSG2.$orderRecord.'"';
	logTwil("sql message: " . $SQL_MSG2);
    $result = $wpdbb->get_results($sqlMes, "ARRAY_A");
    logTwil("Final result...");
	try {
		$message = $client->messages->create("102", array('From' => $TWIL_NUM, 'Body' => "Restaurant gave no response for order ".$order_id."."));
		logTwil("Restaurant no response: Order- ".$order_id.", TwilioSid- ". $message->sid);
	} 
	catch (Exception $e) {
		logTwil("Restaurant no response message error: " . $e->getMessage());
		#logTwil("Restaurant no response message error texting: " . $result[0]["cus_num"]);
	}
	*/
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
    logTwil("Responded with a 1");
    $order= substr($order, 0,15).'Menu.xml';
}
elseif ($_REQUEST['Digits'] == 99){
	// initial instruction will repeat twice, if no response then taken as voicemail and hangs up
	if ($count >= 1){
		
		$wpdb->update($STAT, array($STAT_count => ++$count), array($STAT_tsid => $_REQUEST['CallSid']));
		noResponseMsg($order);
		echo '<Response><Hangup/></Response>';
		die();
	}
	else{
		$wpdb->update($STAT, array($STAT_count => ++$count), array($STAT_tsid => $_REQUEST['CallSid']));
	}
	$order= substr($order, 0,15).'.xml';
}
elseif ($_REQUEST['Digits'] == 3) {
	logTwil("pressed 3");
	echo '<Response><Hangup/></Response>';
	$wpdb->update($STAT, array($STAT_count => ++$count), array($STAT_tsid => $_REQUEST['CallSid']));
	noResponseMsg($order);
	die();

} elseif ($_REQUEST['Digits'] == 4) {


} else {
    // repeat initial message
    $order= substr($order, 0,15).'.xml';
}

$output = new TwiML();
$output->redirect('https://www.swipetobites.com/wp-content/uploads/twilio/'.$order, ['method'=>'POST']);
echo $output;
?>