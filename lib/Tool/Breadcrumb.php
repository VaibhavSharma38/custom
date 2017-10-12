<?php

namespace xepan\custom;

class Tool_Breadcrumb extends \xepan\cms\View_Tool{
	public $count = 0;
	public $options = [
						'intermediate_value'=>'',
						'intermediate_link'=>''
					  ];
					  
	function init(){
		parent::init();

		$breadcrumb_array = [];
		$url_array = [];
		
		$breadcrumb_array [] = 'Home';
		$url_array [] = 'index.php';

		if($this->options['intermediate_value']){
			$breadcrumb_array [] = $this->options['intermediate_value'];
			$url_array [] = $this->options['intermediate_link'];
		}
		
		if($_GET['parent_category_code']){			
			$breadcrumb_array [] = $_GET['parent_category_code'];
			
			if($this->options['intermediate_link'])
				$url_array [] = $this->options['intermediate_link'].'/'.$_GET['parent_category_code'];
			else
				$url_array [] = $_GET['parent_category_code'];
		}
		
		if($_GET[' category_code']){			
			$breadcrumb_array [] = $_GET[' category_code'];
			$url_array [] = end($url_array).'/'.$_GET[' category_code'];
		}

		if(!$_GET[' category_code'] AND $_GET['category_code']){			
			$breadcrumb_array [] = $_GET['category_code'];
			$url_array [] = end($url_array).'/'.$_GET['category_code'];
		}
			
		if($_GET['item_code']){
			$breadcrumb_array [] = $_GET['item_code'];
			$url_array [] = end($url_array).'/'.$_GET['item_code'];
		}

		$lister = $this->add('Lister',null,null,['view\tool\breadcrumb']);
		$lister->setSource($breadcrumb_array);
		
		$lister->addHook('formatRow',function($l)use($url_array){
			$l->current_row_html['name'] = $l->model['name'];
			$l->current_row_html['url'] = 'http://'.$_SERVER['SERVER_NAME'].'/'.$url_array[$this->count];			
			$this->count+=1;
		});
	}
}