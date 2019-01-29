<?php
namespace App\Controller\V1;

use App\Controller\ApiController;
use App\Model\Entity\User;
use App\Model\Table\UsersTable;
use Cake\Auth\FormAuthenticate;
use Cake\Controller\ComponentRegistry;
use Cake\Http\Exception\BadRequestException;
use Cake\Http\Exception\MethodNotAllowedException;
use Cake\Mailer\MailerAwareTrait;
use Cake\ORM\TableRegistry;
use Cake\Utility\Hash;

/**
 * Class UsersController
 * @package App\Controller
 * @property UsersTable $Users
 */
class UsersController extends ApiController
{
    use MailerAwareTrait;

    /**
     * /user/register endpoint
     *
     * @return void
     * @throws BadRequestException
     * @throws MethodNotAllowedException
     */
    public function register()
    {
        $this->request->allowMethod('post');

        $user = $this->Users->newEntity($this->request->getData(), [
            'fields' => ['name', 'email', 'password']
        ]);
        $user->role = 'user';
        $user->token = User::generateToken();

        if (!$this->Users->save($user)) {
            throw new BadRequestException(
                'There was an error registering. Details: ' . print_r($user->getErrors(), true)
            );
        }

        // Recreate entity so that only specific fields are visible
        $user = $this->Users
            ->find()
            ->select(['id', 'name', 'email', 'token'])
            ->where(['id' => $user->id])
            ->first();

        $this->set([
            '_entities' => ['User'],
            '_serialize' => ['user'],
            'user' => $user
        ]);
    }

    /**
     * /user/login endpoint
     *
     * @return void
     * @throws BadRequestException
     * @throws MethodNotAllowedException
     */
    public function login()
    {
        $this->request->allowMethod('post');

        foreach (['email', 'password'] as $field) {
            if (!$this->request->getData($field)) {
                throw new BadRequestException('The parameter "' . $field . '" is required');
            }
        }

        $user = $this->getUserFromLoginCredentials();
        if (!$user) {
            throw new BadRequestException('Email or password is incorrect');
        }

        // Convert user array into user entity, as required by JsonApi view
        /** @var User $user */
        $user = $this->Users
            ->find()
            ->select(['id', 'name', 'email', 'token'])
            ->where(['id' => $user['id']])
            ->first();
        if (!$user->token) {
            $user = $this->Users->addToken($user);
        }

        $this->set([
            '_entities' => ['User'],
            '_serialize' => ['user'],
            'user' => $user
        ]);
    }

    /**
     * Identifies a user based on email and password
     *
     * @return array|bool
     */
    private function getUserFromLoginCredentials()
    {
        $registry = new ComponentRegistry();
        $config = [
            'fields' => ['username' => 'email'],
            'passwordHasher' => [
                'className' => 'Fallback',
                'hashers' => ['Default', 'Legacy']
            ]
        ];
        $auth = new FormAuthenticate($registry, $config);

        return $auth->authenticate($this->getRequest(), $this->response);
    }

    /**
     * /user/{userId} endpoint
     *
     * @param int $userId User ID
     * @return void
     * @throws BadRequestException
     * @throws MethodNotAllowedException
     */
    public function view($userId = null)
    {
        $this->request->allowMethod('get');

        $user = $this->Users
            ->find()
            ->select(['id', 'name', 'email'])
            ->where(['id' => $userId])
            ->first();
        if (!$user) {
            throw new BadRequestException('User not found');
        }

        $this->set([
            '_entities' => ['User'],
            '_serialize' => ['user'],
            'user' => $user
        ]);
    }

    /**
     * /user/forgot-password endpoint
     *
     * @return void
     * @throws BadRequestException
     * @throws MethodNotAllowedException
     */
    public function forgotPassword()
    {
        $this->request->allowMethod('post');

        $email = $this->request->getData('email');
        $email = trim($email);
        $email = mb_strtolower($email);
        if (empty($email)) {
            throw new BadRequestException('Please provide an email address');
        }

        $user = $this->Users
            ->find()
            ->where(['email' => $email])
            ->first();
        if (!$user) {
            throw new BadRequestException('No account was found matching that email address');
        }

        $this->getMailer('Users')->send('forgotPassword', [$user]);

        $this->response = $this->response->withStatus(204, 'No Content');

        /* Bypass JsonApi plugin to render blank response,
         * as required by the JSON API standard (https://jsonapi.org/format/#crud-creating-responses-204) */
        $this->viewBuilder()->setClassName('Json');
        $this->set('_serialize', true);
    }

    /**
     * /user/images endpoint
     *
     * @return void
     * @throws BadRequestException
     * @throws MethodNotAllowedException
     */
    public function images()
    {
        $this->request->allowMethod('get');
        if (!$this->tokenUser) {
            throw new BadRequestException('User token missing');
        }
        $imagesTable = TableRegistry::getTableLocator()->get('Images');
        $images = $imagesTable
            ->find()
            ->where(['user_id' => $this->tokenUser->id])
            ->orderDesc('created')
            ->all();

        $this->set([
            '_entities' => ['Image'],
            '_serialize' => ['images'],
            'images' => $images
        ]);
    }

    /**
     * /user/profile endpoint
     *
     * @return void
     * @throws BadRequestException
     * @throws MethodNotAllowedException
     */
    public function profile()
    {
        $this->request->allowMethod('patch');
        if (!$this->tokenUser) {
            throw new BadRequestException('User token missing');
        }

        $updatedName = $this->request->getData('name');
        $updatedEmail = $this->request->getData('email');
        if ($updatedName === null && $updatedEmail === null) {
            throw new BadRequestException('Either \'name\' or \'email\' parameters must be provided');
        }

        $user = $this->tokenUser;
        $data = [];
        if ($updatedName !== null) {
            $data['name'] = $updatedName;
        }
        if ($updatedEmail !== null) {
            $data['email'] = $updatedEmail;
        }
        $this->Users->patchEntity($user, $data);
        if (!$this->Users->save($user)) {
            $errors = $user->getErrors();
            $messages = Hash::extract($errors, '{s}.{s}');
            throw new BadRequestException('There was an error updating your profile. Details: ' . implode('; ', $messages));
        }

        /* Bypass JsonApi plugin to render blank response,
         * as required by the JSON API standard (https://jsonapi.org/format/#crud-creating-responses-204) */
        $this->viewBuilder()->setClassName('Json');
        $this->set('_serialize', true);
    }
}
