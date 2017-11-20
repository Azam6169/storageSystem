<?php

if (false) {
    $app = new \Slim\Slim();
    $log = new Logger('main');
}


$app->get('/search', function() use ($app, $log) {
//    if (!$_SESSION['user']) {
//        $app->render("access_denied.html.twig");
//        return;
//    }
    $app->render('/searchgooglemap.html.twig');
});

$app->post('/search', function() use ($app, $log) {
//    if (!$_SESSION['user']) {
//        $app->render("access_denied.html.twig");
//        return;
//    }
    $latA = $app->request()->post('latA');
    $latB = $app->request()->post('latB');
    $longA = $app->request()->post('longA');
    $longB = $app->request()->post('longB');
    $price = $app->request()->post('price');
    
   // $values = array('latA' => $latA, 'latB' => $latB, 'longA' => $longA, 'longB' => $longB);
   

       $values = DB::query('SELECT * from files WHERE filename LIKE %ss', $filename);

       $list = DB::query('SELECT * from files WHERE latitude BETWEEN %d AND %d AND longitude BETWEEN  %d AND %d', $latA, $latB,$longA,$longB);
    $app->render('/searchgooglemap.html.twig', array('f' => $list, 'p'=>$values));
});
