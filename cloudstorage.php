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
$app->get('/logout', function() use ($app) {
    $_SESSION['user'] = array();
    $app->render('logout.html.twig');
});
/* * ****************** LogIn*********************** */
$app->get('/login', function() use ($app) {
    $app->render('login.html.twig');
});

$app->post('/login', function() use ($app) {
    $email = $app->request()->post('email');
    $pass = $app->request()->post('pass');
    $row = DB::queryFirstRow("SELECT * FROM users WHERE email=%s", $email);
    $error = false;
    if (!$row) {
        $error = true; // user not found
    } else {
        if (password_verify($pass, $row['password']) == FALSE) {
            $error = true; // password invalid
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


/* * ****************** check email if registered *********************** */
$app->get('/isemailregistered/:email', function($email)use($app) {
    $row = DB::queryFirstRow("SELECT * FROM users WHERE email=%s", $email);
    echo!$row ? "" : '<span style="color:red; font-weight:bold;">Email already registered.</span>';
    
});
/* * ****************** check username if taken *********************** */
$app->get('/isusernametaken/:username', function($name)use($app) {
    $row = DB::queryFirstRow("SELECT * FROM users WHERE name=%s", $name);
    echo!$row ? "" : '<span style="color:red; font-weight:bold;">Username already taken.</span>';
});
$app->get('/isemailregistered/:email', function($email) use ($app) {
    $row = DB::queryFirstRow("SELECT * FROM users WHERE email=%s", $email);
    echo!$row ? "" : '<span style="background-color: red; font-weight: bold;">Email already taken</span>';
});
$app->get('/register', function() use ($app) {
    $app->render('register.html.twig');
});
$app->post('/register', function() use ($app) {
    $name = $app->request()->post('name');
    $email = $app->request()->post('email');
    $pass1 = $app->request()->post('pass1');
    $pass2 = $app->request()->post('pass2');
    //
    $values = array('name' => $name, 'email' => $email);
    $errorList = array();
    //
    if (strlen($name) < 2 || strlen($name) > 50) {
        $values['name'] = '';
        array_push($errorList, "Name must be between 2 and 50 characters long");
    }
    if (filter_var($email, FILTER_VALIDATE_EMAIL) == FALSE) {
        $values['email'] = '';
        array_push($errorList, "Email must look like a valid email");
    } else {
        $row = DB::queryFirstRow("SELECT * FROM users WHERE email=%s", $email);
        if ($row) {
            $values['email'] = '';
            array_push($errorList, "Email already in use");
        }
    }
    if ($pass1 != $pass2) {
        array_push($errorList, "Passwords don't match");
    } else {
// TODO: do a better check for password quality (lower/upper/numbers/special)
        if (strlen($pass1) < 2 || strlen($pass1) > 50) {
            array_push($errorList, "Password must be between 2 and 50 characters long");
        }
    }
    //
    if ($errorList) { // 3. failed submission
        $app->render('register.html.twig', array(
            'errorList' => $errorList,
            'v' => $values));
    } else { // 2. successful submission
        $passEnc = password_hash($pass1, PASSWORD_BCRYPT);
        DB::insert('users', array('name' => $name, 'email' => $email, 'password' => $passEnc));
        $app->render('register_success.html.twig');
    }
});
/////////////////Upload File////////////////

$app->get('/share', function() use ($app, $log) {
    
    $app->render('share.html.twig');
});
/**
$app->post('/share', function() use ($app, $log) {
    echo 'here'; die();
    $filename = $app->request()->post('filename');
    $values = array('filename' => $filename);
    
    $app->render('share.html.twig', array('v' => $values));
});
**/

$container['upload_directory'] = '/uploads';
$app->post('/books', function () {
    //Create book
    echo 'helloo!'; die();
});
$app->post('/share', function() {    
    $file = $_FILES['newfile'];
    //var_dump($file);
    $originalName = $file['name'];
    $ext = pathinfo($originalName, PATHINFO_EXTENSION);
    //var_dump($ext);
    //die();
    $uniqueName =  md5($file['tmp_name'] . time());
    $destinationDir = 'uploads';
    
    move_uploaded_file($file['tmp_name'], $destinationDir . '/' . $uniqueName .'.' . $ext);
    die();
    //$directory = $this->get('upload_directory');
    
    /**

    $uploadedFiles = $request->getUploadedFiles();

    foreach ($uploadedFiles['example3'] as $uploadedFile) {
        if ($uploadedFile->getError() === UPLOAD_ERR_OK) {
            $filename = moveUploadedFile($directory, $uploadedFile);
            $response->write('uploaded ' . $filename . '<br/>');
        }
    }

   **/
});
function moveUploadedFile($directory, UploadedFile $uploadedFile)
{
    $extension = pathinfo($uploadedFile->getClientFilename(), PATHINFO_EXTENSION);
    $basename = bin2hex(random_bytes(8)); // see http://php.net/manual/en/function.random-bytes.php
    $filename = sprintf('%s.%0.8s', $basename, $extension);

    $uploadedFile->moveTo($directory . DIRECTORY_SEPARATOR . $filename);

    return $filename;
}

$app->run();
