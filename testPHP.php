<?php
/*
Template Name: testPHP
*/
?>
<?php
require '/opt/bitnami/php/composer/vendor/autoload.php';
require '/opt/bitnami/php/composer/vendor/t-conf.php';
use Twilio\Rest\Client;
use Twilio\Twiml;

//header('content-type: text/xml');

global $TWIL_ACC_SID;
global $TWIL_TOKEN;
global $TWIL_NUM;
$client = new Client($TWIL_ACC_SID, $TWIL_TOKEN);
try {
    $call = $client->calls->create('+18034791475', $TWIL_NUM, array(
        "url" => "https://www.swipetobites.com/checkvm", 
        "machineDetection" => "Enable", 
        "MachineDetectionTimeout" => "15"));
    echo 'work';
} catch (Exception $e) {
    echo "Twilio call error: " . $e->getMessage();
}


?>