<?php
namespace xepan\custom;

class Tool_CategoryHeading extends \xepan\cms\View_Tool{
	public $options = [
	];

	function init(){
		parent::init();
		
		if(!$_GET['xsnb_category_id'] AND !$_GET['category_code'])
			return;
		
		$view = $this->add('xepan\custom\View_CategoryHeading',['options'=>$this->options]);
	}
}