<?php

namespace xepan\custom;

class Tool_ItemEnquiry extends \xepan\cms\View_Tool{
	function init(){
		parent::init();

		$vp = $this->add('VirtualPage');
		$vp->set(function($p){
			$item_code = $this->app->stickyGET('item_code');

			$form = $p->add('Form');
			$form->setLayout(['view\tool\form\itemenquiry']);
			$form->addField('name');
			$form->addField('organization');
			$form->addField('email');
			$form->addField('contact_no');
			$form->addField('address');
			$form->addField('city');
			$form->addField('state');
			$form->addField('country');
			$form->addField('text','requirements');
			$form->addSubmit('Submit Enquiry');

			$item_m = $this->add('xepan\custom\Model_Item');
			$item_m->tryLoadBy('slug_url',$item_code);
			
			$custom_fields = $item_m->activeAssociateCustomField();
		
			if($form->isSubmitted()){
				if($form['name'] == ''){
					$form->displayError('name','Name field is mandatory');
				}

				if (filter_var($form['email'], FILTER_VALIDATE_EMAIL) === false) {
					$form->displayError('email','Please type a valid email address');
				}

				if($form['contact_no'] == ''){
					$form->displayError('contact_no','Contact field is mandatory');
				}				

				$enquiry_m = $p->add('xepan\custom\Model_ItemEnquiry');
				$enquiry_m['name'] = $form['name'];
				$enquiry_m['organization'] = $form['organization'];
				$enquiry_m['email'] = $form['email'];
				$enquiry_m['contact_no'] = $form['contact_no'];
				$enquiry_m['address'] = $form['address'];
				$enquiry_m['city'] = $form['city'];
				$enquiry_m['state'] = $form['state'];
				$enquiry_m['country'] = $form['country'];
				$enquiry_m['requirements'] = $form['requirements'];
				$enquiry_m['item_id'] = $_GET['item_id'];

				if($this->app->auth->model->id){
					$contact_m = $this->add('xepan\base\Model_Contact');
					$contact_m->loadBy('user_id',$this->app->auth->model->id);
					$enquiry_m['customer_id'] = $contact_m->id;
				}

				$enquiry_m->save();
				
				$item_m = $this->add('xepan\custom\Model_Item');
				$item_m->tryLoadBy('slug_url',$_GET['item_code']);

				$this->sendEmail($form, $item_m['sku']);

				$form->js()->univ()->successMessage('Enquiry Send')->execute();
			}

		});

		$button = $this->add('Button')->set('Submit Enquiry')->setHTML('<span style ="font-size:16px;"><i class="glyphicon glyphicon-envelope"></i></span> <br>Submit Enquiry')->addClass('enquiry-button');
		
		$button->js('click',$this->js()->univ()->frameURL("Send Enquiry",$this->api->url($vp->getURL(),['item_code'=>$_GET['item_code']])))->_selector('.enquiry-button');
	}

	function sendEmail($form, $item_name){
		$string = "Name"." = ".$form['name']."<br><br>";
		$string .= "Organization"." = ".$form['organization']."<br><br>";
		$string .= "Email"." = ".$form['email']."<br><br>";
		$string .= "Contact"." = ".$form['contact_no']."<br><br>";
		$string .= "Address"." = ".$form['address']."<br><br>";
		$string .= "City"." = ".$form['city']."<br><br>";
		$string .= "State"." = ".$form['state']."<br><br>";
		$string .= "Country"." = ".$form['country']."<br><br>";
		$string .= "Requirements"." = ".$form['requirements']."<br><br>";
		$string .= "Item Name"." = ".$item_name."<br><br>";

		$communication = $this->add('xepan\communication\Model_Communication_Email_Sent');
		$email_settings = $this->add('xepan\communication\Model_Communication_EmailSetting')->tryLoadAny();
		$communication->setfrom($email_settings['from_email'],$email_settings['from_name']);
		$communication->addTo('info@saraswatiglobal.com');
		$communication->setSubject('Enquiry for item on saraswati global website');
		$communication->setBody($string);
		$communication->send($email_settings);
	}
}