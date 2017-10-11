<?php

namespace xepan\custom;

class Initiator extends \Controller_Addon {
	
	public $addon_name = 'xepan_custom';

	function setup_admin(){

		$this->routePages('xepan_custom');
		$this->addLocation(array('template'=>'templates','js'=>'templates/js'))
		->setBaseURL('../vendor/xepan/custom/');

		$m = $this->app->top_menu->addMenu('Custom Application');
		$m->addItem(['Category Image','icon'=>'fa fa-picture-o'],'xepan_custom_categoryimage');
		$m->addItem(['Popup Banner','icon'=>'fa fa-file-image-o'],'xepan_custom_popup');
		$m->addItem(['Carrer','icon'=>'fa fa-briefcase'],'xepan_custom_carrer');
		$m->addItem(['Feed','icon'=>'fa fa-rss'],'xepan_custom_feeds');
		$m->addItem(['Item Enquiry','icon'=>'fa fa-envelope-o'],'xepan_custom_itemenquiry');
		$m->addItem(['URL Redirection','icon'=>'fa fa-link'],'xepan_custom_redirection');

		$this->app->addHook('entity_collection',[$this,'exportEntities']);
		return $this;

	}

	function setup_frontend(){
		$url = 'www.saraswatiglobal.com'.$_SERVER['REQUEST_URI'];			
		$redirection = $this->add('xepan\custom\Model_Redirection');
		$redirection->tryLoadBy('request',$url);
		
		if($redirection->loaded())
			$this->app->redirect($this->app->url($redirection['target']));

		$this->routePages('xepan_custom');
		$this->addLocation(array('template'=>'templates','js'=>'templates/js','css'=>'templates/css'))
		->setBaseURL('./vendor/xepan/custom/');

		$this->app->exportFrontEndTool('xepan\custom\Tool_PopupCard','Custom');
		$this->app->exportFrontEndTool('xepan\custom\Tool_CategoryImage','Custom');
		$this->app->exportFrontEndTool('xepan\custom\Tool_ItemEnquiry','Custom');
   	 	$this->app->exportFrontEndTool('xepan\custom\Tool_LatestFeed','Custom');
    	$this->app->exportFrontEndTool('xepan\custom\Tool_CurrentOpening','Custom');
		$this->app->exportFrontEndTool('xepan\custom\Tool_CategoryHeading','Custom');
		$this->app->exportFrontEndTool('xepan\custom\Tool_RecentlyViewedItems','Commerce');
		$this->app->exportFrontEndTool('xepan\custom\Tool_WishlistDetail','Commerce');
		$this->app->exportFrontEndTool('xepan\custom\Tool_Wishlist','Commerce');
		$this->app->exportFrontEndTool('xepan\custom\Tool_IdToName','Commerce');
		$this->app->exportFrontEndTool('xepan\custom\Tool_LinkRefer','Commerce');	

		return $this;
	}

	function exportEntities($app,&$array){
    }
}