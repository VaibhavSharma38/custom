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

		$this->addHook('beforeSave',[$this,'saveCustomer']);
		$this->addHook('beforeDelete',$this);
	}

	function saveCustomer($m){

	}


	function beforeDelete($m){
		$custom_order_info_m = $this->add('xepan\custom\Model_CustomOrderInfo');
		$custom_order_info_m->addCondition('custom_order_id',$m->id);

		foreach ($custom_order_info_m as $co) {
			$co->delete();	
		}
	}
}