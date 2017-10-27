<?php

namespace xepan\custom;

class Model_CustomOrderInfo extends \xepan\base\Model_Table{
	public $table = "custom_order_info";

	function init(){
		parent::init();

		$this->hasOne('xepan\custom\Model_CustomOrder','custom_order_id');
		$this->addField('collection');
		$this->addField('design');
		$this->addField('color');
		$this->addField('size');
		$this->addField('qty');
		$this->addField('price');
		$this->addField('narration')->type('text');

		$this->addHook('beforeSave',[$this,'DoSomething']);
	}


	function DoSomething($m){
		
	}
}