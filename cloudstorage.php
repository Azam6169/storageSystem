<?php

use Monolog\Logger;
use Monolog\Handler\StreamHandler;

session_start();

require_once 'vendor/autoload.php';

DB::$dbName = 'cp4809_storage';
DB::$user = 'cp4809_storage';
DB::$encoding = 'utf8';

DB::$password = 'OzrAns;t}tL4';
// Slim creation and setup
$app = new \Slim\Slim(array(
    'view' => new \Slim\Views\Twig()
        ));

$view = $app->view();
$view->parserOptions = array(
    'debug' => true,
    'cache' => dirname(__FILE__) . '/cache'
);
$view->setTemplatesDirectory(dirname(__FILE__) . '/templates');

// create a log channel
$log = new Logger('main');
$log->pushHandler(new StreamHandler('logs/everything.log', Logger::DEBUG));
$log->pushHandler(new StreamHandler('logs/errors.log', Logger::ERROR));


if (!isset($_SESSION['user'])) {
    $_SESSION['user'] = array();
}
// url/event handler go here
$app->get('/', function() use ($app) {
    echo "This is storage project";
});
//--------------------------------------------------GET LOGIN
$app->get('/login', function() use ($app) {
    $app->render('login.html.twig');
});
//--------------------------------------------------POST LOGIN
$app->post('/login',function() use ($app) {
    $email = $app->request()->post('email');
    $pass = $app->request()->post('password');
    $row = DB::queryFirstRow("SELECT * FROM users WHERE email=%s", $email);
    $error = true;
    if ($row) {
        if(password_verify($pass, $row['password'])){
        //if ($pass == $row['password']) {
            $error = false;
        }
    }
    if ($error) {
        $app->render('login.html.twig', array('error' => true));
    } else {
        unset($row['password']);
        $_SESSION['user'] = $row;
        $app->render('login_success.html.twig', array('userSession' => $_SESSION['user']));
    }    
});

//--------------------------------------------------GET LOGOUT
$app->get('/logout', function() use ($app) {
    $_SESSION['user'] = array();
    $app->render('logout.html.twig', array('userSession' => $_SESSION['user']));
});


$app->run();
