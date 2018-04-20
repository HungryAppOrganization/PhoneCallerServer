<?php
/*
Template Name: checkVM
*/
?>
<?php
//handle answered by voicemail

require '/opt/bitnami/php/composer/vendor/t-conf.php';
chdir($ROOT_LOC);

function getORD(){
	$file = "messageRepeatTrack";
	$handle = fopen($file, "r");
	if ($handle) {
		while (($buffer = fgets($handle)) !== false) {
			list($sid, $goToFile) = explode("=>", $buffer);
			if ($sid === $_REQUEST['CallSid']){
				fclose($handle);
				return array($sid,$goToFile, getcwd());
			}
		}
		fclose($handle);
	}

	global $wpdb;
	global $ATT;
    global $ATT_id;
	global $ATT_tsid;
    
    $sql = 'SELECT '.$ATT_id.' FROM '.$ATT.' WHERE '.$ATT_tsid.' = "'.$_REQUEST['CallSid'].'"';
    $result = $wpdb->get_results($sql, "ARRAY_A");
    return $result[0][$ATT_id];
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
// if ($_REQUEST['AnsweredBy'] == 'human'){
if (true){
	//answered by human
	header("content-type: text/xml; charset=utf-8");
    echo '<Response>';
    echo '<Redirect method="POST">https://www.swipetobites.com/wp-content/uploads/twilio/'.getORD().'.xml'.'</Redirect>';
    echo '</Response>';
}
else{
	//answered by machine or other
	header("content-type: text/xml; charset=utf-8");
    echo '<Response>';
    echo '<Say voice="alice">A customer wanted to make a order. Please call Hungry.</Say>';
    echo '</Response>';
    logTwil("Received voicemail with callID: ".$_REQUEST['CallSid']);
}
?>