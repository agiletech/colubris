<?php
class Model_User extends Model_BaseTable {
	public $table='user';
    function init(){
        parent::init(); //$this->debug();
		if (@$this->app->auth) $this->app->auth->addEncryptionHook($this);

		// fields
		$this->addField('email')->mandatory('required');
		$this->addField('name');
		$this->addField('password')->display(array('form'=>'password'))->mandatory('required');
		$this->addField('is_admin')->type('boolean');
		$this->addField('is_manager')->type('boolean');
		$this->addField('is_financial')->type('boolean')->caption('Is Financial Manager');
		$this->addField('is_developer')->type('boolean')->caption('Is Team Member');
		$this->addField('is_sales')->type('boolean')->caption('Is Sales Manager');
		$this->addField('hash');
		$this->addField('mail_task_changes')->type('boolean')->caption('Send when task changed');
		$this->addField('is_deleted')->type('boolean')->defaultValue('0');
        $this->addField('avatar_id');
		$this->hasOne('User','deleted_id');

		$this->addField('is_system')->defaultValue('0')->type('boolean');
		$this->hasOne('Client');

		$this->addField('chash');

		$this->hasOne('Organisation')->mandatory('required');

        // For logging through APIs
        $this->addField('lhash');
        $this->addField('lhash_exp');

        // expressions
		$this->addExpression('is_client')->datatype('boolean')->set(function($m,$q){
			return $q->dsql()
				->expr('if(client_id is null,false,true)');
		});
        $this->addExpression('avatar')->set(function($m,$q){
            return $q->dsql()
                ->table('filestore_file')
                ->field('filename')
                ->where('filestore_file.id',$q->getField('avatar_id'))
                ;
        });
        $this->addExpression('avatar_thumb')->set(function($m,$q){
            return $q->dsql()
                ->table('filestore_file')
                ->table('filestore_image')
                ->field('filename')
                ->where('filestore_image.original_file_id',$q->getField('avatar_id'))
                ->where('filestore_image.thumb_file_id=filestore_file.id')
                ;
        });

		// order
		$this->setOrder('name');


		$this->addHooks();
    }

	// ------------------------------------------------------------------------------
	//
	//            HOOKS :: BEGIN
	//
	// ------------------------------------------------------------------------------

	function addHooks() {
		$this->addHook('beforeInsert',function($m){
			if($m->getBy('email',$m['email'])) throw $m
				->exception('User with this email already exists','ValidityCheck')
				->setField('email');
		});

		$this->addHook('beforeModify',function($m){
			if($m->dirty['email']) throw $m
				->exception('Do not change email for existing user','ValidityCheck')
				->setField('email');
		});

        $this->addHook('beforeDelete', function($m){
            if( !isset($this->app->is_test_app)) $m['deleted_id']=$m->app->currentUser()->get('id');
        });
	}

	// HOOKS :: END -----------------------------------------------------------


	function getActive(){
		$this->addCondition('is_system',false);
		$this->addCondition('is_deleted',false);
		return $this;
	}
	function deleted() {
		//$this->addCondition('organisation_id',$this->app->currentUser()->get('organisation_id'));
		$this->addCondition('is_deleted',true);
		return $this;
	}
	function notDeleted() {
		$this->addCondition('is_deleted',false);
		return $this;
	}
	function getAdmins(){
		$this->addCondition('is_admin',true);
		return $this;
	}
	function getDevelopers(){
		$this->addCondition('is_developer',1);
		return $this;
	}
	function getUsersOfOrganisation(){
		$this->addCondition('organisation_id',$this->app->currentUser()->get('organisation_id'));
		return $this;
	}
	function getSystemUsers(){
		$this->addCondition('is_system',true);
		$this->addCondition('is_deleted',false);
		return $this;
	}

	function me(){
		$this->addCondition('id',$this->app->auth->get('id'));
		return $this;
	}
	function beforeInsert(&$d){
		$d['hash']=md5(uniqid());
		return parent::beforeInsert($d);
	}
	function resetPassword(){
		throw $this->exception('Function resetPassword is not implemented yet');
	}

