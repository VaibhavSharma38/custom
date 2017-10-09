<?php

namespace xepan\custom;

class page_itemenquiry extends\xepan\base\Page{
	public $title = "Item Enquiry";
	
	function init(){
		parent::init();

		$enquiry_model = $this->add('xepan\custom\Model_ItemEnquiry');
		
		$crud = $this->add('xepan\hr\CRUD',['allow_add'=>false]);
		$crud->setModel($enquiry_model);
	}
}