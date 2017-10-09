<?php

namespace xepan\custom;

class Tool_LatestFeed extends \xepan\cms\View_Tool{

	function init(){
		parent::init();
		
		$feeds = $this->add('xepan\custom\Model_Feeds');
		$feeds->tryLoadAny();

		$this->template->trySet('src',$feeds['image']);	
		$this->template->trySet('title',$feeds['title']);	
		$this->template->trySet('description',$feeds['description']);	
		$this->template->trySet('url',$feeds['url']);	

	}

	function defaultTemplate(){
		return ['view\tool\feed'];
	}
}