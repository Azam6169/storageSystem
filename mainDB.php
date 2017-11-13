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
$log = new Logger('mail');
$log->pushHandler(new StreamHandler('logs/everything.log', Logger::WARNING));
$log->pushHandler(new StreamHandler('logs/error.log', Logger::WARNING));


if (!isset($_SESSION['user'])) {
    $_SESSION['user'] = array();
}
// url/event handler go here

$app->get('/', function() use ($app) {
     $app->render('index.html.twig',array('userSession' => $_SESSION['user']));
 
});
$app->run();

