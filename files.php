<?php

// fake $app, $log so that Netbeans can provide suggestions while typing code
if (false) {
    $app = new \Slim\Slim();
    $log = new Logger('main');
}

if (!isset($_SESSION['user'])) {
    $_SESSION['user'] = array();
}


// Veiw of list of files 
$app->get('/list', function() use ($app) {
    if (!$_SESSION['user']) {
        $app->render("access_denied.html.twig");
        return;
    }
    $userId = $_SESSION['user']['id'];
    $fileList = DB::query("SELECT * FROM files "
            . "WHERE userid =%i", $userId);
    $app->render("/file_list.html.twig", array('list' => $fileList));
});

// Delete File 
$app->get('/file/delete/:id', function($id) use ($app) {
    if (!$_SESSION['user'] ) {
        $app->render("access_denied.html.twig");
        return;
    }
    $file = DB::queryFirstRow("SELECT * FROM files WHERE id=%d" , $id);
    if (!$file) {
        $app->render("/not_found.html.twig");
        return;
    }
    $app->render("/file_delete.html.twig", array('f' => $file));
});

$app->post('/file/delete/:id', function($id) use ($app) {
    if (!$_SESSION['user']) {
        $app->render("access_denied.html.twig");
        return;
    }
    $confirmed = $app->request()->post('confirmed');
    if ($confirmed != 'true') {
        $app->render('/not_found.html.twig');
        return;
    }
    DB::delete('files', "id=%i", $id);
    if (DB::affectedRows() == 0) {
        $app->render('/not_found.html.twig');
    } else {
        $app->render('/file_delete_success.html.twig');
    }
});


// Add-Edit File
$app->get('/file/:op(/:id)', function($op, $id = -1) use ($app) {
    if (!$_SESSION['user'] ){ 
        $app->render('access_denied.html.twig');
        return;
    }
    if (($op == 'add' && $id != -1) || ($op == 'edit' && $id == -1)) {
        $app->render('/not_found.html.twig');
        return;
    }
    //
    if ($id != -1) {
        $values = DB::queryFirstRow('SELECT * FROM files WHERE id=%i', $id);
        if (!$values) {
            $app->render('/not_found.html.twig');
            return;
        }
    } else { 
        $values = array();
    }
    $app->render('/file_addedit.html.twig', array(
        'f' => $values,
        'isEditing' => ($id != -1)
    ));
})->conditions(array(
    'op' => '(edit|add)',
    'id' => '\d+'
));

$app->post('/file/:op(/:id)', function($op, $id = -1) use ($app, $log) {
    if (!$_SESSION['user']) {
        $app->render('access_denied.html.twig');
        return;
    }
    if (($op == 'add' && $id != -1) || ($op == 'edit' && $id == -1)) {
        $app->render('/not_found.html.twig');
        return;
    }
   
//    $filename = $_FILES['filename'];
//    $values = array('filename' => $filename);
//    $errorList = array();
   
    //var_dump($file);
    $file = $_FILES['filename'];
    $filename = $file['name'];
    
    $ext = pathinfo($filename, PATHINFO_EXTENSION);
    //var_dump($ext);
    //die();
    $uniqueName =  md5($file['tmp_name'] . time());
    $destinationDir = 'uploads';
    
    move_uploaded_file($file['tmp_name'], $destinationDir . '/' . $uniqueName .'.' . $ext);
  
    //
    $values = array('filename' => $filename, 'secretdir' => $uniqueName);
    $errorList = array();
    //
    
    // add the condition for adding file
    
    
    if ($errorList) {
     
        $app->render("/file_addedit.html.twig", array(
            "errorList" => $errorList,
            'isEditing' => ($id != -1),
            'f' => $values));
    } else { // 2. successful submission
       
        if ($id != -1) {
            DB::update('files', $values, "id=%i", $id);
        } else {
//            $values = DB::queryFirstRow('SELECT * FROM folders WHERE id=%i', $id);
            $values['userid'] = $_SESSION['user']['id'];
            DB::insert('files', $values);
        }
        $app->render('/file_addedit_success.html.twig', array('isEditing' => ($id != -1)));
    }
})->conditions(array(
    'op' => '(edit|add)',
    'id' => '\d+'
)); // End of add-edit file

////choose user

$app->get('/file/chooseuser/:id', function($id) use ($app) {
    if (!$_SESSION['user'] ) {
        $app->render("access_denied.html.twig");
        return;
    }
    if (!$_SESSION['user'] ) {
        echo 'Access denied';
        return;
    }
    //
    $usersList = DB::query("SELECT * FROM users");
     $app->render('file_chooseuser.html.twig', array('list' => $usersList));
});


$app->post('/file/chooseuser/:id', function($id) use ($app) {
    //echo 'In file chooser post';
if (!$_SESSION['user']) {
        $app->render("access_denied.html.twig");
        return;
    }
    $user = $app->request()->post('user');
    if (!$user ) {
        $app->render('/not_found.html.twig');
        echo 'In file chooser post';
        return;
    }
    
     DB::insert('shares', array('fileid' => $id, 'usrid' => $user));
    if (DB::affectedRows() == 0) {
        $app->render('/not_found.html.twig');
    } else {
        $app->render('/file_chooseuser.html.twig');
    }
});

