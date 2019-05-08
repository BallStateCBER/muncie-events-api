<?php
namespace App\Test\TestCase\Controller;

use Cake\Core\Configure;
use Cake\TestSuite\EmailTrait;
use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;
use PHPUnit\Exception;

/**
 * PagesControllerTest class
 */
class PagesControllerTest extends TestCase
{
    use IntegrationTestTrait;
    use EmailTrait;

    /**
     * Sets up this set of tests
     *
     * @return void
     */
    public function setUp()
    {
        parent::setUp();
        $this->configRequest([
            'environment' => ['HTTPS' => 'on']
        ]);
    }

    /**
     * Tests /api/docs/v1
     *
     * @return void
     * @throws Exception
     */
    public function testDocsV1()
    {
        $this->get([
            'controller' => 'Pages',
            'action' => 'apiDocsV1'
        ]);
        $this->assertResponseOk();
    }

    /**
     * Tests that /api/docs redirects to /api/docs/v1
     *
     * @return void
     * @throws Exception
     */
    public function testDocsRedirect()
    {
        $this->get('/api/docs');
        $this->assertRedirect([
            'controller' => 'Pages',
            'action' => 'apiDocsV1'
        ]);
    }

    /**
     * Tests that /api returns a successful response
     *
     * @return void
     * @throws Exception
     */
    public function testApi()
    {
        $this->get([
            'controller' => 'Pages',
            'action' => 'api'
        ]);
        $this->assertResponseOk();
    }

    /**
     * Test that the contact page loads
     *
     * @return void
     * @throws \PHPUnit\Exception
     */
    public function testContactPageGetSuccess()
    {
        $this->get([
            'controller' => 'Pages',
            'action' => 'contact'
        ]);
        $this->assertResponseOk();
        $this->assertResponseContains('site administrator');
        $this->assertResponseContains('</html>');
        $this->assertNoMailSent();
    }

    /**
     * Test that the contact page sends emails
     *
     * @return void
     * @throws \PHPUnit\Exception
     */
    public function testContactPagePostSuccess()
    {
        $data = [
            'category' => 'General',
            'name' => 'Sender name',
            'email' => 'sender@example.com',
            'body' => 'Message body'
        ];
        $this->post([
            'controller' => 'Pages',
            'action' => 'contact'
        ], $data);
        $this->assertResponseContains('Thank you for contacting us.');
        $this->assertResponseOk();
        $this->assertMailSentFrom($data['email']);
        $this->assertMailSentTo(Configure::read('adminEmail'));
        $this->assertMailContains($data['body']);
    }
}
