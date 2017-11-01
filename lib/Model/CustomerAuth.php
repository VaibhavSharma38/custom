<?php

namespace xepan\custom;

class Model_CustomerAuth extends \xepan\base\Model_Table{
	public $table = "customer_auth";
	public $acl = false;
	
	function init(){
		parent::init();

		$this->hasOne('xepan\commerce\Model_Customer','customer_id');
		$this->addField('custom_order')->type('boolean');
	}
}