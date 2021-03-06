<?php
namespace xepan\custom;

class Tool_SubCategory extends \xepan\cms\View_Tool{
	public $options = [
		'url_page' =>'index',
		"custom_template"=>'',
		'show_name'=>true,
		'show_price'=>false,
		'show_image'=>false,
		'show_item_count'=>false,
	];

	function init(){
		parent::init();
		
		if($this->options['custom_template']){
			$path = getcwd()."/websites/".$this->app->current_website_name."/www/view/tool/".$this->options['custom_template'].".html";
			if(!file_exists($path)){
				$this->add('View_Warning')->set('template not found');
				return;	
			}	
		}else
			$this->options['custom_template'] = "subcategorylister";

		$lister = $this->add('xepan\custom\View_SubCategoryLister',['options'=>$this->options]);
	}
}