<?php

namespace xepan\custom;

class page_popup extends \xepan\base\Page{
	public $title = "Popup Banner";

	function init(){
		parent::init();

		$card_m = $this->add('xepan\custom\Model_Popup');
		$crud = $this->add('xepan\hr\CRUD',null,null,['page\card']);
		$crud->setModel($card_m,['name','link','image_id'],['name','link','status','image']);
		$crud->grid->removeColumn('attachment_icon');
	}
}