<?php
error_reporting(E_ALL);
ini_set('display_errors', '1');

$client = new SoapClient( SOAP_URL );

// If some stuff requires api authentification, then get a session token
$session = $client->login( SOAP_LOGIN, SOAP_API_KEY );

?>