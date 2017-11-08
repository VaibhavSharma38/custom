<?php

namespace xepan\custom;

class Model_ItemPrice extends \xepan\commerce\Model_Item_Quantity_Set{
	function init(){
		parent::init();		
	}	

	function importPrice($data){
		$price_m = $this->add('xepan\commerce\Model_Item_Quantity_Set');
		
		foreach ($price_m as $price) {
			$price->delete();
		}

		foreach ($data as $key => $record) {
			try{
				$this->api->db->beginTransaction();
				$price_m = $this->add('xepan\commerce\Model_Item_Quantity_Set');
				$size_array = [];
				$item_id = '';

				foreach ($record as $field => $value) {					
					$field = trim($field);
					$value = trim($value);

					if($field == "sku" && $value){												
						$item_m = $this->add('xepan\commerce\Model_Item');
						$item_m->addCondition('sku',$value);
						$item_m->tryLoadAny();

						if(!$item_m->loaded())
							continue;

						$item_id = $item_m->id;
						$price_m['item_id'] = $item_m->id;			
						continue;
					}

					if($field == "price" && $value != null){						
						$price_m['price'] = $value;
						$price_m['old_price'] = $value;
						continue;
					}

					if($field == "size" && $value){
						$price_m['name'] = $value;
						$price_m['is_default'] = 0;
						$price_m['qty'] = 1;
						$size_array [] = $value; 
						continue;
					}
				}

				$price_m->Save();
				$this->qtySetCondition($item_id, $size_array, $price_m);
				$this->api->db->commit();
			}catch(\Exception $e){
				// echo $e->getMessage()."<br/>";
				// continue;
				throw $e;
				$this->api->db->rollback();
			}
		}
	}

	function qtySetCondition($item_id, $size_array, $price_m){
		$condition_m = $this->add('xepan\commerce\Model_Item_Quantity_Condition');

		foreach ($size_array as $size) {
											
			$custom_field = $this->add('xepan\commerce\Model_Item_CustomField');
			$custom_field->tryLoadBy('name','Size');

			if(!$custom_field->loaded())
				return;				

			$model_cf_asso = $this->add('xepan\commerce\Model_Item_CustomField_Association');
			$model_cf_asso->addCondition('customfield_generic_id',$custom_field->id);
			$model_cf_asso->addCondition('item_id',$item_id);
			$model_cf_asso->tryLoadAny();

			if(!$model_cf_asso->loaded())
				return;

			$model_cf_value = $this->add('xepan\commerce\Model_Item_CustomField_Value');
			$model_cf_value->addCondition('customfield_association_id',$model_cf_asso->id);
			$model_cf_value->addCondition('name',$size);
			$model_cf_value->tryLoadAny();

			if(!$model_cf_value->loaded()){
				$qs_m = $this->add('xepan\commerce\Model_Item_Quantity_Set');
				$qs_m->loadBy($price_m->id);
				$qs_m->delete();
				continue;
			}

			$item_qs_c_m = $this->add('xepan\commerce\Model_Item_Quantity_Condition');
			$item_qs_c_m['quantity_set_id'] = $price_m->id;
			$item_qs_c_m['customfield_value_id'] = $model_cf_value->id;
			$item_qs_c_m->save();

			return;
		}
	}
}