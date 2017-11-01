<?php

namespace xepan\custom;

class Model_CustomOrder extends \xepan\base\Model_Table{
	public $table = "custom_order";

	function init(){
		parent::init();

		$this->hasOne('xepan\commerce\Model_Customer','created_by_id');
		$this->addField('created_at')->type('date')->defaultValue($this->app->now);
		$this->addField('customer_name');
		$this->addField('order_no');
		$this->addField('deliver_date')->type('date');
		$this->addField('ship_to')->type('text');
		$this->addField('ship_method');
		$this->addField('instructions')->type('text');

		$this->addHook('beforeSave',[$this,'fieldValidations']);
		$this->addHook('beforeSave',[$this,'uniqueOrderNo']);
		$this->addHook('beforeDelete',$this);

	}

	function uniqueOrderNo($m){
		$com = $this->add('xepan\custom\Model_CustomOrder');
		$com->tryLoadBy('order_no',$m['order_no']);

		if($com->loaded())
			throw $this->exception('Order number already exist','ValidityCheck')->setField('order_no');
			
	}

	function fieldValidations($m){
		if($this['customer_name'] == '')
			throw $this->exception('Required(*)','ValidityCheck')->setField('customer_name');

		if($this['order_no'] == '' || !is_numeric($this['order_no']))
			throw $this->exception('Required(*), number','ValidityCheck')->setField('order_no');

		if($this['deliver_date'] == '')
			throw $this->exception('Required(*)','ValidityCheck')->setField('deliver_date');

		if($this['ship_to'] == '')
			throw $this->exception('Required(*)','ValidityCheck')->setField('ship_to');

		if($this['ship_method'] == '')
			throw $this->exception('Required(*)','ValidityCheck')->setField('ship_method');

		if($this['instructions'] == '')
			throw $this->exception('Required(*)','ValidityCheck')->setField('instructions');
	}


	function beforeDelete($m){
		$custom_order_info_m = $this->add('xepan\custom\Model_CustomOrderInfo');
		$custom_order_info_m->addCondition('custom_order_id',$m->id);

		foreach ($custom_order_info_m as $co) {
			$co->delete();	
		}
	}
}