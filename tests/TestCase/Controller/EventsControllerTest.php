<?php
namespace App\Test\TestCase\Controller;

use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;

/**
 * App\Controller\EventsController Test Case
 */
class EventsControllerTest extends TestCase
{
    use IntegrationTestTrait;

    /**
     * Fixtures
     *
     * @var array
     */
    public $fixtures = [
        'app.Events',
        'app.Users',
        'app.Categories',
        'app.EventSeries',
        'app.Images',
        'app.Tags',
        'app.EventsImages',
        'app.EventsTags'
    ];

    /**
     * testMultipleGet method
     *
     * @return void
     * @throws \PHPUnit\Exception
     */
    public function testMultipleGet()
    {
        $this->get('/');
        $this->assertResponseOk();
        $this->get('/');
        $this->assertResponseOk();
    }

    /**
     * Tests HTTP requests being redirected to HTTPS
     *
     * @return void
     * @throws \PHPUnit\Exception
     */
    public function testRedirectToHttps()
    {
        $this->configRequest([
            'environment' => ['HTTPS' => 'off']
        ]);
        $this->get('/');
        $this->assertRedirect();

        // Test redirection SPECIFICALLY to HTTPS
        $this->markTestIncomplete('Not implemented yet.');
    }
}
