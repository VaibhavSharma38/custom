<?php

namespace xepan\custom;

class Model_CurrentOpening extends \xepan\base\Model_Table{
	public $table = "current_opening";
	public $acl = true;

	public $status=[
		'Active',
		'InActive'
	];

	public $actions=[
		'Active'=>['view','edit','delete','deactivate'],
		'InActive'=>['view','edit','delete','activate']
	];

	function init(){
		parent::init();

		$this->hasOne('xepan\hr\Employee','created_by_id')->defaultValue($this->app->employee->id);

		$this->addField('created_at')->type('datetime')->defaultValue($this->app->now);
		$this->addField('status')->enum($this->status)->defaultValue('Active');
		$this->addField('type')->defaultValue('CurrentOpening');		
		$this->addField('post_name');		
		$this->addField('experience_required');		
		$this->addField('location');		
		$this->addField('description')->type('text')->display(['form'=>'xepan\base\RichText']);				
	}

	function deactivate(){
		$this['status']='InActive';
		$this->app->employee
            ->addActivity("Job Opening : '".$this['post_name']."' has been deactivated", null/* Related Document ID*/, $this->id /*Related Contact ID*/,null,null,null)
            ->notifyWhoCan('activate','InActive',$this);
		$this->save();
	}

	function activate(){
		$this['status']='Active';
		$this->app->employee
            ->addActivity("Job Opening : '".$this['post_name']."' is now active", null/* Related Document ID*/, $this->id /*Related Contact ID*/,null,null,null)
            ->notifyWhoCan('deactivate','Active',$this);
		$this->save();
	}
}