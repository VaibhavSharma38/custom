<?php

namespace xepan\custom;

class Tool_CustomOrder extends \xepan\cms\View_Tool{
	public $customer_id = '';

	function init(){
		parent::init();
        
    $customer = $this->add('xepan\commerce\Model_Customer');
    $customer->loadLoggedIn("Customer");

    if(!$customer->loaded()){
        $this->add('View_Info')->set('Please Login To Add Orders')->addClass('jumbotron well text-center row alert alert-info h3');
        return;            
    }

    $this->customer_id = $customer->id;

		// ORDER
    $order_m = $this->add('xepan\custom\Model_CustomOrder');
    $order_m->addCondition('created_by_id',$customer->id);  
    $order_c = $this->add('xepan\base\CRUD',null,null,['view\tool\customorder']);
    if($order_c->isEditing())
      $order_c->form->setLayout('view\tool\form\customorder');
    
    $order_c->setModel($order_m,['customer_name','account_no','order_no','deliver_date','ship_to','ship_method','residentail','lift_gate','signature_required','ship_complete','white_glove','instructions'],['created_at','customer_name','account_no','order_no','deliver_date','ship_to','ship_method','residentail','lift_gate','signature_required','ship_complete','white_glove','instructions']);

    $order_c->grid->add('VirtualPage')->addColumn('OrderItems')
            ->set(function($page){
              $id = $_GET[$page->short_name.'_id'];
              $order_info_m = $page->add('xepan\custom\Model_CustomOrderInfo');
              $order_info_m->addCondition('custom_order_id',$id);

              $order_info_c = $page->add('xepan\base\CRUD',null,null,['view\tool\customorderinfo']);
          
              if($order_info_c->isEditing()){
                $order_info_c->form->setLayout('view\tool\form\customorderinfo');
              }

              $order_info_c->setModel($order_info_m,['collection','design','color','size','qty','price','narration'],['collection','design','color','size','qty','price','narration']);
            });
  }
}