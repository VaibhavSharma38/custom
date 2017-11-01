<?php

namespace xepan\custom;

class page_configuration extends \xepan\base\Page{
	public $title = "Configuration";

	function init(){
		parent::init();

		$customer_auth_m = $this->add('xepan\custom\Model_CustomerAuth');

		$crud = $this->add('xepan\hr\CRUD');
		$crud->setModel($customer_auth_m);
	}
}