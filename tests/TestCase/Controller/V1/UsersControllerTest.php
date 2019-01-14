<?php
namespace App\Test\TestCase\Controller\V1;

use App\Test\Fixture\UsersFixture;
use App\Test\TestCase\ApplicationTest;
use Cake\TestSuite\EmailTrait;
use Cake\TestSuite\TestEmailTransport;

/**
 * UsersControllerTest class
 */
class UsersControllerTest extends ApplicationTest
{
    use EmailTrait;

    /**
     * Fixtures
     *
     * @var array
     */
    public $fixtures = [
        'app.ApiCalls',
        'app.Categories',
        'app.EventSeries',
        'app.Events',
        'app.EventsImages',
        'app.EventsTags',
        'app.Images',
        'app.Tags',
        'app.Users'
    ];

    /**
     * Method for cleaning up after each test
     *
     * @return void
     */
    public function tearDown()
    {
        parent::tearDown();

        // Clean up previously sent emails for the next test
        TestEmailTransport::clearEmails();
    }

    /**
     * Tests that /user/register succeeds with valid data
     *
     * @return void
     * @throws \PHPUnit\Exception
     */
    public function testRegisterSuccess()
    {
        $url = [
            'prefix' => 'v1',
            'controller' => 'Users',
            'action' => 'register',
            '?' => ['apikey' => $this->getApiKey()]
        ];
        $data = [
            'name' => 'New User Name',
            'email' => 'newuser@example.com',
            'password' => 'password'
        ];
        $this->post($url, $data);
        $this->assertResponseOk();

        $response = (array)json_decode($this->_response->getBody());
        $attributes = $response['data']->attributes;
        $this->assertNotEmpty($response['data']->id);
        $this->assertEquals($data['name'], $attributes->name);
        $this->assertEquals($data['email'], $attributes->email);
        $this->assertNotEmpty($attributes->token);
    }

    /**
     * Tests that /user/register fails for non-POST requests
     *
     * @return void
     * @throws \PHPUnit\Exception
     */
    public function testRegisterFailBadMethod()
    {
        $url = [
            'prefix' => 'v1',
            'controller' => 'Users',
            'action' => 'register',
            '?' => ['apikey' => $this->getApiKey()]
        ];

        $this->get($url);
        $this->assertResponseError();

        $this->put($url);
        $this->assertResponseError();

        $this->patch($url);
        $this->assertResponseError();

        $this->delete($url);
        $this->assertResponseError();
    }

    /**
     * Tests that /user/register fails with missing parameters
     *
     * @return void
     * @throws \PHPUnit\Exception
     */
    public function testRegisterFailMissingParams()
    {
        $url = [
            'prefix' => 'v1',
            'controller' => 'Users',
            'action' => 'register',
            '?' => ['apikey' => $this->getApiKey()]
        ];
        $data = [
            'name' => 'New User Name',
            'email' => 'newuser@example.com',
            'password' => 'password'
        ];

        foreach (array_keys($data) as $requiredField) {
            $partialData = $data;
            unset($partialData[$requiredField]);
            $this->post($url, $partialData);
            $this->assertResponseError();
        }
    }

    /**
     * Tests that /user/register fails for nonunique emails
     *
     * @return void
     * @throws \PHPUnit\Exception
     */
    public function testRegisterFailEmailNonunique()
    {
        $url = [
            'prefix' => 'v1',
            'controller' => 'Users',
            'action' => 'register',
            '?' => ['apikey' => $this->getApiKey()]
        ];
        $usersFixture = new UsersFixture();
        $email = $usersFixture->records[0]['email'];
        $data = [
            'name' => 'New User Name',
            'email' => $email,
            'password' => 'password'
        ];
        $this->post($url, $data);
        $this->assertResponseError();
    }

    /**
     * Tests successful response from /user/login
     *
     * @return void
     * @throws \PHPUnit\Exception
     */
    public function testLoginSuccess()
    {
        $url = [
            'prefix' => 'v1',
            'controller' => 'Users',
            'action' => 'login',
            '?' => ['apikey' => $this->getApiKey()]
        ];
        $usersFixture = new UsersFixture();

        $users = [
            'with new password hash' => $usersFixture->records[0],
            'with legacy password hash' => $usersFixture->records[2]
        ];

        $expectedFields = ['name', 'email', 'token'];
        foreach ($users as $type => $user) {
            $data = [
                'email' => $user['email'],
                'password' => 'password'
            ];
            $this->post($url, $data);
            $response = json_decode($this->_response->getBody())->data;
            $this->assertNotEmpty($response->id);
            foreach ($expectedFields as $expectedField) {
                $this->assertNotEmpty($response->attributes->$expectedField, ucwords($expectedField) . ' is empty');
            }
            $this->assertResponseOk();
        }
    }

