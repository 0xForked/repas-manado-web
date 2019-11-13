<?php

use Illuminate\Database\Capsule\Manager as Eloquent;
use Respect\Validation\Validator as RespectValidation;
use PHPMailer\PHPMailer\PHPMailer;
use App\Base\Validations\Validator;
use App\Base\Mailers\Mailer;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use Slim\Views\Twig;
use Slim\Views\TwigExtension;
use Slim\Flash\Messages;
use App\Base\Auth;
use App\Base\Helper\ImageHelper;
use App\Base\Helper\StringHelper;
use Slim\Csrf\Guard;

use App\Http\Middlewares\ValidationErrorsMiddlerware as ValidatorMidd;
use App\Http\Middlewares\Authentication as AuthMidd;

/*
|----------------------------------------------------
| Slim Container                                    |
|----------------------------------------------------
*/

    $container = $app->getContainer();

/*
|----------------------------------------------------
| Eloquent ORM                                      |
|----------------------------------------------------
*/

    $capsule =  new Eloquent();
    $capsule->addConnection(
        [
            'driver' => 'mysql',
            'host' => '178.128.62.114',
            'port' => '3306',
            'database' => 'repas_manado',
            'username' => 'repas',
            'password' => "repas.password",
            'charset' => 'utf8',
            'collation' => 'utf8_unicode_ci',
            'prefix' => '',
            'strict' => true,
            'engine' => null,
        ]
    );
    $capsule->setAsGlobal();
    $capsule->bootEloquent();

/*
|----------------------------------------------------
| Monolog Logger                                    |
|----------------------------------------------------
*/

    $container['logger'] = function ($c) {
        $settings = $c->get('settings')['logger'];
        $logger = new Logger($settings['name']);
        $logger->pushProcessor(new Monolog\Processor\UidProcessor());
        $logger->pushHandler(new Monolog\Handler\StreamHandler($settings['path'], $settings['level']));
        return $logger;
    };

/*
|----------------------------------------------------
| Respect Validator                                 |
|----------------------------------------------------
*/

    $container['validator'] = function ($container) {
        return new Validator($container);
    };

    RespectValidation::with('Src\\Base\\Validations\\Rules\\');

/*
|----------------------------------------------------
| Mailer                                            |
|----------------------------------------------------
*/
    $container['mailer'] = function ($container) {
        $mailer = new PHPMailer();
        //$mailer->SMTPDebug = 3;
        $mailer->isSMTP();
        $mailer->SMTPOptions = array(
            'ssl' => array(
                'verify_peer' => false,
                'verify_peer_name' => false,
                'allow_self_signed' => true
            )
        );
        //$mailer->Host = 'tsl://smtp.gmail.com:587';
        $mailer->Host = 'ssl://smtp.gmail.com:465';
        $mailer->SMTPAuth = true;
        $mailer->Username = env('MAIL_ADR', 'hello@fokipoke.com');
        $mailer->Password = env('MAIL_PWD', 'password');
        $mailer->setFrom('fookipoke@gmail.com', 'FookiPoke Studio');
        $mailer->isHtml(true);
        return new \App\Base\Mailer\Mail($container->view, $mailer);
    };


/*
|----------------------------------------------------
|  SLIM CSRF                                        |
|----------------------------------------------------
*/

    $container['csrf'] = function ($container) {
        return new Guard;
    };

/*
|----------------------------------------------------
|  Auth                                             |
|----------------------------------------------------
*/

    $container['auth'] = function ($container) {
        return new Auth;
    };

/*
|----------------------------------------------------
|  Image                                            |
|----------------------------------------------------
*/

    $container['imageHelper'] = function ($container) {
        return new ImageHelper;
    };


/*
|----------------------------------------------------
|  Flash                                            |
|----------------------------------------------------
*/

    $container['flash'] = function ($container) {
        return new Messages;
    };

/*
|----------------------------------------------------
| Twig & View                                       |
|----------------------------------------------------
*/

    $container['view'] = function ($container)
    {
        $view = new Twig(
            __DIR__ . '/../../resources/views/',
            [ 'cache' => false ]
        );

        $basePath = rtrim(str_ireplace('index.php', '',
            $container['request']->getUri()->getBasePath()), '/'
        );

        $view->addExtension(new TwigExtension($container['router'], $basePath));

        $view->getEnvironment()->addGlobal('auth', [
            'check' => $container->auth->check(),
            'user' => $container->auth->user()
        ]);

        $view->getEnvironment()->addGlobal('flash', $container->flash);

        return $view;
    };

    $container['notFoundHandler'] = function ($container)
    {
        return function ($request, $response) use ($container)
        {
            return $container->view->render($response, '/error/404.twig');
        };
    };


/*
|----------------------------------------------------
| Upload Files                                      |
|----------------------------------------------------
*/

    $container['uploadDirectory'] = __DIR__ . '/../../public/assets/img/uploads/';
