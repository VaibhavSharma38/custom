<?php

namespace xepan\custom;

class Tool_Item_Detail extends \xepan\cms\View_Tool{
	public $options = [
				'layout'=>'primary',/*flat,collapse,tab*/
				'specification_layout'=>'specification',
				'show_item_upload'=>false,
				'show_addtocart'=>true,
				'show_multi_step_form'=>false,
				'multi_step_form_layout'=>"stacked",
				'custom_template'=>"",
				'personalized_page'=>"",
				'personalized_button_label'=>"Personalized",
				'addtocart_button_label'=>'Add To Cart',
				'show_price_or_amount'=>false,
				"show_original_price"=>true, // sale Price, sale/Original Price
				"show_shipping_charge"=>false,
				"shipping_charge_with_item_amount"=>false,
				"checkout_page"=>"",
				'continue_shopping_page'=>"index",
				'amount_group_in_multistepform'=>null
				];
	public $item;
	function init(){
		parent::init();

		$country_m = $this->app->country;

		if($_SERVER['SERVER_ADDR']){ 
	        $ip = str_replace('.',"", $_SERVER['SERVER_ADDR']);
	        $s = $this->app->db->dsql()
	                        ->table('ip2location-lite-db11')
	                        // ->where('ip_from','<=','16777216')
	                        // ->where('ip_to','>=','16777471')
	                        ->where('ip_from','<=',$ip)
	                        ->where('ip_to','>=',$ip)
	                        ->del('fields')->get();
	        // throw new \Exception(var_dump($s), 1);
	        // exit;
	        
	        $country_m->tryLoadBy('iso_code',$s['0']['country']);     		
    	}

  //   	if(!$country_m->loaded())
		// 	$this->options['show_addtocart'] = false;

		// if($country_m->loaded() AND $country_m['status'] =='InActive')
		// 	$this->options['show_addtocart'] = false;


		// added for price changing
		$this->addClass('xshop-item');

		$item_id = $this->api->stickyGET('commerce_item_id');
		if(!$item_id){
			$i = $this->add('xepan\custom\Model_Item');
			$i->tryLoadBy('slug_url',$_GET['item_code']);
			$item_id = $i->id;
			
		}

		$this->item = $this->add('xepan\custom\Model_Item')->tryLoad($item_id?:-1);
		if(!$this->item->loaded()){
			$this->add('View')->set('No Record Found');
			$this->template->tryDel("xepan_commerce_itemdetail_wrapper");
			return;
		}

		$this->setModel($this->item);
	}

	function setModel($model){
		//tryset html for description 
		$this->template->trySetHtml('item_description', $model['description']);
		$this->template->trySetHtml('name', $model['name']);

		//specification
		$spec_grid = $this->add('xepan\base\Grid',null,'specification',["view/tool/item/detail/".$this->options['specification_layout']]);
		$spec_grid->setModel($model->specification()->addCondition('is_system','<>',true),['name','value']);

		//add personalized button

		if($model['is_designable']){
			// add Personalioze View
			$personalized_page_url = $this->app->url(
										$this->options['personalized_page'],
										['xsnb_design_item_id'=>$model['id']]
									);
			$this->add('Button',null,'personalizedbtn')
					->addClass("xepan-commerce-item-personalize btn btn-primary btn-block")
					->set(
							$this->options['personalized_button_label']?:"Personalize"
						)
					->js('click',
							$this->js()->univ()->location($personalized_page_url)
						);
		}else{
			$this->current_row_html['personalizedbtn'] = "";
			$this->current_row_html['personalizedbtn_wrapper'] = "";
		}

		//price calculation or add to cart button setup
		//if item is designable than hide "AddToCart" button
		if($model['is_saleable']){
			$options = [
						'button_name'=>$this->options['addtocart_button_label'],
						'show_addtocart_button'=>$model['is_designable']?0:1,
						'show_price'=>$this->options['show_price_or_amount'],
						'show_multi_step_form'=>$this->options['show_multi_step_form'],
						'form_layout'=>$this->options['multi_step_form_layout'],
						'show_shipping_charge'=>$this->options['show_shipping_charge'],
						'shipping_charge_with_item_amount'=>$this->options['shipping_charge_with_item_amount'],
						'checkout_page' => $this->options['checkout_page'],
						'continue_shopping_page'=>$this->options['continue_shopping_page'],
						'amount_group_in_multistepform'=>$this->options['amount_group_in_multistepform']
						];

			$cart_btn = $this->add('xepan\custom\Tool_Item_AddToCartButton',
				[
					'name' => "addtocart_view_".$model->id,
					'options'=>$options
				],'Addtocart'
				);
			$cart_btn->setModel($model);
		}
		
		parent::setModel($model);
	}

	function defaultTemplate(){
		$layout = $this->options['layout'];

		if($this->options['custom_template']){
			$path = getcwd()."/websites/".$this->app->current_website_name."/www/view/tool/item/detail/layout/".$this->options['custom_template'].".html";
			if(!file_exists($path)){
				throw new \Exception($path);
				$this->add('View_Warning')->set('template not found');
				return;
			}else{
				$layout = $this->options['custom_template'];
			}
		}
		
		return ['view/tool/item/detail/layout/'.$layout];
	}
	
}