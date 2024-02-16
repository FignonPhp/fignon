<?php
/**
 * The purpose of this file is to served as the entry for test environnement
 */
declare(strict_types=1);

include_once __DIR__ . "/../vendor/autoload.php";

use Fignon\Middlewares\BodyParser;
use Fignon\Tunnel;
use Fignon\Request\Request;
use Fignon\Response\Response;
use Fignon\Middlewares\Griot;
use Fignon\Extra\TwigEngine;

$app = new Tunnel();

$app->setFrom(dirname(__DIR__) . '/tests-data/.env.local.php');
$app->setFromEnv();
$app->setFromJson(dirname(__DIR__) . '/tests-data/config.json');
$app->setFromYaml(dirname(__DIR__) . '/tests-data/config.yaml');

$app->set('env', 'development');
$app->set('baseUrl', 'http://localhost:9000');
$app->set('debug', true);
$app->set('trust proxy', true);
$app->set('x-powered-by', false);

// View engine initialization
$app->set('views', dirname(__DIR__) . '/tests-data/templates');
$app->set('views cache', dirname(__DIR__) . '/tests-data/var/cache');
$app->set('view engine options', []); // Some view engine
$app->engine('twig', new TwigEngine());


$app->set('case sensitive routing', true);
$app->set('always decode urls', true);
$app->set('foo.bar', true);
$app->locals['title'] = "Fignon";
$app->locals['env'] = "Env";
$app->use(new BodyParser());
$app->use(new Griot());


$app->use(function (Request $req, Response $res, $next) {
    $req->addData('any', 'Method and any path');
    $next();
})->as('use_any');

$app->get('/', function (Request $req, Response $res) {
    $res->status(200)->render('home.twig.html', [
        'title' => 'Page d\'accueil',
        'heading' => 'Bienvenue sur notre site',
        'message' => 'Contenu de la page d\'accueil',
    ]);
})->as('get_home');

$app->get('/users', function (Request $req, Response $res) {
    $users = [
        ['name' => 'John', 'age' => 21],
        ['name' => 'Jane', 'age' => 22],
        ['name' => 'Jack', 'age' => 23],
        ['name' => 'Jill', 'age' => 24],
    ];
    $res->status(200)->render('users.twig.html', ['users' => $users]);
})->as('get_user');


$app->get('/json', function (Request $req, Response $res) {
    $res->status(200)->json(['message' => 'JSON Response', 'body' => $req->body ?? []]);
})->as('get_json');


$app->get('/with-params/:id', function (Request $req, Response $res, $next) {
    $id1 = $req->params['id'];
    $id2 = $req->param('id');
    $id3 = $req->p('id');

    // Let get the name and email query
    $name = $req->query('name');
    $email = $req->q('email');
    $res->status(200)->html("With Params $id1 $id2 $id3 <br> Name: $name <br> Email: $email");
})->as('get_with_params');


$app->get('/with-query', function (Request $req, Response $res) {
    $res->status(200)->html("With Query " . $req->query->get('id'));
})->as('get_with_query');

$app->listen();
