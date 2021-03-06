<?php

namespace xepan\custom;

class Tool_ItemList extends \xepan\cms\View_Tool{
	public $options = [

					'show_item'=>"all", /* all,new,mostviewed,featured*/
					'layout'=>'grid',/* grid,list*/
					/*show or hide options*/
					'show_name'=>true,
					'show_image'=>true,
					'show_sku'=>true,
					'show_sale_price'=>true,
					'show_original_price'=>true,
					'show_description'=>true, 
					'show_tags'=>true,
					'show_specification'=>true,
					'show_qty_unit'=>true,
					'show_qty_selection'=>true,
					'show_stock_availability'=>false,
					'show_is_enquiry_allow'=>false,
					'show_paginator'=>true,
					'show_personalizedbtn'=>true,
					'show_addtocart'=>true,
					'show_multi_step_form'=>false,
					'show_price_or_amount'=>false,
					'filter-effect'=>false,
					'show_item_count'=>true,
					'show_category_name'=>false,
					/**/
					'personalized_page_url'=>'',
					'personalized_button_name'=>'Personalize',
					'paginator_set_rows_per_page'=>"4",
					'show_shipping_charge'=>true,
					'shipping_charge_with_item_amount'=>true,
					'show_item_of_category'=>"",
					'custom_template'=>'',
					'show_microdata'=>true,
					'amount_group_in_multistepform'=>null,
					'show_out_of_stock'=>true,
					'hide_items'=>'',
					'sef'=>''
				];

	public $complete_lister=null;

