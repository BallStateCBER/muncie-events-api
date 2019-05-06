<?php
/**
 * Routes configuration
 *
 * In this file, you set up routes to your controllers and their actions.
 * Routes are very important mechanism that allows you to freely connect
 * different URLs to chosen controllers and their actions (functions).
 *
 * CakePHP(tm) : Rapid Development Framework (https://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 * @link          https://cakephp.org CakePHP(tm) Project
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */

use Cake\Routing\RouteBuilder;
use Cake\Routing\Router;
use Cake\Routing\Route\DashedRoute;

/**
 * The default class to use for all routes
 *
 * The following route classes are supplied with CakePHP and are appropriate
 * to set as the default:
 *
 * - Route
 * - InflectedRoute
 * - DashedRoute
 *
 * If no call is made to `Router::defaultRouteClass()`, the class used is
 * `Route` (`Cake\Routing\Route\Route`)
 *
 * Note that `Route` does not do any inflections on URLs which will result in
 * inconsistently cased URLs when used with `:plugin`, `:controller` and
 * `:action` markers.
 *
 */
Router::defaultRouteClass(DashedRoute::class);

Router::scope('/', function (RouteBuilder $routes) {
    $routes->connect('/', ['controller' => 'Pages', 'action' => 'home']);
    $routes->connect('/api', ['controller' => 'Pages', 'action' => 'api']);
    $routes->redirect('/docs', '/docs/v1');
    $routes->connect('/docs/v1', ['controller' => 'Pages', 'action' => 'docsV1']);
    $routes->connect('/register', ['controller' => 'Users', 'action' => 'register']);
    $routes->connect('/login', ['controller' => 'Users', 'action' => 'login']);
    $routes->connect('/logout', ['controller' => 'Users', 'action' => 'logout']);
    $routes->connect('/api-key', ['controller' => 'Users', 'action' => 'apiKey']);

    /**
     * Connect catchall routes for all controllers.
     *
     * Using the argument `DashedRoute`, the `fallbacks` method is a shortcut for
     *    `$routes->connect('/:controller', ['action' => 'index'], ['routeClass' => 'DashedRoute']);`
     *    `$routes->connect('/:controller/:action/*', [], ['routeClass' => 'DashedRoute']);`
     *
     * Any route class can be used with this method, such as:
     * - DashedRoute
     * - InflectedRoute
     * - Route
     * - Or your own route class
     *
     * You can remove these routes once you've connected the
     * routes you want in your application.
     */
    $routes->fallbacks(DashedRoute::class);
});

// API
Router::prefix('v1', function (RouteBuilder $routes) {
    $routes->fallbacks(DashedRoute::class);

    // EventsController
    $routes->post('/event', ['controller' => 'Events', 'action' => 'add']);
    $routes->get('/event/:id', ['controller' => 'Events', 'action' => 'view'])
        ->setPass(['id'])
        ->setPatterns(['id' => '[0-9]+']);
    $routes->patch('/event/:id', ['controller' => 'Events', 'action' => 'edit'])
        ->setPass(['id'])
        ->setPatterns(['id' => '[0-9]+']);
    $routes->delete('/event/:id', ['controller' => 'Events', 'action' => 'delete'])
        ->setPass(['id'])
        ->setPatterns(['id' => '[0-9]+']);

    // EventSeriesController
    $routes->get('/event-series/:id', ['controller' => 'EventSeries', 'action' => 'view'])
        ->setPass(['id'])
        ->setPatterns(['id' => '[0-9]+'])
        ->setHost('api.*');
    $routes->delete('/event-series/:id', ['controller' => 'EventSeries', 'action' => 'delete'])
        ->setPass(['id'])
        ->setPatterns(['id' => '[0-9]+'])
        ->setHost('api.*');

    // ImagesController
    $routes->connect('/image', ['controller' => 'Images', 'action' => 'add'])
        ->setHost('api.*');

    // MailingListController
    $routes->get('/mailing-list/subscription', ['controller' => 'MailingList', 'action' => 'subscriptionStatus'])
        ->setHost('api.*');
    $routes->put('/mailing-list/subscription', ['controller' => 'MailingList', 'action' => 'subscriptionUpdate'])
        ->setHost('api.*');
    $routes->delete('/mailing-list/subscription', ['controller' => 'MailingList', 'action' => 'unsubscribe'])
        ->setHost('api.*');

    // TagsController
    $routes->connect('/tag/*', ['controller' => 'Tags', 'action' => 'view'])
        ->setHost('api.*');

    // UsersController
    $routes->connect('/user/register', ['controller' => 'Users', 'action' => 'register'])
        ->setHost('api.*');
    $routes->connect('/user/login', ['controller' => 'Users', 'action' => 'login'])
        ->setHost('api.*');
    $routes->connect('/user/forgot-password', ['controller' => 'Users', 'action' => 'forgotPassword'])
        ->setHost('api.*');
    $routes->connect('/user/:id', ['controller' => 'Users', 'action' => 'view'])
        ->setPass(['id'])
        ->setPatterns(['id' => '[0-9]+'])
        ->setHost('api.*');
    $routes->connect('/user/', ['controller' => 'Users', 'action' => 'view', null])
        ->setHost('api.*');
    $routes->connect('/user/images', ['controller' => 'Users', 'action' => 'images', null])
        ->setHost('api.*');
    $routes->connect('/user/:id/events', ['controller' => 'Users', 'action' => 'events'])
        ->setPass(['id'])
        ->setPatterns(['id' => '[0-9]+'])
        ->setHost('api.*');
    $routes->connect('/user/password', ['controller' => 'Users', 'action' => 'password'])
        ->setHost('api.*');
    $routes->connect('/user/profile', ['controller' => 'Users', 'action' => 'profile'])
        ->setHost('api.*');
});
