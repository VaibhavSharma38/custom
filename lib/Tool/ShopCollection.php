<?php
namespace xepan\custom;

class Tool_ShopCollection extends \xepan\cms\View_Tool{
	public $options = [
		'url_page' =>'index',
		"custom_template"=>'',
		'show_name'=>true,
		'show_price'=>false,
		'show_image'=>false,
		'show_item_count'=>false,
		'include_sub_category'=>true
	];

	function init(){
		parent::init();
		// return;
		if($this->options['custom_template']){
			$path = getcwd()."/websites/".$this->app->current_website_name."/www/view/tool/".$this->options['custom_template'].".html";
			if(!file_exists($path)){
				$this->add('View_Warning')->set('template not found');
				return;	
			}	
		}else
			$this->options['custom_template'] = "categorylister";

		$lister = $this->add('xepan\custom\View_ShopCollectionLister',['options'=>$this->options]);
	}
}