<?php
error_reporting(E_ALL);
ini_set('display_errors', '1');

$client = new SoapClient('http://magentomigrate.com/index.php/api/v2_soap?wsdl=1');

// If some stuff requires api authentification, then get a session token
$session = $client->login('bfitzgerald ', 'api_key');

?>