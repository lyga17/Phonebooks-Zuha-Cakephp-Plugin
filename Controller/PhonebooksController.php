<?php
App::uses('PhonebooksAppController', 'Phonebooks.Controller');
/**
 * Directories Controller
 *
 * @property Phonebook $Phonebook
 */
class _PhonebooksController extends PhonebooksAppController {

/**
 * Helpers
 *
 * @var array
 */
	//public $helpers = array('Media');
	
	public $uses = 'Phonebooks.Phonebook';

/**
 * index method
 *
 * @return void
 */
	public function index() {
		$this->Phonebook->recursive = 0;
		$this->paginate['contain'][] = 'PhonebookService';
		if(CakePlugin::loaded('Categories')) {
			$this->set('categories', $this->Phonebook->Category->find('list', array('conditions' => array('model' => 'Phonebook'))));
			$this->paginate['contain'][] = 'Category';
			if(isset($this->request->query['categories'])) {
				$categories_param = explode(';', rawurldecode($this->request->query['categories']));
				$this->set('selected_categories', json_encode($categories_param));
				$joins = array(
		           array('table'=>'categorized', 
		                 'alias' => 'Categorized',
		                 'type'=>'left',
		                 'conditions'=> array(
		                 	'Categorized.foreign_key = Phonebook.id'
		           )),
		           array('table'=>'categories', 
		                 'alias' => 'Category',
		                 'type'=>'left',
		                 'conditions'=> array(
		                 	'Category.id = Categorized.category_id'
				   ))
		         );
				$this->paginate['joins'] = $joins;
				$this->paginate['conditions'] = array('Category.name' => $categories_param);
				$this->paginate['fields'] = array(
					'DISTINCT Phonebook.id', 
					'Phonebook.name', 
					'Phonebook.address_1', 
					'Phonebook.address_2',
					'Phonebook.city',
					'Phonebook.state',
					'Phonebook.zip', 
					'Phonebook.phone',
					'Phonebook.email',
					'Phonebook.website'
				);
			}
		}
		$this->set('phonebooks', $this->paginate());
	}

/**
 * view method
 *
 * @param string $id
 * @return void
 */
	public function view($id = null) {
		
		if (!$this->Phonebook->exists($id)) {
			throw new NotFoundException(__('Invalid Phonebook'));
		}
		
		$this->request->data = $this->Phonebook->find('first', array(
			'conditions' => array('Phonebook.id' => $id),
			'contain' => array('Category', 'PhonebookService', 'Answer'),
			));
		
	}

/**
 * add method
 *
 * @return void
 */
	public function add() {
		$this->view = 'add_edit';
		
		if ($this->request->is('post')) {
			
			$this->Phonebook->create();
			if ($this->Phonebook->saveAll($this->request->data)) {
				$this->Session->setFlash(__('The Phonebook has been saved'));
				$this->redirect(array('action' => 'index'));
			} else {
				$this->Session->setFlash(__('The Phonebook could not be saved. Please, try again.'));
			}
			$categories = $this->Phonebook->Category->find('list');
			$this->set('categories',$categories);
		}
	}

/**
 * edit method
 *
 * @param string $id
 * @return void
 */
	public function edit($id = null) {
		$this->view = 'add_edit';
		$this->Phonebook->id = $id;
		if (!$this->Phonebook->exists()) {
			throw new NotFoundException(__('Invalid Phonebook'));
		}
		if ($this->request->is('post') || $this->request->is('put')) {
			if ($this->Phonebook->saveAll($this->request->data)) {
				$this->Session->setFlash(__('The Phonebook has been saved'));
				$this->redirect(array('action' => 'index'));
			} else {
				$this->Session->setFlash(__('The Phonebook could not be saved. Please, try again.'));
			}
		} else {
			$this->request->data = $this->Phonebook->read(null, $id);
		}
		$categories = $this->Phonebook->Category->find('list');
		$this->set('categories',$categories);
	}

/**
 * delete method
 *
 * @param string $id
 * @return void
 */
	public function delete($id = null) {
		if (!$this->request->is('post')) {
			throw new MethodNotAllowedException();
		}
		$this->Phonebook->id = $id;
		if (!$this->Phonebook->exists()) {
			throw new NotFoundException(__('Invalid Phonebook'));
		}
		if ($this->Phonebook->delete()) {
			$this->Session->setFlash(__('Phonebook deleted'));
			$this->redirect(array('action' => 'index'));
		}
		$this->Session->setFlash(__('Phonebook was not deleted'));
		$this->redirect(array('action' => 'index'));
	}

/**
 * search method
 *
 * @param string $id
 * @return void
 */
	public function search() {
		$query = '';
		if(!empty($this->request->query)) {
			$query = $this->request->query['search'];
		}else {
			throw new MethodNotAllowedException('No Search Provided');
		}
		
		App::uses('HttpSocket', 'Network/Http');
		
		$apikey = 'x1JbnWhgUK0snZDiWcxFkFrXkfugdrJKssWQpd7jX7Ml7pE3CbJWRbUbqorDPaoi';
		$url = 'http://zipcodedistanceapi.redline13.com/rest';
		
		if(!empty($query)) {
			$zip = $query;
		}
		

		$HttpSocket = new HttpSocket();
		
		// Get zipcodes by location
		//http://zipcodedistanceapi.redline13.com/rest/<api_key>/radius.<format>/<zip_code>/<distance>/<units>
		
		// Get zipcodes by radius of zip
		//http://zipcodedistanceapi.redline13.com/rest/<api_key>/city-zips.<format>/<city>/<state>
		$json = $HttpSocket->get($url.'/'.$apikey.'/radius.json/'.$zip.'/50/mile');
		
		$json = json_decode($json->body);
		
		$zips = array();
		foreach($json->zip_codes as $zipObj) {
			$zips[] = $zipObj->zip_code;
		}
		
		$this->set('locations', $this->Phonebook->find('all', array('conditions' => array('zip' => $zips))));
	}
	
}

if (!isset($refuseInit)) {
	class PhonebooksController extends _PhonebooksController {}
}
