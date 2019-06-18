<?php
namespace App\Controller;

use App\Model\Entity\User;
use App\Model\Table\EventsTable;
use Cake\Controller\Controller;
use Cake\Event\Event;
use Cake\Http\Response;
use Exception;

/**
 * Class AppController
 *
 * @package App\Controller
 * @property EventsTable $Events
 */
class AppController extends Controller
{
    /**
     * Initialization hook method
     *
     * @return void
     * @throws Exception
     */
    public function initialize()
    {
        parent::initialize();

        $this->loadComponent('RequestHandler', [
            'enableBeforeRedirect' => false
        ]);
        $this->loadComponent('Flash');
        $this->loadComponent(
            'Auth',
            [
                'loginAction' => [
                    'prefix' => false,
                    'controller' => 'Users',
                    'action' => 'login'
                ],
                'logoutRedirect' => [
                    'prefix' => false,
                    'controller' => 'Pages',
                    'action' => 'home'
                ],
                'authenticate' => [
                    'Form' => [
                        'fields' => [
                            'username' => 'email',
                            'password' => 'password'
                        ],
                        'passwordHasher' => [
                            'className' => 'Fallback',
                            'hashers' => [
                                'Default',
                                'Weak' => ['hashType' => 'sha1']
                            ]
                        ]
                    ],
                    'Cookie' => [
                        'fields' => [
                            'username' => 'email',
                            'password' => 'password'
                        ]
                    ]
                ],
                'authError' => 'You are not authorized to view this page',
                'authorize' => 'Controller'
            ]
        );
        $this->Auth->deny();
    }

    /**
     * beforeFilter method
     *
     * @param Event $event CakePHP event object
     * @return void
     */
    public function beforeFilter(Event $event)
    {
        if (!$this->Auth->user() && $this->request->getCookie('CookieAuth')) {
            $this->loadModel('Users');
            $user = $this->Auth->identify();
            if ($user) {
                $this->Auth->setUser($user);
            } else {
                $this->response = $this->response->withExpiredCookie('CookieAuth');
            }
        }
    }

    /**
     * Before render callback.
     *
     * @param Event $event The beforeRender event.
     * @return Response|null|void
     */
    public function beforeRender(Event $event)
    {
        $this->loadModel('Events');
        $this->set([
            'authUser' => $this->Auth->user(),
            'unapprovedCount' => $this->Auth->user() ? $this->Events->getUnapprovedCount() : 0
        ]);
    }

    /**
     * isAuthorized method
     *
     * @param User $user User entity
     * @return bool
     */
    public function isAuthorized($user)
    {
        if (!$this->request->getParam('prefix')) {
            return true;
        }

        return false;
    }
}
