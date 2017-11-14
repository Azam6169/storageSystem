<?php

use Monolog\Logger;
use Monolog\Handler\StreamHandler;

session_start();

require_once 'vendor/autoload.php';

DB::$dbName = 'cp4809_storage';
DB::$user = 'cp4809_storage';
DB::$encoding = 'utf8';

DB::$password = 'OzrAns;t}tL4';
DB::$nonsql_error_handler='non_sql_hundler';
DB::$error_handler = 'sql_error_handler';
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

require_once 'cloudstorage.php';
require_once 'account.php';

if (!isset($_SESSION['user'])) {
    $_SESSION['user'] = array();
}
// url/event handler go here

$app->get('/', function() use ($app) {
     $app->render('index.html.twig',array('userSession' => $_SESSION['user']));
 
});

