<?php

namespace xepan\custom;

class Tool_Breadcrumb extends \xepan\cms\View_Tool{
	public $options = [];

	function init(){
		parent::init();

		
	}

	function defaultTemplate(){
		return ['view\tool\idtoname'];
	}
}