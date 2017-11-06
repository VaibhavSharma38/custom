<?php

namespace xepan\custom;

class Tool_PopupCard extends \xepan\cms\View_Tool{
	public $options = ['show_popup'=>true,
					   'show_image'=>true,
					   'show_form'=>true
					];

	function init(){
		parent::init();

		$card_m = $this->add('xepan\custom\Model_Popup');
		$card_m->addCondition('status','Active');
		$card_m->tryLoadAny();

		if(!$card_m->loaded()){
			$this->options['show_popup'] = false;
		}

		// $value = isset($_COOKIE['xepan_popupcard']);

		// if($value)
		// 	$this->options['show_popup'] = false;

		if($this->options['show_form']){			
			$form = $this->add('Form',null,'form');
			// $form->setLayout('view/tool/form/popupcard');
			$form->addField('line','name','')->addClass('form-control popup-input')->setAttr(['placeholder'=>'Name']);
			$form->addField('line','email','')->addClass('form-control popup-input')->setAttr(['placeholder'=>'Email']);
			$new_field = $form->addField('line','captcha','')->addClass('popup-input')->setAttr(['placeholder'=>'Captcha']);
			$new_field->add('xepan\captcha\Controller_Captcha');
			$form->addSubmit('Submit')->addClass('btn btn-primary btn-popup-submit');

			if($form->isSubmitted()){
				if($form->hasElement('captcha') && !$form->getElement('captcha')->captcha->isSame($form['captcha']))
					return $this->js()->univ()->alert('Wrong Captcha, click on captcha and reload it')->execute();
					// $form->displayError('captcha','wrong Captcha');	

				if($form['name'] == '')
					return $this->js()->univ()->alert('Fill your name')->execute();

				if(!filter_var(trim($form['email']), FILTER_VALIDATE_EMAIL))
					return $this->js()->univ()->alert('Enter a valid email id')->execute();

				$ei = $this->add('xepan\base\Model_Contact_Email');
				$ei->tryLoadBy('value',$form['email']);

				if($ei->loaded()){
					$l_id = $ei['contact_id'];
					$l_model = $this->add('xepan\marketing\Model_Lead')->load($l_id);
					$cat_arr = $l_model->getAssociatedCategories();
					if(in_array('5595', $cat_arr))
						return $this->js(true,$form->js()->univ()->errorMessage('Already Subscribed'))->_selector('#'.$this->name."_card_model")->modal('hide')->execute();
						// return $form->js()->univ()->errorMessage('Already Subscribed')->execute();
					

					$category_m = $this->add('xepan\marketing\Model_MarketingCategory');
					$category_m->loadBy('name','Online Subscriptions');

					$association = $this->add('xepan\marketing\Model_Lead_Category_Association');
					$association->addCondition('lead_id',$l_model->id);
					$association->addCondition('marketing_category_id',$category_m->id);  
					$association->tryLoadAny();

					if(!$association->loaded()){
						$association['created_at'] = $this->app->now;  
						$association->save();
					}

					return $this->js(true,$form->js()->univ()->successMessage(' Subscribed'))->_selector('#'.$this->name."_card_model")->modal('hide')->execute();
				}				

				$lead = $this->add('xepan\marketing\Model_Lead');				
				try{
					
					$this->api->db->beginTransaction();
					$lead['source'] = 'Subscription';
					
					$lead['first_name'] = $form['name'];
					
					$lead->save();

					$category_m = $this->add('xepan\marketing\Model_MarketingCategory');
					$category_m->loadBy('name','Online Subscriptions');

					$assoc = $this->add('xepan\marketing\Model_Lead_Category_Association');
					$assoc['lead_id'] = $lead->id;
					$assoc['marketing_category_id'] = $category_m->id;  
					$assoc['created_at'] = $this->app->now;  
					$assoc->save();

					$email_info = $this->add('xepan\base\Model_Contact_Email');
					$email_info['contact_id'] = $lead->id;
					$email_info['head'] = 'Official';
					$email_info['value'] = $form['email'];
					$email_info->save();
					$this->api->db->commit();
				}catch(\Exception $e){
					throw $e;
					$this->api->db->rollback();	    			
	    			return $form->error('email','Please try again');
	    		}										

				$this->sendThankYouMail($form['email']);
				return $this->js(true,$form->js()->univ()->successMessage(' Subscribed'))->_selector('#'.$this->name."_card_model")->modal('hide')->execute();	
			}
		}

		if($this->options['show_image']){
			$this->template->trySet('image',$card_m['image']);
			$this->template->trySet('url',$card_m['link']);
		}
		
		setcookie('xepan_popupcard',true);
	}

	function render(){
		if($this->options['show_popup'])
			$this->js(true)->_selector('#'.$this->name."_card_model")->modal('show');
		
		parent::render();
	}

	function defaultTemplate(){
		return ['view\tool\card'];
	}

	function sendThankYouMail($email_id){								
		if(!$email_id)
			return;

		$email_settings = $this->add('xepan\communication\Model_Communication_EmailSetting')->tryLoadAny();
		$mail = $this->add('xepan\communication\Model_Communication_Email');
		
		// $sub_model=$this->app->epan->config;
		// $email_subject=$sub_model->getConfig('SUBSCRIPTION_MAIL_SUBJECT');
		// $email_body=$sub_model->getConfig('SUBSCRIPTION_MAIL_BODY');												

		$frontend_config_m = $this->add('xepan\base\Model_ConfigJsonModel',
		[
			'fields'=>[
						'user_registration_type'=>'DropDown',
						'reset_subject'=>'xepan\base\RichText',
						'reset_body'=>'xepan\base\RichText',
						'update_subject'=>'Line',
						'update_body'=>'xepan\base\RichText',
						'registration_subject'=>'Line',
						'registration_body'=>'xepan\base\RichText',
						'verification_subject'=>'Line',
						'verification_body'=>'xepan\base\RichText',
						'subscription_subject'=>'Line',
						'subscription_body'=>'xepan\base\RichText',
						],
				'config_key'=>'FRONTEND_LOGIN_RELATED_EMAIL',
				'application'=>'communication'
		]);
		$frontend_config_m->tryLoadAny();

		$email_subject = $frontend_config_m['subscription_subject'];
		$email_body = $frontend_config_m['subscription_body'];

		$subject_temp = $this->add('GiTemplate');
		$subject_temp->loadTemplateFromString($email_subject);
		
		$subject_v = $this->add('View',null,null,$subject_temp);

		$temp=$this->add('GiTemplate');
		$temp->loadTemplateFromString($email_body);
		
		$body_v = $this->add('View',null,null,$temp);
		$body_v->template->trySet('username',$email_id);					

		$mail->setfrom($email_settings['from_email'],$email_settings['from_name']);
		$mail->addTo($email_id);
		$mail->setSubject($subject_v->getHtml());
		$mail->setBody($body_v->getHtml());
		$mail->send($email_settings);
	}
}