	function init(){
		parent::init();		
		
		$this->app->stickyForget('parent_category_code');
		$this->app->stickyForget('category_code');
		
		//Validate Required Options Value
		$message = $this->validateRequiredOptions();
		if($message != 1){
			$this->add('View_Warning')->set($message);
			return;
		}
		if($this->options['show_microdata']){
			$this->company_m = $this->add('xepan\base\Model_ConfigJsonModel',
						[
							'fields'=>[
										'company_name'=>"Line",
										'company_owner'=>"Line",
										'mobile_no'=>"Line",
										'company_email'=>"Line",
										'company_address'=>"Line",
										'company_pin_code'=>"Line",
										'company_description'=>"text",
										],
							'config_key'=>'COMPANY_AND_OWNER_INFORMATION',
							'application'=>'communication'
						]);
			$this->company_m->tryLoadAny();
		}

		$item = $this->add('xepan\commerce\Model_Item_WebsiteDisplay');
		$item->addCondition('status','Published');

		$q = $item->dsql();
		
		$this->app->stickyGET('parent_category_id');
		$this->app->stickyGET('xsnb_category_id');
		/**
		category wise filter
		*/
		//tool options show only category item
		$selected_category = [];
		if($this->options["show_item_of_category"]){
			$selected_category = explode(",", $this->options["show_item_of_category"]);
		}elseif($_GET['xsnb_category_id'] and is_numeric($_GET['xsnb_category_id'])){
			$selected_category[] = $_GET['xsnb_category_id'];
		}

		if($category_code = $_GET['category_code']){
			$category_code = explode('/', $category_code);
			$cat_m = $this->add('xepan\custom\Model_Category');
			$cat_m->tryLoadBy('slug_url',$category_code[1]);			
			$selected_category[] = $cat_m->id;
		}

		if(count($selected_category)){
			$item_join = $item->Join('category_item_association.item_id');
			$item_join->addField('category_id');
			$item_join->addField('category_assos_item_id','item_id');
			
			$cat_join = $item_join->leftJoin('category.document_id','category_id');
			$cat_join->addField('category_document_id','document_id');

			$document_join = $cat_join->leftJoin('document.id','document_id');
			$document_join->addField('category_status','status');

			$item->addCondition('category_status',"Active");
			$item->addCondition('category_id',$selected_category);
			
			$group_element = $q->expr('[0]',[$item->getElement('category_assos_item_id')]);
		}

		if($this->options['show_related_items']){
			$current_item_id = $this->app->stickyGET('commerce_item_id');
			$current_item_m = $this->add('xepan\custom\Model_Item');
			$current_item_m->addCondition('id',$current_item_id);

			$item_j = $current_item_m->Join('category_item_association.item_id');
			$item_j->addField('category_id');

			$item_join = $item->Join('category_item_association.item_id');
			$item_join->addField('category_id');
			$item_join->addField('category_assos_item_id','item_id');
			
			$cat_join = $item_join->leftJoin('category.document_id','category_id');
			$cat_join->addField('category_document_id','document_id');

			$document_join = $cat_join->leftJoin('document.id','document_id');
			$document_join->addField('category_status','status');

			$item->addCondition('category_status',"Active");
			$item->addCondition('category_id',$current_item_m['category_id']);
			
			$group_element = $q->expr('[0]',[$item->getElement('category_assos_item_id')]);
		}

		if(!$this->options['show_out_of_stock']){
			$parent_category_id = $_GET['parent_category_id'];
			if(!$parent_category_id){
				$c = $this->add('xepan\custom\Model_Category');
				$c->loadBy('slug_url',$_GET['parent_category_code']);
				$parent_category_id = $c->id;
			}	

			$item->addExpression('net_stock')->set(function($m,$q) use($parent_category_id){
				$item_stock_m = $this->add('xepan\custom\Model_ItemStock');
				$item_stock_m->addCondition('item_id',$m->getElement('id'));
				$item_stock_m->addCondition('category',$parent_category_id);

				return $q->expr('IFNULL([0],"0")',[$item_stock_m->sum('current_stock')]);
			});

			$item->addCondition('net_stock','>',0);
		}
		
		if($this->options['hide_items']){
			if($this->options['hide_items'] === 'hide_in_shop'){
				$item->addCondition($item->dsql()->orExpr()
	    					     		->where('hide_in_shop',0)
	    					     		->where('hide_in_shop',null)
				       			);
			}

			if($this->options['hide_items'] === 'hide_in_product'){
				$item->addCondition($item->dsql()->orExpr()
	    					     		->where('hide_in_product',0)
	    					     		->where('hide_in_product',null)
				       			);
			}
		}

		if($_GET['search']){
			// $item->addExpression('Relevance')->set('MATCH(search_string) AGAINST ("'.$_GET['search'].'" IN NATURAL LANGUAGE MODE)');
			$item->addExpression('Relevance')->set(function($m, $q){
				return $q->expr('MATCH([0]) AGAINST ("[1]" IN NATURAL LANGUAGE MODE)',[$q->getField('search_string'),$_GET['search']]);
			});
			$item->addCondition('Relevance','>',0);
	 		$item->setOrder('Relevance','Desc');
		}

		// //Price Range Search
		if($price_range = $this->app->recall('price_range')){
			$price_array = explode(",", $price_range);
			$item->addCondition('sale_price','>=',$price_array[0]);
			$item->addCondition('sale_price','<=',$price_array[1]);
			$this->app->forget('price_range');
		}

		
		// //Filter Search
		$filter = $this->app->recall('filter',false);
		$selected_filter_data_array = json_decode($filter,true);
		if($this->options['filter-effect'] and count($selected_filter_data_array)){

			$item_custom_field_asso_j = $item->Join('customfield_association.item_id','id');
			$item_custom_field_asso_j->addField('customfield_generic_id');
			$item_custom_field_asso_j->addField('specification_item_id','item_id');

			$custom_field_j = $item_custom_field_asso_j->join('customfield_generic.id','customfield_generic_id');
			$custom_field_j->addField('cf_is_filterable','is_filterable');

			$item->addCondition('cf_is_filterable',true);

			$cf_asso_value_j = $item_custom_field_asso_j->join('customfield_value.customfield_association_id','id');
			$cf_asso_value_j->addField('value_name','name');
			$cf_asso_value_j->addField('value_status','status');

			$item->addCondition('value_status','Active');
			
			$cond=[];
			foreach ($selected_filter_data_array as $specification_id => $values_array) {
				if(empty($values_array)) continue;
				
				$or = $q->orExpr();
				foreach ($values_array as $value) {
					$value = str_replace("\"","",$value);
					$or->where($q->expr('[0] = "[1]"',[$item->getElement('value_name'),$value]));
				}

				$item->addCondition($q->andExpr()
									->where('customfield_generic_id',$specification_id)
									->where($or)
									);
			}


			$group_element = $q->expr('[0]',[$item->getElement('specification_item_id')]);
			$item->_dsql()->group($group_element); // Multiple category association shows multiple times item so .. grouped

			$this->app->forget('filter');
		}


		//load record according to sequence of order 
		$item->setOrder('display_sequence','desc');
		//$item->setOrder('name','asc');

		$layout_template = $this->options['layout'];
		
		if($this->options['custom_template']){
			$path = getcwd()."/websites/".$this->app->current_website_name."/www/view/tool/item/".$this->options['custom_template'].".html";
			if(!file_exists($path)){
				throw new \Exception($path);
				$this->add('View_Warning')->set('template not found');
				return;	
			}else{
				$layout_template = $this->options['custom_template'];
				
			}
		}	
		$this->complete_lister = $cl = $this->add('CompleteLister',null,null,['view/tool/item/'.$layout_template]);
		//not record found
		if(!$item->count()->getOne())
			$cl->template->set('not_found_message','No Record Found');
		else
			$cl->template->del('not_found');

		$designer = $this->add('xepan\base\Model_Contact');
		if($designer_id = $this->app->stickyGET('designer_id')){
			$item->addCondition('designer_id',$designer_id);
			$designer->load($designer_id);	
		}

		$cl->setModel($item);

		if( !($this->options['show_item_count'] or $this->options['show_category_name'])){
			$cl->template->tryDel('item_count_wrapper');
		}else{		
			// show item count
			if($this->options['show_item_count']){
				$cl->template->trySet('item_count',$item->count()->getOne());
			}
			
			if($this->options['show_category_name']){
				$str = "";
				foreach ($selected_category as $cat_id) {
					$ct_model = $this->add('xepan\custom\Model_Category')->tryLoad($cat_id);
					if($ct_model->loaded()){
						$str .= $ct_model['name'] .", ";
					}
				}
				$cl->template->trySet('category_name',rtrim($str,", "));
				if($designer_id)
					$cl->template->trySet('designer_name',$designer['name']);
			}
		}	

		if($this->options['show_paginator']=="true"){
			$paginator = $cl->add('Paginator',['ipp'=>$this->options['paginator_set_rows_per_page']]);
			$paginator->setRowsPerPage($this->options['paginator_set_rows_per_page']);
		}


		$cl->add('xepan\cms\Controller_Tool_Optionhelper',['options'=>$this->options,'model'=>$item]);

		$self = $this;
		$url = $this->app->url($this->options['personalized_page_url']);
		//click in personilize btn redirect to personilize pag
		$cl->on('click','.xepan-commerce-item-personalize',function($js,$data)use($url,$self){
			$url = $self->app->url($url,['xsnb_design_item_id'=>$data['xsnbitemid']]);
			return $js->univ()->location($url);
		});

	}

