<?php
/**
 * CakePHP(tm) : Rapid Development Framework (https://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 * @link      https://cakephp.org CakePHP(tm) Project
 * @since     0.2.9
 * @license   https://opensource.org/licenses/mit-license.php MIT License
 */
namespace App\Controller;

use Cake\Core\Configure;
use Cake\Network\Exception\ForbiddenException;
use Cake\Network\Exception\NotFoundException;
use Cake\View\Exception\MissingTemplateException;

/**
 * Static content controller
 *
 * This controller will render views from Template/Pages/
 *
 * @link https://book.cakephp.org/3.0/en/controllers/pages-controller.html
 */
class PagesController extends AppController
{
    /**
     * Initialize method
     *
     * @return \Cake\Http\Response|null
     */
    public function initialize()
    {
        parent::initialize();

        if (! $this->request->is('ssl')) {
            return $this->redirect('https://' . env('SERVER_NAME') . $this->request->getRequestTarget());
        }
    }

    /**
     * Home page
     *
     * @return void
     */
    public function home()
    {
    }

    /**
     * Docs page
     *
     * @return void
     */
    public function docsV1()
    {
    }
}