    // For APIs
    function setLHash(){
        $this->set('lhash',md5(time().$this->get('password')));
        $this->set('lhash_exp',date('Y-m-d G:i:s', time() + $this->app->getConfig('api_login_expire_minutes', 60) * 60));
        $this->save();
        return array('lhash' => $this->get('lhash'), 'lhash_exp' => $this->get('lhash_exp'));
    }
    function prolongLHash(){
        $this->set('lhash_exp',date('Y-m-d G:i:s', time() + $this->app->getConfig('api_login_expire_minutes', 60) * 60));
        $this->save();
        return array('lhash' => $this->get('lhash'), 'lhash_exp' => $this->get('lhash_exp'));
    }

    function getByLHash($lhash) {
        return $this->tryLoadBy('lhash',$lhash);
    }

    function checkUserByLHash($lhash){
        $this->addCondition('lhash_exp','>',date('Y-m-d G:i:s', time()));
        $this->tryLoadBy('lhash',$lhash);
        if($this->loaded()) return $this; else return false;
    }

    function prepareForSelect(Model_User $u){
        $r = $this->add('Model_User_Right');

        $fields = ['id'];

        // to make user be able to see itself
        if ($this['id'] == $u['id']) {
            $fields = array('id','name','password','mail_task_changes','avatar_id','avatar','avatar_thumb');
        }

        if($r->canSeeUsers($u['id'])){
            $fields = array('id','email','name','password','is_admin','is_manager','is_financial','is_developer','is_sales','hash','mail_task_changes','is_deleted','deleted_id','avatar_id','deleted','is_system','client_id','client','chash','organisation_id','organisation','lhash','lhash_exp','is_client','avatar','avatar_thumb');
        }

        $this->setActualFields($fields);
        return $this;
    }
    function prepareForInsert(Model_User $u){
        $r = $this->add('Model_User_Right');

        $fields = ['id'];

        if($r->canManageUsers($u['id'])){
            $fields = array('id','email','name','password','is_admin','is_manager','is_financial','is_developer','is_sales','hash','mail_task_changes','is_deleted','deleted_id','avatar_id','deleted','is_system','client_id','client','chash','organisation_id','organisation','lhash','lhash_exp','is_client','avatar','avatar_thumb');
        } else {
            throw $this->exception('This User cannon add record','API_CannotAdd');
        }

        foreach ($this->getActualFields() as $f){
            $fo = $this->hasElement($f);
            if(in_array($f, $fields)){
                if($fo) $fo->editable = true;
            } else {
                if($fo) $fo->editable = false;
            }
        }
        return $this;
    }
    function prepareForUpdate(Model_User $u){
        $r = $this->add('Model_User_Right');

        $fields = ['id'];

        if ($this['id'] == $u['id']) {
            $fields = array('id','name','password','mail_task_changes','avatar_id','avatar','avatar_thumb');
        }

        if($r->canManageUsers($u['id'])){
            $fields = array('id','email','name','password','is_admin','is_manager','is_financial','is_developer','is_sales','hash','mail_task_changes','is_deleted','deleted_id','avatar_id','deleted','is_system','client_id','client','chash','organisation_id','organisation','lhash','lhash_exp','is_client','avatar','avatar_thumb');
        }
        // to make user be able to edit itself
        else if ($this['id'] == $u['id']) {
            $fields = array('id','name','password','mail_task_changes','avatar_id','avatar','avatar_thumb');
        } else {
            throw $this->exception('This User cannon edit record','API_CannotAdd');
        }

        foreach ($this->getActualFields() as $f){
            $fo = $this->hasElement($f);
            if(in_array($f, $fields)){
                if($fo) $fo->editable = true;
            } else {
                if($fo) $fo->editable = false;
            }
        }
        return $this;
    }
    function prepareForDelete(Model_User $u){
        $r = $this->add('Model_User_Right');

        if($r->canManageUsers($u['id'])) return $this;

        throw $this->exception('This user has no permissions for deleting','API_CannotDelete');
    }

}
