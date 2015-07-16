<?php

require_once 'library/src/Google/autoload.php'; // or wherever autoload.php is located

session_start();

$client_id = '437878856334-61sc9t1hv8434cl0bg883bkj6pl3s3ao.apps.googleusercontent.com';
$client_secret = 'ezkYQfnU1Q9qm-7p6tBaRJBZ';
$redirect_uri = 'http://localhost/GOOGLE_API/oauth2callback.php';

$client = new Google_Client();
$client->setClientId($client_id);
$client->setClientSecret($client_secret);
$client->setRedirectUri($redirect_uri);
//$client->setAuthConfigFile('client_secret.json');
$client->addScope(Google_Service_Analytics::ANALYTICS_READONLY);

if (!isset($_GET['code'])) {
    $auth_url = $client->createAuthUrl();
    header('Location: ' . filter_var($auth_url, FILTER_SANITIZE_URL));
} else {
    $client->authenticate($_GET['code']);
    $_SESSION['access_token'] = $client->getAccessToken();
//    $redirect_uri = 'http://' . $_SERVER['HTTP_HOST'] . '/';
    $redirect_uri = 'http://localhost/GOOGLE_API';
    header('Location: ' . filter_var($redirect_uri, FILTER_SANITIZE_URL));
}
