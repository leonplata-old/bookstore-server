<?php
namespace App\Controller;

use Cake\Network\Exception\BadRequestException;
use League\JsonGuard\Validator as JsonValidator;
use App\Controller\AppController;
use Cake\ORM\TableRegistry;

/**
 * Books Controller
 *
 * @property \App\Model\Table\BooksTable $Books
 *
 * @method \App\Model\Entity\Book[] paginate($object = null, array $settings = [])
 */
class BooksController extends AppController
{
    const ADD_BOOK_BODY_SCHEMA = '
    {
        "type": "object",
        "properties": {
            "title": {
                "type": "string",
                "minLength": 1
            },
            "edition_date": {
                "type": "string",
                "format": "date-time"
            }
        },
        "required": ["title"],
        "additionalProperties": false
    }';

    const ASSOCIATE_AUTHOR_BODY_SCHEMA = '
    {
        "type": "object",
        "properties": {
            "add": {
                "type": "array",
                "items": {
                    "type": "number"
                }
            },
            "remove": {
                "type": "array",
                "items": {
                    "type": "number"
                }
            }
        },
        "additionalProperties": false
    }';

    /**
     * Index method
     *
     * @return \Cake\Http\Response|void
     */
    public function index()
    {
        $books = $this->paginate($this->Books);

        $this->set(compact('books'));
        $this->set('_serialize', ['books']);
    }

    /**
     * View method
     *
     * @param string|null $id Book id.
     * @return \Cake\Http\Response|void
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function view($id = null)
    {
        $book = $this->Books->get($id, [
            'contain' => ['Authors']
        ]);

        $this->set('book', $book);
        $this->set('_serialize', ['book']);
    }

    /**
     * Add method
     *
     * @return \Cake\Http\Response|null Redirects on successful add, renders view otherwise.
     */
    public function add()
    {
        $schema = json_decode(BooksController::ADD_BOOK_BODY_SCHEMA);
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

         $book = $this->Books->newEntity((array) $data);
         if (!$this->Books->save($book)) {
             $this->Flash->error(__('The book could not be saved. Please, try again.'));
         }
         $this->Flash->success(__('The book has been saved.'));
         $author = $this->Books->Authors->find('list', ['limit' => 200]);
         $this->set(compact('book', 'author'));
         $this->set('_serialize', ['book']);
    }

    /**
     * Edit method
     *
     * @param string|null $id Book id.
     * @return \Cake\Http\Response|null Redirects on successful edit, renders view otherwise.
     * @throws \Cake\Network\Exception\NotFoundException When record not found.
     */
    public function edit($id = null)
    {
        $schema = json_decode(BooksController::ADD_BOOK_BODY_SCHEMA);
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


        $book = $this->Books->get($id, [
            'contain' => ['Authors']
        ]);
        if ($this->request->is(['patch', 'post', 'put'])) {
            $book = $this->Books->patchEntity($book, $this->request->getData());
            if ($this->Books->save($book)) {
                $this->Flash->success(__('The book has been saved.'));

                return $this->redirect(['action' => 'index']);
            }
            $this->Flash->error(__('The book could not be saved. Please, try again.'));
        }
        $authors = $this->Books->Authors->find('list', ['limit' => 200]);
        $this->set(compact('book', 'authors'));
        $this->set('_serialize', ['book']);
    }

    /**
     * Delete method
     *
     * @param string|null $id Book id.
     * @return \Cake\Http\Response|null Redirects to index.
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function delete($id = null)
    {
        $this->request->allowMethod(['post', 'delete']);
        $book = $this->Books->get($id);
        if ($this->Books->delete($book)) {
            $this->Flash->success(__('The book has been deleted.'));
        } else {
            $this->Flash->error(__('The book could not be deleted. Please, try again.'));
        }

        return $this->redirect(['action' => 'index']);
    }

    /**
     * Associate method
     *
     */
    public function associate($id = null)
    {
        $schema = json_decode(BooksController::ASSOCIATE_AUTHOR_BODY_SCHEMA);
        $data = $this->request->input('json_decode');

        $validator = new JsonValidator($data, $schema);

        if ($validator->fails()) {
            $errors = $validator->errors();
            $firstError = array_values($errors)[0];

            throw new BadRequestException($firstError->getMessage());
        }

        $book = $this->Books->get($id);

        $booksTable = TableRegistry::get('Books');

        $added = [];
        $removed = [];

        if (property_exists($data, 'add') && !empty($data->add)) {
            $authors = $booksTable
                ->Authors
                ->find()
                ->where(['id_author IN' => $data->add])
                ->toArray();

            $booksTable->Authors->link($book, $authors);
            $added = $authors;
        }

        if (property_exists($data, 'remove') && !empty($data->remove)) {
            $authors = $booksTable
                ->Authors
                ->find()
                ->where(['id_author IN' => $data->remove])
                ->toArray();

            $booksTable->Authors->unlink($book, $authors);
            $removed = $authors;
        }

        $this->set(compact('added', 'removed'));
        $this->set('_serialize', ['added', 'removed']);
    }
}
