<?php
namespace App\Controller;

use Cake\Network\Exception\BadRequestException;
use League\JsonGuard\Validator as JsonValidator;
use App\Controller\AppController;

/**
 * Authors Controller
 *
 * @property \App\Model\Table\AuthorsTable $Authors
 *
 * @method \App\Model\Entity\Author[] paginate($object = null, array $settings = [])
 */
class AuthorsController extends AppController
{
    const ADD_AUTHOR_BODY_SCHEMA = '
    {
        "type": "object",
        "properties": {
            "name": {
                "type": "string",
                "minLength": 1
            }
        },
        "required": ["name"]
    }';

    /**
     * Index method
     *
     * @return \Cake\Http\Response|void
     */
    public function index()
    {
        $authors = $this->paginate($this->Authors);

        $this->set(compact('authors'));
        $this->set('_serialize', ['authors']);
    }

    /**
     * View method
     *
     * @param string|null $id Author id.
     * @return \Cake\Http\Response|void
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function view($id = null)
    {
        $author = $this->Authors->get($id, [
            'contain' => ['Books']
        ]);

        $this->set('author', $author);
        $this->set('_serialize', ['author']);
    }

    /**
     * Add method
     *
     * @return \Cake\Http\Response|null Redirects on successful add, renders view otherwise.
     */
     public function add()
     {
        $schema = json_decode(AuthorsController::ADD_AUTHOR_BODY_SCHEMA);
        $data = $this->request->input('json_decode');

        $validator = new JsonValidator($data, $schema);

        if ($validator->fails()) {
            $errors = $validator->errors();
            $firstError = array_values($errors)[0];

            // 422 "Unprocessable Entity" is more suitable but seems CakePHP doesn't have a
            // built-in exception for this HTTP status code, using "Bad Request" instead
            // TODO: Send meaningful data about the validation through exception
            throw new BadRequestException($firstError->getMessage());
        }

         $author = $this->Authors->newEntity((array) $data);
         if (!$this->Authors->save($author)) {
             $this->Flash->error(__('The author could not be saved. Please, try again.'));
         }
         $this->Flash->success(__('The author has been saved.'));
         $book = $this->Authors->Books->find('list', ['limit' => 200]);
         $this->set(compact('author', 'book'));
         $this->set('_serialize', ['author']);
     }

    /**
     * Edit method
     *
     * @param string|null $id Author id.
     * @return \Cake\Http\Response|null Redirects on successful edit, renders view otherwise.
     * @throws \Cake\Network\Exception\NotFoundException When record not found.
     */
    public function edit($id = null)
    {
        $author = $this->Authors->get($id, [
            'contain' => ['Books']
        ]);
        if ($this->request->is(['patch', 'post', 'put'])) {
            $author = $this->Authors->patchEntity($author, $this->request->getData());
            if ($this->Authors->save($author)) {
                $this->Flash->success(__('The author has been saved.'));

                return $this->redirect(['action' => 'index']);
            }
            $this->Flash->error(__('The author could not be saved. Please, try again.'));
        }
        $books = $this->Authors->Books->find('list', ['limit' => 200]);
        $this->set(compact('author', 'books'));
        $this->set('_serialize', ['author']);
    }

    /**
     * Delete method
     *
     * @param string|null $id Author id.
     * @return \Cake\Http\Response|null Redirects to index.
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function delete($id = null)
    {
        $this->request->allowMethod(['post', 'delete']);
        $author = $this->Authors->get($id);
        if ($this->Authors->delete($author)) {
            $this->Flash->success(__('The author has been deleted.'));
        } else {
            $this->Flash->error(__('The author could not be deleted. Please, try again.'));
        }

        return $this->redirect(['action' => 'index']);
    }
}
