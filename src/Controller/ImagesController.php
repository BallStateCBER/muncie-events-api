<?php

namespace App\Controller;

use App\Model\Entity\Image;
use App\Model\Table\ImagesTable;
use App\Model\Table\UsersTable;
use Cake\Datasource\ResultSetInterface;
use Cake\Http\Exception\BadRequestException;
use Cake\Http\Exception\InternalErrorException;

/**
 * Images Controller
 *
 * @property ImagesTable $Images
 * @property UsersTable $Users
 *
 * @method Image[]|ResultSetInterface paginate($object = null, array $settings = [])
 */
class ImagesController extends AppController
{
    /**
     * Displays a collection of this user's previously-uploaded images
     *
     * @param int $userId User ID
     * @return void
     */
    public function userImages($userId)
    {
        $this->viewbuilder()->setLayout('ajax');
        $this->loadModel('Users');
        $this->set([
            'images' => $this->Users->getImagesList($userId)
        ]);
    }

    /**
     * Effectively bypasses Uploadify's check for an existing file
     *
     * This check is unnecessary, as the image's filename is changed as it's being saved
     *
     * @return void
     */
    public function fileExists()
    {
        exit(0);
    }

    /**
     * Uploads an image
     *
     * @return void
     */
    public function upload()
    {
        $this->request->allowMethod('post');

        $file = $this->request->getData('Filedata');

        if (!$file) {
            throw new BadRequestException('No image received. Did you forget to select a file to upload?');
        }

        try {
            $image = $this->Images->processUpload($this->Auth->user('id'), $file);
            $retval = $image->id;
        } catch (BadRequestException $e) {
            $retval = $e->getMessage();
            $this->response = $this->response->withStatus(400);
        } catch (InternalErrorException $e) {
            $retval = $e->getMessage();
            $this->response = $this->response->withStatus(500);
        }

        $this->viewbuilder()->setLayout('ajax');
        $this->set(compact('retval'));
    }
}
