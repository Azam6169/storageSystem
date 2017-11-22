<?php

if (false) {
    $app = new \Slim\Slim();
    $log = new Logger('main');
}


$app->get('/search', function() use ($app, $log) {
    if (!$_SESSION['user']) {
        $app->render("access_denied.html.twig");
        return;
    }

    $app->render('/searchgooglemap.html.twig');
});

$app->post('/search', function() use ($app, $log) {
    if (!$_SESSION['user']) {
        $app->render("access_denied.html.twig");
        return;
    }

    $filename = $app->request()->post('filename');

    $values = DB::query('SELECT * from files WHERE filename LIKE %ss', $filename);

    $list = DB::query('SELECT * from files WHERE filename BETWEEN %d AND %d ', $filename);
    $app->render('/searchgooglemap.html.twig', array('f' => $list, 'p' => $values));
});
