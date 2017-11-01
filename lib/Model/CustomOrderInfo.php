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

		$this->addHook('beforeSave',[$this,'fieldValidations']);
	}

	function fieldValidations($m){
		if($this['collection'] == '')
			throw $this->exception('Required(*)','ValidityCheck')->setField('collection');

		if($this['design'] == '')
			throw $this->exception('Required(*)','ValidityCheck')->setField('design');

		if($this['color'] == '')
			throw $this->exception('Required(*)','ValidityCheck')->setField('color');

		if($this['size'] == '')
			throw $this->exception('Required(*)','ValidityCheck')->setField('size');

		if($this['qty'] == '')
			throw $this->exception('Required(*)','ValidityCheck')->setField('qty');

		if($this['price'] == '')
			throw $this->exception('Required(*)','ValidityCheck')->setField('price');		
	}
}