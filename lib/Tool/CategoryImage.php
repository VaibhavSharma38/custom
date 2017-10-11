<?php

namespace xepan\custom;

class Tool_CategoryImage extends \xepan\cms\View_Tool{
	public $options = [
					'show_image_of'=>'parent_category'
				];

	function init(){
		parent::init();

		$cat_m = $this->add('xepan\commerce\Model_Category');
		$cat_m->tryLoadBy('slug_url',$_GET['parent_category_code']);	
		$parent_category_id = $cat_m->id;
		$cat_m->unload();	
		$category_code = explode('/', $_GET['category_code']);
		$cat_m->tryLoadBy('slug_url',$category_code[1]);	
		$category_id = $cat_m->id;

		$categoryimage_m = $this->add('xepan\custom\Model_CategoryImage');
		
		if($this->options['show_image_of'] === "parent_category"){
			$categoryimage_m->tryLoadBy('category_id',$parent_category_id);
		}else{
			$categoryimage_m->tryLoadBy('category_id',$category_id);
		}

		if($categoryimage_m->loaded())
			$this->template->trySet('url',$categoryimage_m['image']);
		else
			$this->template->tryDel('superWrapper');
	}

	function defaultTemplate(){
		return ['view\tool\categoryimage'];
	}
}