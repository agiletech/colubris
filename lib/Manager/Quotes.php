<?php
class Manager_Quotes extends View {
    public $quotes,$acceptance;
    function init(){
        parent::init();

        $this->api->stickyGET('id');
        $this->api->stickyGET($this->name);

        

        $v=$this->add('View')->setClass('left');
        
        $b=$v->add('Button')->set('Request For Quotation');
        $b->js('click', array(
        		$this->js()->univ()->redirect($this->api->url('manager/quotes/rfq'))
        ));
        
        $v=$this->add('View')->setClass('clear');
        
        $this->add('P');
        
        $cr=$this->add('CRUD', array('grid_class'=>'Grid_Quotes','allow_add'=>false));
        $m=$this->add('Model_Quote');
        $cr->setModel($m,
        		array('project_id','name','general','duration','deadline','status'),
        		array('project','user','name','estimated','spent_time','durdead','status')
        		);
        if($cr->grid){
        	$cr->grid->addFormatter('status','status');
        	$cr->grid->addColumn('button','requirements');
        	$cr->grid->addColumn('button','details');
        	$cr->grid->addColumn('button','estimation','Request for estimate');
        	$cr->grid->addColumn('button','send_to_client','Send Quote to the client');
        	$cr->grid->addColumn('button','approve','Approve Estimation');
        }
        
        if($_GET['requirements']){
        	$this->js()->univ()->redirect($this->api->url('/manager/quotes/rfq/step2',
        			array('quote_id'=>$_GET['requirements'])))
        			->execute();
        }
        
        if($_GET['details']){
        	$this->js()->univ()->redirect($this->api->url('/manager/quotes/rfq/view',
        			array('quote_id'=>$_GET['details'])))
        			->execute();
        }
        
        if($_GET['estimation']){
        	$quote=$this->add('Model_Quote')->load($_GET['estimation']);
        	$quote->set('status','estimate_needed');
        	$quote->save();
        	$this->api->redirect($this->api->url('/manager/quotes'));
        }
        
        if($_GET['send_to_client']){
        	$this->from=$this->api->getConfig('tmail/from','test@test.com');
        	
        	$quote=$this->add('Model_Quote')->load($_GET['send_to_client']);
        	
        	if ($quote['client_id']>0){
        		$client=$this->add('Model_Client')->load($quote['client_id']);
        		$to=$client['email'];
        		
	        	$mail = $this->add('TMail');
	        	$mail->loadTemplate('send_quote');
	            $mail->setTag('from',$this->from);
	            $mail->setTag('link',$this->api->url('/client/quotes/rfq/estimated',array('quote_id'=>$_GET['send_to_client'])));
	            $mail->send($to);

	            $this->js()->univ()->successMessage('Sent')->execute();
        	}else{
        		$this->js()->univ()->successMessage('The project of this quote has no client!')->execute();
        	}
        }
        
        if($_GET['approve']){
        	$quote=$this->add('Model_Quote')->load($_GET['approve']);
        	$quote->set('status','estimation_approved');
        	$quote->save();
        	$this->api->redirect($this->api->url('/manager/quotes'));
        }
        
        $this->add('P');
        
    }
}
