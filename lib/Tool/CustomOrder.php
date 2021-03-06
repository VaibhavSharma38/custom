<?php

namespace xepan\custom;

class Tool_CustomOrder extends \xepan\cms\View_Tool{
	public $customer_id = '';

	function init(){
		parent::init();
        
    $customer = $this->add('xepan\commerce\Model_Customer');
    $customer->loadLoggedIn("Customer");

    if(!$customer->loaded()){
        $this->add('View_Info')->set('Please Login To Add Orders')->addClass('jumbotron well text-center row alert alert-info h3');
        return;            
    }

    $customer_auth_m = $this->add('xepan\custom\Model_CustomerAuth');
   
    $customer_auth_m->addCondition('customer_id',$customer->id);
    $customer_auth_m->addCondition('custom_order',true);
    $customer_auth_m->tryLoadAny();

    if(!$customer_auth_m->loaded()){
      $this->add('View_Info')->set('Contact Us At info@saraswatiglobal To Place Order')->addClass('jumbotron well text-center row alert alert-info h3');
      return; 
    }

    $this->customer_id = $customer->id;

		// ORDER
    $order_m = $this->add('xepan\custom\Model_CustomOrder');
    $order_m->addCondition('created_by_id',$customer->id);  
    $order_c = $this->add('xepan\base\CRUD',null,null,['view\tool\customorder']);
    if($order_c->isEditing())
      $order_c->form->setLayout('view\tool\form\customorder');
    
    $order_c->setModel($order_m,['customer_name','order_no','deliver_date','ship_to','ship_method','instructions'],['created_at','customer_name','order_no','deliver_date','ship_to','ship_method','instructions']);

    $order_c->grid->add('VirtualPage')->addColumn('OrderItems')
            ->set(function($page){
              $id = $_GET[$page->short_name.'_id'];
              $order_info_m = $page->add('xepan\custom\Model_CustomOrderInfo');
              $order_info_m->addCondition('custom_order_id',$id);

              $order_info_c = $page->add('xepan\base\CRUD',null,null,['view\tool\customorderinfo']);
          
              if($order_info_c->isEditing()){
                $order_info_c->form->setLayout('view\tool\form\customorderinfo');
              }
              $order_info_c->setModel($order_info_m,['collection','design','color','size','qty','price','narration'],['collection','design','color','size','qty','price','narration']);
            });

    $order_c->grid->add('VirtualPage')->addColumn('Email')
        ->set(function($page)use($customer){
          $email_array = $customer->getEmails();
          $email_array [] = 'info@saraswatiglobal.com';
          $id = $_GET[$page->short_name.'_id'];
          $form = $page->add('Form');
          $form->add('View')->setHTML('Mail will be sent to <b> '.implode(',',$email_array).' </b>')->setStyle('margin-bottom:10px; font-size:16px;');
          $form->addSubmit('SEND EMAIL');

          if($form->isSubmitted()){
            $this->sendEmail($id, implode(',',$email_array));
            $js = [
              $page->js()->univ()->successMessage('Email Sent')
            ];
            return $form->js(null,$js)->reload()->execute();
          }

        });
  }

  function sendEmail($id, $emails){
    $custom_order = $this->add('xepan\custom\Model_CustomOrder');
    $custom_order->load($id);

    $custom_order_info = $this->add('xepan\custom\Model_CustomOrderInfo');
    $custom_order_info->addCondition('custom_order_id',$custom_order->id);

    $communication = $this->add('xepan\communication\Model_Communication_Abstract_Email');
    $communication->getElement('status')->defaultValue('Draft');
    $communication['direction']='Out';

    $email_settings = $this->add('xepan\communication\Model_Communication_EmailSetting')->tryLoadAny();

    $communication->setfrom($email_settings['from_email'],$email_settings['from_name']);
    $communication->addCondition('communication_type','Email');

    foreach (explode(",", $emails) as $value) {
      $communication->addTo(trim($value));
    }

    $body_v = $this->add('View',null,null,['view\email\custom-order']);
    $body_v->setModel($custom_order);

    $body_v->template->trySet($custom_order,['customer_name','order_no','deliver_date','ship_to','ship_method','instructions']);

    $item_lister = $body_v->add('Lister',null,'item_lister',['view\email\custom-order-info']);
    $item_lister->setModel($custom_order_info,['collection','design','color','size','qty','price','narration']);  
     
    $customer_m = $this->add('xepan\commerce\Model_Customer');
    $customer_m->load($custom_order['created_by_id']);

    $communication->setSubject('New custom order generated by '.$customer_m['name'].' on SGPL website');
    $communication->setBody($body_v->getHtml());
    $communication->save();
    
    $pdf_v = $this->add('View',null,null,['view\email\custom-order-pdf']);
    $pdf_v->setModel($custom_order);
    $pdf_v->template->trySet($custom_order,['customer_name','order_no','deliver_date','ship_to','ship_method','instructions']);
    $item_lister_pdf = $pdf_v->add('Lister',null,'item_lister',['view\email\custom-order-info-pdf']);
    $item_lister_pdf->setModel($custom_order_info,['collection','design','color','size','qty','price','narration']);  

    $file = $this->add('xepan/filestore/Model_File',array('policy_add_new_type'=>true,'import_mode'=>'string','import_source'=>$this->generatePDF('return', $pdf_v, $custom_order)));
    $file['filestore_volume_id'] = $file->getAvailableVolumeID();
    $file['original_filename'] =  strtolower('custom order').'_'.$custom_order['order_no'].'.pdf';
    $file->save();
    
    $communication->addAttachment($file->id);
    $communication->send($email_settings);
  }

  function generatePDF($action = "return", $pdf_v, $custom_order){
    if(!in_array($action, ['return','dump']))
      throw $this->exception('Please provide action as result or dump');

    $pdf = new \TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
    // set document information
    $pdf->SetCreator(PDF_CREATOR);
    $pdf->SetAuthor('xEpan ERP');
    $pdf->SetTitle('custom order '. $custom_order['order_no']);
    $pdf->SetSubject('custom order '. $custom_order['order_no']);
    $pdf->SetKeywords('custom order '. $custom_order['order_no']);

    // set default monospaced font
    $pdf->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);
    // set font
    $pdf->SetFont('dejavusans', '', 10);
    //remove header or footer hr lines
    $pdf->SetPrintHeader(false);
    $pdf->SetPrintFooter(false);
    // add a page
    $pdf->AddPage();

    $html = $pdf_v->getHTML();
    // echo "string".$html;

    // echo $html;
    // exit;

    // output the HTML content
    $pdf->writeHTML($html, false, false, true, false, '');
    // set default form properties
    $pdf->setFormDefaultProp(array('lineWidth'=>1, 'borderStyle'=>'solid', 'fillColor'=>array(255, 255, 200), 'strokeColor'=>array(255, 128, 128)));
    // reset pointer to the last page
    $pdf->lastPage();
    //Close and output PDF document
    switch ($action) {
      case 'return':
        return $pdf->Output(null, 'S');
        break;
      case 'dump':
        return $pdf->Output(null, 'I');
        exit;
      break;
    }
  }
}