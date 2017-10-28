<?php

namespace xepan\custom;

class Model_CustomOrder extends \xepan\base\Model_Table{
	public $table = "custom_order";

	function init(){
		parent::init();

		$this->hasOne('xepan\commerce\Model_Customer','created_by_id');
		$this->addField('created_at')->type('date')->defaultValue($this->app->now);
		$this->addField('customer_name');
		$this->addField('account_no');
		$this->addField('order_no');
		$this->addField('deliver_date')->type('date');
		$this->addField('ship_to')->type('text');
		$this->addField('ship_method');
		$this->addField('residentail')->type('boolean');
		$this->addField('lift_gate')->type('boolean');
		$this->addField('signature_required')->type('boolean');
		$this->addField('ship_complete')->type('boolean');
		$this->addField('white_glove')->type('boolean');
		$this->addField('instructions')->type('text');

		$this->addHook('beforeSave',[$this,'saveCustomer']);
		$this->addHook('beforeDelete',[$this,'deleteCustomOrderInfo']);
	}

	function saveCustomer($m){

	}


	function deleteCustomOrderInfo($m){
		// $custom_order_info_m = $this->add('xepan\commerce\Model_CustomOrderInfo');
		// $custom_order_info_m->addCondition('custom_order_id',$m->id);

		// foreach ($custom_order_info_m as $co) {
		// 	$co->delete();	
		// }
	}
}