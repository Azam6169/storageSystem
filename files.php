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
    $fileList = DB::query("SELECT users.name, folders.name, files.filename FROM users, folders, files "
            . "WHERE users.id =%i", $userId);
    $app->render("/files_list.html.twig", array('list' => $fileList));
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
        'v' => $values,
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
   
    $filename = $_FILES['filename'];
    $values = array('filename' => $filename);
    $errorList = array();
   
    //var_dump($file);
    $$filename = $file['filename'];
    
    $ext = pathinfo($$filename, PATHINFO_EXTENSION);
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
            'v' => $values));
    } else { // 2. successful submission
       
        if ($id != -1) {
            DB::update('files', $values, "id=%i", $id);
        } else {
            $values['folderid'] = $_SESSION['user']['id'];
            DB::insert('files', $values);
        }
        $app->render('/file_addedit_success.html.twig', array('isEditing' => ($id != -1)));
    }
})->conditions(array(
    'op' => '(edit|add)',
    'id' => '\d+'
)); // End of add-edit file

