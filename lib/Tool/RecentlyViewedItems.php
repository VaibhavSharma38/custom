<?php

namespace xepan\custom;

class Tool_RecentlyViewedItems extends \xepan\cms\View_Tool{
	public $options = [
							'custom_template'=>'',
							'page_name' =>'',
							'hide_product' =>''
						];	

	function init(){
		parent::init();

		$this->app->stickyForget('item_code');

		$item_id = $_GET['item_code'];		
		$item_cookie = $_COOKIE['items'];		
		$item_cookie = stripslashes($item_cookie);
		$item_id_array = json_decode($item_cookie, true);
		
		
		$this->options['custom_template'] = "recentlyviewed";

		$this->displayProducts($item_id_array);

		if(is_array($item_id_array) AND (!in_array($item_id, $item_id_array, true))){			
			array_push($item_id_array, $item_id);
			$item_id_json = json_encode($item_id_array);
			setcookie('items', $item_id_json);
    	}
    	
    	if(!is_array($item_id_array)){
    		$item_id_array = [];
    		array_push($item_id_array, $item_id);
			$item_id_json = json_encode($item_id_array);
			setcookie('items', $item_id_json);
    	}	   	
	}

	function displayProducts($arr){	
		if(!is_array($arr) || empty($arr))
			return;
		
		$item_m = $this->add('xepan\custom\Model_Item');
		$item_m->addCondition('slug_url',$arr);

		if($this->options['hide_product'] == 'true'){
			$item_m->addCondition('hide_in_product',1);
		}

		if(!$item_m->count()->getOne())
			return;

		$grid = $this->add('xepan\base\Grid',null,null,['view/tool/'.$this->options['custom_template']]);
		$grid->setModel($item_m);
		
		$grid->addHook('formatRow',function($g){
			$detail_page_url = $this->api->url($this->options['page_name'].'/'.$_GET['parent_category_code'].'/'.$_GET[' category_code'].'/'.$g->model['slug_url']);						
			$g->current_row_html['page_link'] = $detail_page_url;
		});

		$paginator = $grid->add('Paginator',['ipp'=>4]);
		$paginator->setRowsPerPage(4);
	}
}