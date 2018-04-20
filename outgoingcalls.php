<?php
/*
Template Name: outgoingcalls
*/
?>
<?php
/**
 * @link https://codex.wordpress.org/Template_Hierarchy
 *
 * @package WordPress
 * @subpackage Twenty_Seventeen
 * @since 1.0
 * @version 1.0
 */

?>
<?php
// Use the REST API Client to make requests to the Twilio REST API
require '/opt/bitnami/php/composer/vendor/autoload.php';
require '/opt/bitnami/php/composer/vendor/t-conf.php';
use Twilio\Rest\Client;

chdir($ROOT_LOC);

global $wpdb;
global $call;
global $result;

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

function createXML($filename, $cusname, $busname, $menu, $menu_add, $cusphone){
	//Create the XML file
	$dom = new DOMDocument('1.0','UTF-8');
	$dom->formatOutput = true;
	$root = $dom->createElement('Response');
	$dom->appendChild($root);

	$pause = $dom->createElement('Pause');
	$pause->setAttribute('length',1);
	$gather = $dom->createElement('Gather');
	$gather->setAttribute('action',"https://www.swipetobites.com/twilio-menu/");
	$gather->setAttribute('method','POST');
	$gather->setAttribute('timeout',15);
	$gather->setAttribute('numDigits',1);
	$say = $dom->createElement('Say', 'Are you ready for their order? Press 1 if yes, Press 2 if you need this message repeated.');
	$say->setAttribute('voice','alice');
	$gather->appendChild($say);

	$root->appendChild($pause);
	$say = $dom->createElement('Say', 'Hello, this is a call from the Hungry app,​​ the customers name is '.$cusname.' and they would like to place an order to come pick up. Their phone number is '.num_to_text($cusphone).'.');
	$say->setAttribute('voice','alice');
	$root->appendChild($say);
	$root->appendChild($gather);
	$root->appendChild($dom->createElement('Redirect', "https://www.swipetobites.com/twilio-menu/?Digits=99"));

	$dom->save($filename) or (logTwil('Create XML error: XML file Create Error') and die());

	//create menu xml file
	$domMen = new DOMDocument('1.0','UTF-8');
	$domMen->formatOutput = true;
	$rootMen = $domMen->createElement('Response');
	$domMen->appendChild($rootMen);

	$pause = $domMen->createElement('Pause');
	$pause->setAttribute('length',1);
	$gather = $domMen->createElement('Gather');
	$gather->setAttribute('action',"https://www.swipetobites.com/twiliores/");
	$gather->setAttribute('method','POST');
	$gather->setAttribute('timeout',15);
	$gather->setAttribute('numDigits',1);
	$rootMen->appendChild($pause);
	if (!empty($menu_add)){
		$say = $domMen->createElement('Say', $cusname.' wants to order​ '.$menu.'. Also, additionaly requirements are '.$menu_add.'.');
	}
	else{
		$say = $domMen->createElement('Say', $cusname.' wants to order​ '.$menu.'.');
	}
	$say->setAttribute('voice','alice');
	$rootMen->appendChild($say);
	$say = $domMen->createElement('Say', 'Did you get all that? Press 1 if yes, press 2 if you need this message repeated.');
	$say->setAttribute('voice','alice');
	$gather->appendChild($say);
	$rootMen->appendChild($gather);
	$domMen->save(substr($filename, 0, 15).'Menu.xml');
}

//convert twilio number into text
function num_to_text($num){
	$text = '';
	for ($i = 1; $i <= 11; $i++) {
		$text = $text.$num[$i].',,,';
	}
	return $text;
}

// //essential for implementing repeat message and voicemail
// function createCallRecord($filename, $num, $sid){
// 	global $wpdb;
// 	global $ATT;
//     global $ATT_id;
// 	global $ATT_tsid;
// 	global $ATT_time;
// 	global $ATT_count;

// 	$wpdb->insert($ATT, array($ATT_id => $_REQUEST["ord"], $ATT_tsid => $sid, $ATT_time => date('Y-m-d H:i:s'), $ATT_count => 0));
	
// }

//create a record of the current status of an order
function logOrdStat($TwilSID){
	global $wpdb;
	global $STAT;
	global $STAT_id;
	global $STAT_tsid;
	global $STAT_ftime;
    global $STAT_count;
    global $STAT_ack;
    global $STAT_etime; 
	
	$wpdb->insert( 
	$STAT, 
	array( 
		$STAT_id => $_REQUEST["ordid"],
		$STAT_tsid => $TwilSID,
		$STAT_ftime => date('Y-m-d H:i:s'),
		$STAT_count => 0,
		$STAT_ack => 'N',
		$STAT_etime => 999,
		));
}

function makeCall($filename, $cusphone){
	global $TWIL_ACC_SID;
	global $TWIL_TOKEN;
	global $TWIL_NUM;
	$client = new Client($TWIL_ACC_SID, $TWIL_TOKEN);
	try {
		$call = $client->calls->create($cusphone, $TWIL_NUM, array(
			//"url" => "https://www.swipetobites.com/checkvm", 
			"url" => 'https://www.swipetobites.com/wp-content/uploads/twilio/'.$_REQUEST["ordid"].'.xml',
			//"machineDetection" => "Enable", 
			//"MachineDetectionTimeout" => "10"
		));
		logTwil("Started call: " . $call->sid);
		logOrdStat($call->sid);
    } catch (Exception $e) {
		logTwil("Twilio call error: " . $e->getMessage());
		die();
	}
}

$ordid = $_REQUEST["ordid"];
$cname = $_REQUEST["cname"];
$cnum = $_REQUEST["cnum"];
$ord = $_REQUEST["ord"];
$ord_add = $_REQUEST["ord_add"];
$rname = $_REQUEST["rname"];
$rnum = $_REQUEST["rnum"];
$pinfo = $_REQUEST["pinfo"];


if (!empty($ordid)){
	//Check entry
	if (strlen($ordid)!=15){
		logTwil("HTTP POST Error: Length requirement not met");
		echo $ordid, $cname, $cnum, $ord, $ord_add, $rname, $rnum, $pinfo;
		die();
	}
	elseif (substr($ordid, 0,3) != "ord"){
		logTwil("HTTP POST Error: Begin requirements not met");
		echo $ordid, $cname, $cnum, $ord, $ord_add, $rname, $rnum, $pinfo;
		die();
	}

	$wpdb->insert( 
	$ORD, 
	array( 
		$ORD_id => $ordid,
		$ORD_cname => $cname,
		$ORD_cnum => $cnum,
		$ORD_ord => $ord,
		$ORD_ord_add => $ord_add,
		$ORD_rname => $rname,
		$ORD_rnum => $rnum,
		$ORD_pinfo => $pinfo
		));
	
	//Set up SQL and query database
	$sql = $SQL_STATEMENT2.$ordid.'"';
	$result = $wpdb->get_results($sql, "ARRAY_A");
	
	if($result)	{		
		$filename = $ordid.".xml";	
		createXML($filename, $result[0]["cus_name"], $result[0]["res_name"], $result[0]["food_ord"], $result[0]["food_ord_add"], $result[0]["cus_num"]);
		if ($_REQUEST["debug"] == 0){
			makeCall($filename, $result[0]["res_num"]);
		}
	}
}
else{
 	logTwil("NO ORDER ID PROVIDED!");
}
?>
