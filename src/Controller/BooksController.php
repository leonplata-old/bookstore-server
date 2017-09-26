<?php
namespace App\Controller;

use Cake\ORM\TableRegistry;
use App\Controller\AppController;

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
        $sort = $this->request->query('sort');
        $filter = $this->request->query('filter');
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
        $data = $this->getJsonInput(BooksController::ADD_BOOK_BODY_SCHEMA);

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
        $data = $this->getJsonInput(BooksController::ADD_BOOK_BODY_SCHEMA);

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
     * @param string|null $id Book id.
     */
    public function associate($id = null)
    {
        $data = $this->getJsonInput(BooksController::ASSOCIATE_AUTHOR_BODY_SCHEMA);

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
