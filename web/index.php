<?php

// vendor
require_once __DIR__.'/../vendor/autoload.php';

// http
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

// app
$app = new Silex\Application();

// server
/*$filename = __DIR__.preg_replace('#(\?.*)$#', '', $_SERVER['REQUEST_URI']);
if (php_sapi_name() === 'cli-server' && is_file($filename)) {
    return false;
}

$app = require __DIR__.'/../web/index.php';*/

// headers
$app->after(function (Request $request, Response $response) {
    $response->headers->set('Access-Control-Allow-Origin', '*');
    $response->headers->set('Access-Control-Allow-Headers', 'Authorization');
    $response->headers->set('Access-Control-Allow-Methods', '*');
});

// handles OPTIONS problem
$app->options("{anything}", function () {
            return new \Symfony\Component\HttpFoundation\JsonResponse(null, 204);
    })->assert("anything", ".*");

$app['debug'] = true;

// mySQL DB Connection
$app->register(new Silex\Provider\DoctrineServiceProvider(), array(
    'db.options' => array(
        'driver'    => 'pdo_mysql',
        'host'      => '127.0.0.1',
        'dbname'    => 'youtube_bookmarks',
        'user'      => 'root',
        'password'  => 'root',
        'charset'   => 'utf8mb4',
    ),
));

// get all videos
$app->get('/api/videos', function () use ($app) {
    $sql = 'SELECT * FROM videos';
    $videos = $app['db']->fetchAll($sql);

    return json_encode($videos);
});

// get all bookmarks
$app->get('/api/bookmarks', function () use ($app) {
    $sql = 'SELECT * FROM videos WHERE bookmarked = 1';
    $videos = $app['db']->fetchAll($sql);

    return json_encode($videos);
});

// update video (add/remove bookmarks)
$app->put('/api/bookmarks/{nid}', function (Request $request, $nid) use ($app){
    $v = $request->get('v');

    $sql = "UPDATE videos SET bookmarked = ? WHERE nid = ?";
    $app['db']->executeUpdate($sql, array(
        (int ) $v, (int) $nid
    ));

    return new Response('Video modified', 201);
});

// post 1 video
$app->post('/api/videos', function (Request $request) use ($app) {
    $id             = $request->get('id');
    $bookmarked     = $request->get('bookmarked');

    $app['db']->insert('videos', array(
        'id'    => $id,
        'bookmarked'  => $bookmarked
    ));

    $idR = $app['db']->lastInsertId();
    $sql = 'SELECT * FROM videos WHERE id = ?';
    $videos = $app['db']->fetchAssoc($sql, array(
        (int) $idR
    ));

    return json_encode($videos);

});

// running app
$app->run();