<?php

namespace xepan\custom;

class page_test extends \xepan\base\Page{
	function init(){
		parent::init();

		$item_m = $this->add('xepan\commerce\Model_Item');
		$item_m->join('item_image.item_id','id');
		$item_m->_dsql()->group('sku');
		$item_m->addExpression('image_count','count(*)');

		$grid = $this->add('Grid');
		$grid->setModel($item_m,['sku','image_count']);
	}
}