    /**
     * Tests error response from /user/login with bad login credentials
     *
     * @return void
     * @throws \PHPUnit\Exception
     */
    public function testLoginFailBadCredentials()
    {
        $url = [
            'prefix' => 'v1',
            'controller' => 'Users',
            'action' => 'login',
            '?' => ['apikey' => $this->getApiKey()]
        ];
        $usersFixture = new UsersFixture();

        $data = [
            'email' => $usersFixture->records[0]['email'],
            'password' => 'password'
        ];
        foreach ($data as $field => $val) {
            $wrongData = $data;
            $wrongData[$field] .= 'bad data';
            $this->post($url, $wrongData);
            $this->assertResponseError();
        }
    }

    /**
     * Tests that /user/login fails for non-POST requests
     *
     * @return void
     * @throws \PHPUnit\Exception
     */
    public function testLoginFailBadMethod()
    {
        $url = [
            'prefix' => 'v1',
            'controller' => 'Users',
            'action' => 'login',
            '?' => ['apikey' => $this->getApiKey()]
        ];

        $this->get($url);
        $this->assertResponseError();

        $this->put($url);
        $this->assertResponseError();

        $this->patch($url);
        $this->assertResponseError();

        $this->delete($url);
        $this->assertResponseError();
    }

    /**
     * Tests successful use of /user/{userId}
     *
     * @return void
     * @throws \PHPUnit\Exception
     */
    public function testViewSuccess()
    {
        $userId = 1;
        $url = [
            'prefix' => 'v1',
            'controller' => 'Users',
            'action' => 'view',
            $userId,
            '?' => ['apikey' => $this->getApiKey()]
        ];

        $this->get($url);
        $this->assertResponseOk();

        $expectedFields = ['name', 'email'];
        $response = json_decode($this->_response->getBody())->data;
        $this->assertNotEmpty($response->id);
        foreach ($expectedFields as $expectedField) {
            $this->assertNotEmpty($response->attributes->$expectedField, ucwords($expectedField) . ' is empty');
        }
    }

    /**
     * Tests that /user/{userId} fails for non-GET requests
     *
     * @return void
     * @throws \PHPUnit\Exception
     */
    public function testViewFailBadMethod()
    {
        $userId = 1;
        $url = [
            'prefix' => 'v1',
            'controller' => 'Users',
            'action' => 'view',
            $userId,
            '?' => ['apikey' => $this->getApiKey()]
        ];

        $this->post($url);
        $this->assertResponseError();

        $this->put($url);
        $this->assertResponseError();

        $this->patch($url);
        $this->assertResponseError();

        $this->delete($url);
        $this->assertResponseError();
    }

    /**
     * Tests that /user/{userId} fails for invalid or missing user IDs
     *
     * @return void
     * @throws \PHPUnit\Exception
     */
    public function testViewFailInvalidUser()
    {
        $userId = 999;
        $url = [
            'prefix' => 'v1',
            'controller' => 'Users',
            'action' => 'view',
            $userId,
            '?' => ['apikey' => $this->getApiKey()]
        ];

        $this->get($url);
        $this->assertResponseError();

        $url = [
            'prefix' => 'v1',
            'controller' => 'Users',
            'action' => 'view',
            null,
            '?' => ['apikey' => $this->getApiKey()]
        ];

        $this->get($url);
        $this->assertResponseError();
    }

    /**
     * Tests that /v1/users/forgot-password returns the correct success status code
     *
     * @return void
     * @throws \PHPUnit\Exception
     */
    public function testForgotPasswordSuccess()
    {
        $url = [
            'prefix' => 'v1',
            'controller' => 'Users',
            'action' => 'forgotPassword',
            '?' => ['apikey' => $this->getApiKey()]
        ];
        $user = (new UsersFixture())->records[0];
        $this->post($url, ['email' => $user['email']]);

        $this->assertResponseCode(204);
    }

    /**
     * Tests that /v1/users/forgot-password fails for invalid email addresses
     *
     * @return void
     * @throws \PHPUnit\Exception
     */
    public function testForgotPasswordFailUnknownUser()
    {
        $url = [
            'prefix' => 'v1',
            'controller' => 'Users',
            'action' => 'forgotPassword',
            '?' => ['apikey' => $this->getApiKey()]
        ];
        $user = (new UsersFixture())->records[0];
        $this->post($url, ['email' => 'invalid' . $user['email']]);

        $this->assertResponseError();
    }

    /**
     * Tests that /v1/users/forgot-password fails if email address is missing or blank
     *
     * @return void
     * @throws \PHPUnit\Exception
     */
    public function testForgotPasswordFailMissingEmail()
    {
        $url = [
            'prefix' => 'v1',
            'controller' => 'Users',
            'action' => 'forgotPassword',
            '?' => ['apikey' => $this->getApiKey()]
        ];
        $this->post($url, ['email' => '']);
        $this->assertResponseError();

        $this->post($url, []);
        $this->assertResponseError();
    }
}
