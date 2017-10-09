<?php

namespace xepan\custom;

class page_feeds extends \xepan\base\Page{
	public $title = "Feeds";

	function init(){
		parent::init();
	
		$feed_m = $this->add('xepan\custom\Model_Feeds');

		$crud = $this->add('xepan\base\CRUD');
		$crud->setModel($feed_m);
	}
}