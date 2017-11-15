<?php

if (false) {
    $app = new \Slim\Slim();
    $log = new Logger('main');
}


//$app->get('/test', function() use ($app) {
//     $a = 2;
//     $b = 5;
//     $c = $a + $b;
//     echo 'result = ' . $c;
//     die();
//});

// create a log channel

//$log = new Logger('mail');
//$log->pushHandler(new StreamHandler('logs/everything.log', Logger::WARNING));
//$log->pushHandler(new StreamHandler('logs/error.log', Logger::WARNING));
//  
//$twig = $app->view()->getEnvironment();
//$twig->addGlobal('usersession',$_SESSION['user']); 
//if(!isset($_SESSION['user'])){    
//    $_SESSION['user']=array();
//}

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


//////My Drive///////
$app->get('/yourdrive', function() use ($app, $log) {
    
    $app->render('yourdrive.html.twig');
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
//    die();
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



//function moveUploadedFile($directory, UploadedFile $uploadedFile)
//{
//    $extension = pathinfo($uploadedFile->getClientFilename(), PATHINFO_EXTENSION);
//    $basename = bin2hex(random_bytes(8)); // see http://php.net/manual/en/function.random-bytes.php
//    $filename = sprintf('%s.%0.8s', $basename, $extension);
//
//    $uploadedFile->moveTo($directory . DIRECTORY_SEPARATOR . $filename);
//
//    return $filename;
//}


