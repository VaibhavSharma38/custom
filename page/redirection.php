<?php

namespace xepan\custom;

class page_redirection extends \xepan\base\Page{
	public $title = "Redirection";

	function init(){
		parent::init();

		$this->add('CRUD')->setModel('xepan\custom\Model_Redirection');
	}
}