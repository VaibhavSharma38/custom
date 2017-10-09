<?php

namespace xepan\custom;

class page_carrer extends \xepan\base\Page{
	public $title = "Current Opening";

	function init(){
		parent::init();

		$current_opening_m = $this->add('xepan\custom\Model_CurrentOpening');

		$crud = $this->add('xepan\hr\CRUD',null,null,['page\currentopening']);
		$crud->setModel($current_opening_m);
	}
}