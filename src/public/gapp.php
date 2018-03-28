<?php


$actual_link = (isset($_SERVER['HTTPS']) ? "https" : "http") . "://$_SERVER[HTTP_HOST]";
$redirect_uri = $actual_link.'/login';
$client = new Google_Client();
$client->setClientId($client_id);
$client->setClientSecret($client_secret);
$client->setRedirectUri($redirect_uri);
$client->setScopes(array('email','profile'));



if (isset($_REQUEST['logout'])) {
    session_destroy();
    echo '<meta http-equiv="refresh" content="0; url= /" />';
    exit(0);
    
}

if (isset($_GET['code'])) {
    $token = $client->fetchAccessTokenWithAuthCode($_GET['code']);
    $_SESSION['access_token'] = $token;
    echo '<meta http-equiv="refresh" content="0; url=/" />';
    exit(0);
}


if (isset($_SESSION['access_token']) && $_SESSION['access_token']) {
    if (($_SESSION['access_token']['created']+$_SESSION['access_token']['expires_in'])-time()>0){
        $client->setAccessToken($_SESSION['access_token']);
        if($client->getAccessToken()){
            $ticket = $client->verifyIdToken();
            $_SESSION['login'] = $ticket['email'];
        }
        
    }else{
        session_destroy();
        echo '<meta http-equiv="refresh" content="0; url='.$redirect_uri .'" />';
        exit(0);
        
    }
    $authUrl = '';
    //   echo '<pre>';
    //   print_r($ticket);
    //   echo '</pre>';
    //   exit(0);
} else {
    $authUrl = $client->createAuthUrl();
    $ticket = '';
}

      