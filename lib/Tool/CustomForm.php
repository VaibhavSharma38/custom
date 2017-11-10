<?php 

namespace xepan\custom;

class Tool_CustomForm extends \xepan\cms\View_Tool{
	public $options = ['customformid'=>0, 'template'=>'','custom_form_success_url'=>null];
	public $form;
	public $customform_model;
	public $customform_field_model;

	function init(){
		parent::init();


		if(!$this->options['customformid']){
			$this->add("View_Error")->set('please select any custom form');
			return;
		}

		$this->customform_model = $customform_model = $this->add('xepan\cms\Model_Custom_Form')
								->tryLoad($this->options['customformid']);
		
		if(!$customform_model->loaded()){
			$this->add('View_Error')->set('no such form found...');
			return;
		}

		$this->customform_field_model = $customform_field_model = $this->add('xepan\cms\Model_Custom_FormField')
												->addCondition('custom_form_id',$customform_model->id);
		
		if(!$customform_field_model->count()->getOne()){
			$this->add("View_Warning")->set('add form fields...');
			return;
		}

		$this->form = $this->add('Form');
		$form = $this->form;

		if($this->options['template'])			
			$form->setLayout('view/tool/form/'.$this->options['template']);

		foreach ($customform_field_model as $field) {

			if($field['type'] === "email"){

				$new_field = $form->addField("line",$field['name']);
				$new_field->validate('email');
			}else if($field['type'] === "Captcha"){				
				$new_field = $form->addField('line','captcha',$field['name']);
				$new_field->add('xepan\captcha\Controller_Captcha');
			}else if($field['type'] === "DropDown"){
				$new_field = $form->addField('xepan\base\DropDownNormal',$field['name']);
			}elseif($field['type'] == "upload"){
				$new_field = $form->addField('xepan\base\Upload',$field['name']);
			}else{
				$new_field = $form->addField($field['type'],$field['name']);
			}
			
			if($field['type'] === "DropDown" or $field['type'] === "radio"){
				$field_array = explode(",", $field['value']);
				$new_field->setValueList(array_combine($field_array,$field_array));
			}

			if($field['is_mandatory'])
				$new_field->validate('required');
		}				
		
		$this->form->addSubmit($customform_model['submit_button_name']);

		if($this->form->isSubmitted()){
			if($form->hasElement('captcha') && !$form->getElement('captcha')->captcha->isSame($form['captcha'])){
				$form->displayError('captcha','wrong Captcha');	
			}
			$model_submission = $this->add('xepan\cms\Model_Custom_FormSubmission');
			$form_fields = $form->getAllFields();
			
			$model_submission['value'] = $form_fields;
			$model_submission['custom_form_id'] = $this->options['customformid'];
			$model_submission->save();

			// creating lead and associating category and email id
			if($this->customform_model['is_create_lead']){
				
				if($this->form['honeydip'])
					return;

				$field_model = $this->add('xepan\cms\Model_Custom_FormField')
							->addCondition('custom_form_id',$this->customform_model->id)
							->addCondition('save_into_field_of_lead','<>',null);

				$lead_model = $this->add('xepan\marketing\Model_Lead');
				// $has_field = 0;
				$lead_field = ['first_name','last_name','organization','post','website','address','city','state','country','pin_code'];
				foreach ($field_model as $field){
					if(in_array($field['save_into_field_of_lead'], $lead_field)){
						// echo $field['save_into_field_of_lead']." = ".$field['name']." = ".$this->form[$this->app->normalizeName($field['name'])]."<br/>";
						$lead_model[$field['save_into_field_of_lead']] = $this->form[$this->app->normalizeName($field['name'])];
						$has_field = 1;
					}else{
						if($lead_model['first_name']) continue;

						if($field['save_into_field_of_lead'] == 'official_email'){
							$lead_model['first_name'] = $this->form[$this->app->normalizeName($field['name'])];
							$has_field = 1;
						}elseif($field['save_into_field_of_lead'] == 'personal_email'){
							$lead_model['first_name'] = $this->form[$this->app->normalizeName($field['name'])];
							$has_field = 1;
						}elseif($field['save_into_field_of_lead'] == 'official_contact'){
							$lead_model['first_name'] = $this->form[$this->app->normalizeName($field['name'])];
							$has_field = 1;
						}elseif($field['save_into_field_of_lead'] == 'personal_contact'){
							$lead_model['first_name'] = $this->form[$this->app->normalizeName($field['name'])];
							$has_field = 1;
						}
					}
				}


				if($has_field){

					$lead_model->save();
					foreach ($field_model as $field){
						$save_into_field = $field['save_into_field_of_lead'];
						$normalize_name = $this->app->normalizeName($field['name']);
						$form_value = $this->form[$normalize_name];

						// company email
						if( $save_into_field == "official_email"){
							$email = $this->add('xepan\base\Model_Contact_Email');
							$email['contact_id'] = $lead_model->id;
							$email['head'] = "Official";
							$email['value'] = $form_value;
							$email->save();
						}

						if($save_into_field == "personal_email"){
							$email = $this->add('xepan\base\Model_Contact_Email');
							$email['contact_id'] = $lead_model->id;
							$email['head'] = "Personal";
							$email['value'] = $form_value;
							$email->save();
						}

						// company phone 
						if( $save_into_field == "official_contact"){
							$phone = $this->add('xepan\base\Model_Contact_Phone');
							$phone['contact_id'] = $lead_model->id;
							$phone['head'] = "Official";
							$phone['value'] = $form_value;
							$phone->save();
						}
						// personal phone 
						if( $save_into_field == "personal_contact"){
							$phone = $this->add('xepan\base\Model_Contact_Phone');
							$phone['contact_id'] = $lead_model->id;
							$phone['head'] = "Personal";
							$phone['value'] = $form_value;
							$phone->save();
						}
					}

					// associate lead
					if($this->customform_model['is_associate_lead']){

						$categories = explode(",",$this->customform_model['lead_category_ids']);
						foreach ($categories as $key => $cat_id) {
							if(!is_numeric($cat_id))
								continue;
							$cat_asso_model = $this->add('xepan\marketing\Model_Lead_Category_Association');
							$cat_asso_model['lead_id'] = $lead_model->id;
							$cat_asso_model['marketing_category_id'] = $cat_id;
							$cat_asso_model['created_at'] = $this->app->now;
							$cat_asso_model->save();
						}
					}

				}

			}

			if($customform_model['emailsetting_id']){
				$communication = $this->add('xepan\communication\Model_Communication_Email_Sent');
				$email_settings = $this->add('xepan\communication\Model_Communication_EmailSetting')->load($customform_model['emailsetting_id']);

				$communication->setfrom($email_settings['from_email'],$email_settings['from_name']);
				foreach (explode(",", $customform_model['recipient_email']) as $key => $value) {
					$communication->addTo($value);
				}

				$string = str_replace("{","",$model_submission['value']);
				$string = str_replace("}","",$string);
				$string = str_replace(":"," = ",$string);
				$string = str_replace(",","<br><br>",$string);
				$string = str_replace("\"","",$string);

				if($customform_model['name'] == 'Contact Form'){
					$communication->setSubject('Enquiry from saraswati global website`s contact form');
				}elseif($customform_model['name'] == 'Job Application'){
					$communication->setSubject('Job application on saraswati global`s website');
				}elseif($customform_model['name'] == 'Custom Rug Form'){
					$communication->setSubject('Custom rug request on saraswati global`s website');
				}else{
					$communication->setSubject('New enquiry from saraswati global website');
				}	

				$communication->setBody($string);
				$communication->send($email_settings);

				if($customform_model['auto_reply']){
					$email_settings = $this->add('xepan\communication\Model_Communication_EmailSetting')->load($customform_model['emailsetting_id']);	
					$communication1 = $this->add('xepan\communication\Model_Communication_Email_Sent');
					$to_array = [];
					foreach ($customform_field_model as $field) {
						if( !($field['type'] == "email" and $field['auto_reply']))
							continue;

						$to_array[] = $this->form[$field['name']];
					}

					
					foreach ($to_array as $email) {
						$communication1->setfrom($email_settings['from_email'],$email_settings['from_name']);
						$communication1->addTo($email);
						$communication1->setSubject($customform_model['email_subject']);
						$communication1->setBody($customform_model['message_body']);
						$communication1->send($email_settings);					
					}

				}
			}

			if($this->options['custom_form_success_url']){
				// throw new \Exception("Error Processing Request", 1);
				$form->js()->redirect($this->app->url($this->options['custom_form_success_url']))->execute();
				// $form->js(null,$form->js()->reload())->univ()->successMessage("Thank you for enquiry")->execute();
			}else{
				$form->js(null,$form->js()->reload())->univ()->successMessage("Thank you for enquiry")->execute();
			}
		}
	}

	function getTemplate(){
		if($this->options['template'])
			return $this->form->layout->template;
		return $this->form->template;
	}

}