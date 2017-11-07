<?php

namespace xepan\custom;

class page_test extends \xepan\base\Page{
	function init(){
		parent::init();

		// $item_m = $this->add('xepan\custom\Model_Item');
		// $item_m->join('item_image.item_id','id');
		// $item_m->_dsql()->group('sku');
		// $item_m->addExpression('image_count','count(*)');

		// $grid = $this->add('Grid');
		// $grid->setModel($item_m,['sku','image_count']);

		// $item_m = $this->add('xepan\custom\Model_Item');
		
		// foreach ($item_m as $item) {
		// 	$text = strtolower(htmlentities($item['sku'])); 
		//     $text = str_replace(get_html_translation_table(), "-", $text);
		//     $text = str_replace(" ", "-", $text);
		//     $text = preg_replace("/[-]+/i", "-", $text);	
		// 	$item['slug_url'] = $text;
		// 	$item->save();
		// }
		// comment
	}
}