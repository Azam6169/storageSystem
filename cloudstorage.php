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

///////////reset password///////
$app->get('/forgotPassword', function() use ($app, $log) {
    $app->render('forgot_password.html.twig');
});
$app->post('/forgotPassword', function() use ($app, $log) {
    //When someone claims that password forgotten , make sure there is no active user
    $_SESSION['user'] = array();
    //$_SESSION['facebook_access_token'] = array();

    $email = $app->request->post('email');
    $user = DB::queryFirstRow("SELECT * FROM users WHERE email=%s", $email);
    if (!$user) {
        $log->debug(sprintf("User failed for email %s from IP %s", $email, $_SERVER['REMOTE_ADDR']));
        $app->render('forgot_password.html.twig', array('loginFailed' => TRUE));
    } else {
        $token = bin2hex(openssl_random_pseudo_bytes(16));
        $to = $user['email'];
        //echo "your email is ::".$email;
        //Details for sending E-mail
        $from = "storage";
        $url = "http://storage.ipd10.com//resetPassword/$token";
        $body = $app->view()->render('email_passreset.html.twig', array(
            'name' => $user['name'],
            'url' => $url
        ));
        $from = "storage.ipd10.com/";
        $subject = "Fastfood-online reset password request";
        $headers = "From: $from\n";
        $headers .= "Content-type: text/html;charset=utf-8\r\n";
        $headers .= "X-Priority: 1\r\n";
        $headers .= "X-MSMail-Priority: High\r\n";
        $headers .= "X-Mailer: Just My Server\r\n";
        try {
            $sentmail = mail($to, $subject, $body, $headers);
            if ($sentmail) {
                DB::$error_handler = FALSE;
                DB::$throw_exception_on_error = TRUE;
                try {
                    DB::startTransaction();
                    //FIXME: update or insert
                    //check if an use has already a reset token
                    $result = DB::queryOneField('userID', "SELECT * FROM resettokens WHERE userID=%d", $user['ID']);
                    DB::insertUpdate('resettokens', array(
                        'userID' => $user['ID'],
                        'resetToken' => $token,
                        'expiryDateTime' => date("Y-m-d H:i:s", strtotime("+5 days"))
                    ));

                    DB::update('users', array(
                        'locked' => TRUE,), 'ID = %d', $user['ID']);

                    DB::commit();
                    $log->debug(sprintf("Reset token for user id %s", $userID));
                    $app->render('email_status.html.twig');
                } catch (MeekroDBException $e) {
                    DB::rollback();
                    $log->debug(sprintf("Could not Reset token for user id %s. Error: %s", $user['ID'], $e));
                    $app->render('forgot_password.html.twig', array('failedEmail' => TRUE));
                }
            } else {
                $log->error(sprintf("Could not send email for user id %s.", $user['ID'], $e));
                $app->render('forgot_password.html.twig', array('failedEmail' => TRUE));
            }
        } catch (Exception $ex) {
            $app->render('email_status.html.twig', array('failed' => TRUE));
        }
    }
});
$app->get('/resetPassword/:token', function($token) use ($app, $log) {
    $app->render('reset_password.html.twig', array('resetToken' => $token));
});
$app->post('/resetPassword', function() use ($app, $log) {
    $pass1 = $app->request->post('pass1');
    $pass2 = $app->request->post('pass2');
    $resetToken = $app->request->post('resetToken');

    // submission received - verify
    $errorList = array("en" => array(), "fr" => array());
    if (!preg_match('/[0-9;\'".,<>`~|!@#$%^&*()_+=-]/', $pass1) || (!preg_match('/[a-z]/', $pass1)) || (!preg_match('/[A-Z]/', $pass1)) || (strlen($pass1) < 8)) {
        array_push($errorList["en"], "Password must be at least 8 characters " .
                "long, contain at least one upper case, one lower case, " .
                " one digit or special character");
        array_push($errorList["fr"], "Mot de passe doit être d'au moins 8 caractères, contenir au moins une majuscule, une minuscule,un chiffre ou un caractère spécial");
    } else if ($pass1 != $pass2) {
        array_push($errorList["en"], "Passwords don't match");
        array_push($errorList["fr"], "Les mots de passe ne coincident pas");
    }
    //
    if ($errorList["en"] || $errorList["fr"]) {
        // STATE 3: submission failed        
        $app->render('reset_password.html.twig', array(
            'errorList' => $errorList[$_COOKIE['lang']]
        ));
    } else {
        // STATE 2: submission successful
        DB::$error_handler = FALSE;
        DB::$throw_exception_on_error = TRUE;
        try {
            DB::startTransaction();
            $userID = DB::queryOneField('userID', "SELECT * FROM resettokens WHERE resetToken=%s AND expiryDateTime > NOW()", $resetToken);
            if (empty($userID)) {
                $log->error(sprintf("Attempt to reset password for an invalid token from IP %s", $_SERVER['REMOTE_ADDR']));
                $app->render('reset_status.html.twig', array('failed' => TRUE));
            } else {
                DB::delete('resettokens', 'resetToken =%s', $resetToken);
                DB::update('users', array(
                    'locked' => FALSE, 'password' => password_hash($pass1, CRYPT_BLOWFISH)), 'ID = %d', $userID);
                DB::commit();
                $log->debug(sprintf("Reset token for user id %s", $userID));
                $app->render('reset_status.html.twig');
            }
        } catch (MeekroDBException $e) {
            DB::rollback();
            $log->debug(sprintf("Could not Reset token for user id %s. Error: %s", $user['ID'], $e));
            $app->render('reset_status.html.twig', array('failed' => TRUE));
        }
    };
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
   
});
$app->run();
