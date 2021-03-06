<?php

class page_deleted extends Page {
    function init() {
        parent::init();

        // Checking client's read permission to this quote and redirect to denied if required
        if( !$this->app->model_user_rights->canSeeDeleted() ){
            throw $this->exception('You cannot see this page','Exception_Denied');
        }

        $this->title = 'Deleted';
        $this->add('x_bread_crumb/View_BC',array(
            'routes' => array(
                0 => array(
                    'name' => 'Home',
                ),
                1 => array(
                    'name' => 'Deleted',
                    'url' => 'deleted',
                ),
            )
        ),'bread_crumb');
    }

    function page_index() {
    	$this->add('View_DeletedTabs');
    }

    function page_projects(){
        $m=$this->add('Model_Project')->deleted();

        $cr=$this->add('CRUD',array(
                'grid_class'=>'Grid',
                'allow_add'=>false,
                'allow_edit'=>false,
                'allow_del'=>false)
        );
        $cr->setModel($m,
            array('name','descr','client','demo_url','prod_url','deleted')
        );

        if($cr->grid){
            $cr->grid->addClass('zebra bordered');
            $cr->grid->addPaginator();

            $cr->grid->addColumn('button','restore');
            if ($_GET['restore']) {
                $m=$this->add('Model_Project')->getThisOrganisation();
                $o=$m->load($_GET['restore']);
                $o->set('is_deleted',false);
                $o->save();
                $cr->grid->js('reload')->reload()->execute();
            }
        }
    }

    function page_quotes(){
        $m=$this->add('Model_Quote')->deleted()->getThisOrganisation();

        $cr=$this->add('CRUD',array(
                'grid_class'=>'Grid',
                'allow_add'=>false,
                'allow_edit'=>false,
                'allow_del'=>false)
        );
        $cr->setModel($m,
            array('project','user','name','estimated','estimpay','spent_time','rate','currency','durdead','status','deleted')
        );

        if($cr->grid){
            $cr->grid->addClass('zebra bordered');
            $cr->grid->addPaginator();

            $cr->grid->addColumn('button','restore');
            if ($_GET['restore']) {
                $m=$this->add('Model_Quote')->getThisOrganisation();
                $o=$m->load($_GET['restore']);
                $o->set('is_deleted',false);
                $o->save();
                $cr->grid->js('reload')->reload()->execute();
            }
        }
    }

    function page_tasks(){
        $m = $this->add('Model_Task')->deleted();

        $cr=$this->add('CRUD',array(
                'grid_class'=>'Grid',
                'allow_add'=>false,
                'allow_edit'=>false,
                'allow_del'=>false)
        );
        $cr->setModel($m,
            array('project','name','priority','type','status','estimate','spent_time','requester','assigned','deleted')
        );

        if($cr->grid){
            $cr->grid->addClass('zebra bordered');
            $cr->grid->addPaginator();

            $cr->grid->addColumn('button','restore');
            if ($_GET['restore']) {
                $m = $this->add('Model_Task')->Base();
                $o = $m->load($_GET['restore']);
                $o->set('is_deleted',false);
                $o->save();
                $cr->grid->js('reload')->reload()->execute();
            }
        }
    }

    function page_users(){
        $m=$this->add('Model_User')->deleted();

        $cr=$this->add('CRUD',array(
                'grid_class'=>'Grid',
                'allow_add'=>false,
                'allow_edit'=>false,
                'allow_del'=>false)
        );
        $cr->setModel($m,
            array('email','name','client','is_admin','is_manager','is_developer','is_client','deleted')
        );

        if($cr->grid){
            $cr->grid->addClass('zebra bordered');
            $cr->grid->addPaginator();

            $cr->grid->addColumn('button','restore');
            if ($_GET['restore']) {
                $m=$this->add('Model_User');
                $o=$m->load($_GET['restore']);
                $o->set('is_deleted',false);
                $o->save();
                $cr->grid->js('reload')->reload()->execute();
            }
        }
    }

    function page_clients(){
        $m=$this->add('Model_Client')->deleted();

        $cr=$this->add('CRUD',array(
                'grid_class'=>'Grid',
                'allow_add'=>false,
                'allow_edit'=>false,
                'allow_del'=>false)
        );
        $cr->setModel($m
        );

        if($cr->grid){
            $cr->grid->addClass('zebra bordered');
            $cr->grid->addPaginator();

            $cr->grid->addColumn('button','restore');
            if ($_GET['restore']) {
                $m=$this->add('Model_Client')->deleted();
                $o=$m->load($_GET['restore']);
                $o->set('is_deleted',false);
                $o->save();
                $cr->grid->js('reload')->reload()->execute();
            }
        }
    }
    function defaultTemplate() {
        return array('page/page');
    }
}
