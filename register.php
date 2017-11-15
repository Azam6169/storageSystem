<?php

if (false) {
    $app = new \Slim\Slim();
    $log = new Logger('main');
}

/* * ****************** check email if registered *********************** */
$app->get('/isemailregistered/:email', function($email)use($app) {
    $row = DB::queryFirstRow("SELECT * FROM users WHERE email=%s", $email);
    echo!$row ? "" : '<span style="color:red; font-weight:bold;">Email already registered.</span>';
    
});
/* * ****************** check username if taken *********************** */
$app->get('/isusernametaken/:username', function($name)use($app) {
    $row = DB::queryFirstRow("SELECT * FROM users WHERE name=%s", $name);
    echo !$row ? "" : '<span style="color:red; font-weight:bold;">Username already taken.</span>';
});
$app->get('/isemailregistered/:email', function($email) use ($app) {
    $row = DB::queryFirstRow("SELECT * FROM users WHERE email=%s", $email);
    echo !$row ? "" : '<span style="background-color: red; font-weight: bold;">Email already taken</span>';
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

//////My Drive///////
$app->get('/yourdrive', function() use ($app, $log) {
    
    $app->render('yourdrive.html.twig');
});


