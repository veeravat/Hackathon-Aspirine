<?php
date_default_timezone_set("Asia/Bangkok");

session_start();

use \Psr\Http\Message\ServerRequestInterface as Request;
use \Psr\Http\Message\ResponseInterface as Response;


require '../../vendor/autoload.php';



//Google Setup
$client_id = '{GoogleClientID}';
$client_secret = '{ClientSecret}';

//CustomVision API
$visionAPI = array(
    'url'=> '{VisionURL',
    'key'=>'{Custom vision API key}'
);

//Using environment variables for DB connection information
$connectstr_dbhost = '';
$connectstr_dbname = '';
$connectstr_dbusername = '';
$connectstr_dbpassword = '';

//automate for azure db
foreach ($_SERVER as $key => $value) {
    if (strpos($key, "MYSQLCONNSTR_") !== 0) {
        continue;
    }
    
    $connectstr_dbhost = preg_replace("/^.*Data Source=(.+?);.*$/", "\\1", $value);
    $connectstr_dbname = preg_replace("/^.*Database=(.+?);.*$/", "\\1", $value);
    $connectstr_dbusername = preg_replace("/^.*User Id=(.+?);.*$/", "\\1", $value);
    $connectstr_dbpassword = preg_replace("/^.*Password=(.+?)$/", "\\1", $value);
}
$db = new MysqliDb ($connectstr_dbhost, $connectstr_dbusername, $connectstr_dbpassword, $connectstr_dbname);

$db->setPrefix ('APR_');
$db->rawQuery('SET time_zone  = \'+7:00\'');
$db ='';

$configuration = [
'settings' => [
'displayErrorDetails' => true,
],
];
require 'gapp.php';
$c = new \Slim\Container($configuration);

$app = new \Slim\App($c);


// Get container
$container = $app->getContainer();


// Register component on container
$container['view'] = function ($container) {
    
    $view = new \Slim\Views\Twig('../templates', [
    // 'cache' => '../cache'
    ]);
    
    // Instantiate and add Slim specific extension
    $basePath = rtrim(str_ireplace('index.php', '', $container['request']->getUri()->getBasePath()), '/');
    $view->addExtension(new Slim\Views\TwigExtension($container['router'], $basePath));
    
    return $view;
};

$container['notFoundHandler'] = function ($c) {
    
    return function ($request, $response) use ($c) {
        return $c['view']->render($response->withStatus(404), 'unauthorize.html', [
        "data" => $data = array("error"=> "ไม่พบหน้าที่ต้องการ")
        ]);
    };
};

$app->get('/', function ($request, $respond, $args)use($authUrl) {

    $state = true;
    if(strlen($authUrl) > 10){
        $state = false;
    }
    if(isset($_SESSION['login'])){
        $state = true;
        $user = $_SESSION['login'];
        return $respond->withStatus(302)->withHeader('Location', 'logedin');
    }else{
        $user = false;
    }
    return $this->view->render($respond, "index.html", [
        'loginGapp' => $authUrl,
        'loginState' => $state,
        'user'  => $user
    ]);
})->setName('home');

$app->get('/logedin', function ($request, $respond, $args)use($authUrl) {

    $state = true;
    if(strlen($authUrl) > 10){
        $state = false;
    }
    if(isset($_SESSION['login'])){
        $state = true;
        $user = $_SESSION['login'];
    }else{
        $user = false;
    }
    return $this->view->render($respond, "logedin.html", [
        'loginGapp' => $authUrl,
        'loginState' => $state,
        'user'  => $user
    ]);
})->setName('logedin');


$app->get('/profile', function ($request, $respond, $args)use($authUrl) {

    $state = true;
    if(strlen($authUrl) > 10){
        $state = false;
    }
    if(isset($_SESSION['login'])){
        $state = true;
        $user = $_SESSION['login'];
    }else{
        $user = false;
    }
    return $this->view->render($respond, "profile.html", [
        'loginGapp' => $authUrl,
        'loginState' => $state,
        'user'  => $user
    ]);
})->setName('logedin');


$app->get('/quiz', function ($request, $respond, $args) {

    return $this->view->render($respond, "quiz.html", []);
})->setName('quiz');

$app->get('/register', function ($request, $respond, $args) {

    return $this->view->render($respond, "register.html", []);
})->setName('register');



$app->post('/login', function ($request, $respond, $args)use($db,$visionAPI) {

    $post = $request->getParsedBody();

    if($post['source']== "video"){
        $data_string = $post['get_param'];
        $image = imagecreatefrompng($data_string);
        imagejpeg($image, 'image.jpg', 100);
        imagedestroy($image);
        $data_string = file_get_contents('image.jpg');
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL,$visionAPI['url']);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data_string);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $headers = [
            'Prediction-Key: '.$visionAPI['key'],
            'Content-Type: application/octet-stream'
        ];
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        $server_output = curl_exec ($ch);
        curl_close ($ch);
        header('Content-Type: application/json');
        $result = json_decode($server_output,true);
        $detect = 'unknow';
        if(isset($result['Predictions'])){
            foreach ($result['Predictions'] as $key => $value) {
                $proba = round($value['Probability']*100)/100;
                if($proba >= 0.75){
                    $detect = $value['Tag'];
                }
            }
        }

        echo($detect);
    }else if($post['source']== "form"){

    }

    
});

$app->run();