	function render(){

		$this->js(true)
				->_load($this->api->url()->absolute()->getBaseURL().'vendor/xepan/commerce/templates/js/tool/jquery-elevatezoom.js')
				->_load($this->api->url()->absolute()->getBaseURL().'vendor/xepan/commerce/templates/js/tool/jquery.fancybox.js');
		
		parent::render();
	}

	function addToolCondition_show_item($value,$model){

		switch ($value) {
			case 'all':
				// No Need to any condition because on all it' show all item 
				// $model->addCondition(
				// 			$model->dsql()->orExpr()
				// 				->where('is_new',true)
				// 				->where('is_mostviewed',true)
				// 				->where('is_feature',true)
				// 			);
				break;
			case 'new':
				$model->addCondition('is_new',true);
				break;
			case 'mostviewed':
				$model->addCondition('is_mostviewed',true);
				break;
			case 'featured':
				$model->addCondition('is_feature',true);
				break;
		}
	}

	function addToolCondition_row_show_personalizedbtn($value,$l){		
		if($l->model['is_designable']){			
			$btn = $l->add('Button',null,'personalizedbtn')
				->addClass('xepan-commerce-item-personalize btn btn-primary btn-block')
				->setAttr('data-xsnbitemid',$l->model->id)
				;
			$btn->set($this->options['personalized_button_name']?:'Personalize');
			$l->current_row_html['personalizedbtn'] = $btn->getHtml();
		}else{
			// $l->current_row_html['personalizedbtn_wrapper'] = "";
			$l->current_row_html['personalizedbtn'] = "";
		 }
	}

