<?php
class Manager_Projects extends View {
    public $quotes,$acceptance;
    function init(){
        parent::init();

        $this->api->stickyGET('id');
        $this->api->stickyGET($this->name);


        $this->add('H4')->set('1. Quotes requested from clients or managers');
        $this->quotes=$grid=$this->add('Grid');
        $grid->addClass('zebra bordered');
        $m=$grid->setModel('Quote',array('project','user','name'));
        $m->addCondition('status','quotation_requested');
        $grid->addColumn('button','edit');
        $grid->addColumn('button','estimation','Submit for Quotation');
        if($_GET['edit']){
            $this->js()->univ()->redirect($this->api->url('/quotes/'.$_GET['edit']))
                ->execute();
        }
        if($_GET['estimation']){
        	$quote=$this->add('Model_Quote')->notDeleted()->getThisOrganisation()->load($_GET['estimation']);
        	$quote->set('status','estimate_needed');
        	$quote->save();
        	$this->api->redirect($this->api->url('/manager'));
        }
        
        $this->add('H4')->set('2. Quotes not estimated (developer returned)');
        $this->quotes=$grid=$this->add('Grid');
        $grid->addClass('zebra bordered');
        $m=$grid->setModel('Quote',array('project','user','name'));
        $m->addCondition('status','not_estimated');
        $grid->addColumn('button','edit');
        if($_GET['edit']){
            $this->js()->univ()->redirect($this->api->url('/quotes/'.$_GET['edit']))
                ->execute();
        }
        
        $this->add('H4')->set('3. Quotes estimated (developer returned)');
        $this->quotes=$grid=$this->add('Grid');
        $grid->addClass('zebra bordered');
        $m=$grid->setModel('Quote',array('project','user','name','estimated'));
        $m->addCondition('status','estimated');
        $grid->addColumn('button','edit');
        if($_GET['edit']){
            $this->js()->univ()->redirect($this->api->url('/quotes/'.$_GET['edit']))
                ->execute();
        }
        $grid->addColumn('button','send_to_client','Send Quote to the client');
        if($_GET['send_to_client']){
        	$quote=$this->add('Model_Quote')->notDeleted()->getThisOrganisation()->load($_GET['send_to_client']);
        	
        	if ($quote['client_id']>0){
        		$client=$this->add('Model_Client')->notDeleted()->load($quote['client_id']);
                $m->api->mailer->setReceivers(array($client['email']));

                if ($client['email']!=''){
                    $this->api->mailer->sendMail('send_quote',array(
                        'link'=>$m->api->siteURL().$this->api->url('quotes')
//                        'link'=>$m->api->siteURL().$this->api->url('client/quotes/rfq/estimated',array('quote_id'=>$_GET['send_to_client']))
                    ),true);

                    $this->js()->univ()->successMessage('Sent')->execute();
                }else{
                    $this->js()->univ()->successMessage('Error! The client '.$client->get('name').' has no email. Please add email for the client.')->execute();
                }
        	}else{
        		$this->js()->univ()->successMessage('The project of this quote has no client!')->execute();
        	}
        }
        
        $this->add('H4')->set('4. Quotes estimate requested (sent to developers for estimation)');
        $this->quotes=$grid=$this->add('Grid');
        $grid->addClass('zebra bordered');
        $m=$grid->setModel('Quote',array('project','user','name'));
        $m->addCondition('status','estimate_needed');
        $grid->addColumn('button','edit');
        if($_GET['edit']){
            $this->js()->univ()->redirect($this->api->url('/quotes/'.$_GET['edit']))
                ->execute();
        }
        
        //if($_GET[$this->name]=='supplyquote')return $this->supplyQuote();
/*
        $this->add('H4')->set('5. Acceptance. Check on client');
        $this->acceptance=$grid=$this->add('Grid');
        $grid->setModel('Budget_Completed',array('name','priority','state','bugs','tasks'));
        */
    }
    function supplyQuote(){

        $v=$this->add('View','supplyquote');
        $_GET['cut_object']=$v->name;

        $m=$this->add('Model_Budget')->load($_GET['id']);

        $form=$v->add('Form');
        $form->setModel($m,array('amount','state'));
        $form->getElement('amount')->js('change',
                $form->getElement('state')->js()->val('quotereview')
                );
        $form->getElement('amount')->js(true)->univ()->autoChange(0);

        if($form->isSubmitted()){
            $form->update();

            $form->js()->univ()->location($this->api->getDestinationURL(null,array(
                            $this->name=>null,'id'=>null)))->execute();
        }
    }
}