	function addToolCondition_row_show_microdata($value,$l){
		
		$v=$this->add('CompleteLister',null,null,['view/schema-micro-data','Product_list_block']);
		$v->setModel(clone $l->model);
		
		$v->addHook('formatRow',function($m){
			// $m->current_row_html['company_name']=$this->company_m['company_name'];
			$m->current_row_html['item_image']=$this->app->pm->base_url.$m->model['first_image'];
			$m->current_row_html['currency']=$this->app->epan->default_currency->get('name');
		});
		$l->current_row_html['micro_data']=$v->getHtml();
	}

	function addToolCondition_row_show_image($value, $l){
		if(!$value){
			$l->current_row_html['image_wrapper'] = "";
			return;
		}

		if(!$l->model['first_image'])
			$l->current_row['first_image'] = "vendor/xepan/commerce/templates/view/tool/item/images/xepan_item_list_no_image.jpg";
		
	}

	function addToolCondition_row_show_addtocart($value,$l){
		
		if($value != true){
			$l->current_row_html['addtocart_wrapper'] = "";
			return;
		}

		if($l->model['is_saleable']){
			$options = [
						'show_addtocart_button'=>'true',
						'button_name'=>$this->options['addtocart_name'],
						'show_shipping_charge'=>$this->options['show_shipping_charge'],
						'shipping_charge_with_item_amount'=>$this->options['shipping_charge_with_item_amount'],
						'show_price'=>$this->options['show_price_or_amount'],
						'show_multi_step_form'=>$this->options['show_multi_step_form'],
						'amount_group_in_multistepform'=>$this->options['amount_group_in_multistepform']
						];

			$cart_btn = $l->add('xepan\custom\Tool_Item_AddToCartButton',
					[
						'name' => "addtocart_view_".$l->model->id,
						'options'=>$options
					],'Addtocart'
				);
		
			$item = $this->add('xepan\custom\Model_Item')->load($l->model->id);
			$cart_btn->setModel($item);
			$l->current_row_html['Addtocart'] = $cart_btn->getHtml();
		}else
			$l->current_row_html['Addtocart'] = "";

	}

	function addToolCondition_row_item_detail_page_url($value,$l){
		$url = $this->api->url();
		// $detail_page_url = $this->api->url($this->options['item_detail_page_url'],['commerce_item_id'=>$l->model->id]);		
		// $detail_page_url = $this->api->url("product/".$_GET['parent_category_code'].'/'.$_GET[' category_code'].'/'.$l->model['slug_url']);

		if($this->options['name_redirect_to_detail'] == "true"){
			$l->current_row_html['item_detail_page_url_via_name'] = $this->api->url($this->options['sef'].'/'.$_GET['parent_category_code'].'/'.$_GET[' category_code'].'/'.$l->model['slug_url']);
		}else{			
			$l->current_row_html['item_detail_page_url_via_name'] = $url;
		}

		if($this->options['image_redirect_to_detail'] == "true")
			$l->current_row_html['item_detail_page_url_via_image'] = $this->api->url($this->options['sef']."/".$_GET['parent_category_code'].'/'.$_GET[' category_code'].'/'.$l->model['slug_url']);
		else
			$l->current_row_html['item_detail_page_url_via_image'] = $url;
			
	}

	function addToolCondition_row_show_specification($value,$l){
		
		if(!$value){
			$l->current_row_html['specification']='';
			return;
		}

		$l->model->specifications = $specification = $l->model->specification(null,$highlight_only = true);
		$temp = $l->add('CompleteLister',null,'specification',['view/tool/item/'.$this->options['layout'],'specification']);
		$temp->setModel($specification);

		$l->current_row_html['specification'] = $temp->getHtml();
	}

	function addToolCondition_row_show_description($value,$l){
		if(!$value){
			$l->current_row_html['description']='';
			return;
		}
		if($this->options['show_description']){
			$l->current_row_html['description']=$l->model['description'];
		}else{
			$l->current_row_html['description']=" ";
		}
	}

	function addToolCondition_row_show_shipping_charge($value,$l){
		if(!$value){
			$l->current_row_html['shipping_charge'] = "";
			return;
		}

		if($this->options['shipping_charge_with_item_amount']){
			$l->current_row_html['shipping_charge'] = "";
			$l->current_row_html['shipping_charge_wrapper'] = "";
		}else
			$l->current_row_html['shipping_charge'] = "0";	

	}

	function validateRequiredOptions(){
		return true;
	}

	function getTemplate(){
		return $this->complete_lister->template;
	}

	function getTemplateFile(){
		return $this->complete_lister->template->origin_filename;
	}
}
