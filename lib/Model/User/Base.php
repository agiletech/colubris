<?php
class Model_User_Base extends Model_BaseTable {
    public $table='user';
    function init(){
        parent::init(); //$this->debug();
        if (@$this->api->auth) $this->api->auth->addEncryptionHook($this);

        // fields
        $this->addField('email')->mandatory('required');
        $this->addField('name');
        $this->addField('password')->display(array('form'=>'password'))->mandatory('required');
        $this->addField('client_id')->refModel('Model_Client');
        $this->addField('is_admin')->type('boolean');
        $this->addField('is_manager')->type('boolean');
        $this->addField('is_developer')->type('boolean');
        $this->addField('is_timereport')->type('boolean')->caption('Is Time Reports');
        $this->addField('hash');
        $this->addField('mail_task_changes')->type('boolean')->caption('Send when task changed');
        $this->addField('is_deleted')->type('boolean')->defaultValue('0');
        $this->addField('is_system')->defaultValue('0')->type('boolean');

        $this->hasOne('Organisation')->mandatory('required');

        // expressions
        $this->addExpression('is_client')->datatype('boolean')->set(function($m,$q){
            return $q->dsql()
                    ->expr('if(client_id is null,false,true)');
        });

        // order
        $this->setOrder('name');


        // hooks
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
    }
    function me(){
        $this->addCondition('id',$this->api->auth->get('id'));
        return $this;
    }
    function beforeInsert(&$d){
        $d['hash']=md5(uniqid());
        return parent::beforeInsert($d);
    }
    function resetPassword(){
        throw $this->exception('Function resetPassword is not implemented yet');
    }



    /* *********************************
     *
     *             GET ROLES
     *
     */
    function isAdmin() {
        return ($this['is_admin']?true:false);
    }
    function isDeveloper() {
        return ($this['is_developer']?true:false);
    }
    function isClient() {
        return ($this['is_client']?true:false);
    }
    function isManager() {
        return ($this['is_manager']?true:false);
    }
    function isSystem() {
        return ($this['is_system']?true:false);
    }




    /* **********************************
     *
     *          CURRENT USER ROLE
     *
     */
    function isCurrentUserAdmin() {
        return ($this->api->getCurrentUserRole() == 'admin');
    }
    function isCurrentUserManager() {
        return ($this->api->getCurrentUserRole() == 'manager');
    }
    function isCurrentUserDev() {
        return ($this->api->getCurrentUserRole() == 'developer');
    }
    function isCurrentUserClient() {
        return ($this->api->getCurrentUserRole() == 'client');
    }



    /* **********************************
     *
     *      PROJECT ACCESS RULES
     *
     */
    function canSeeProject($project) {
    }
    function canCreateProject() {
        return $this->checkRoleSimpleRights(array(false,true,false,true));
    }
    function canDeleteProject() {
        return $this->checkRoleSimpleRights(array(false,true,false,false));
    }
    function canEditProject() {
        return $this->checkRoleSimpleRights(array(false,true,false,false));
    }
    function canSeeProjectParticipantes() {
        return $this->checkRoleSimpleRights(array(false,true,false,false));
    }
    function canSeeProjectTasks() {
        return $this->checkRoleSimpleRights(array(true,true,true,true));
    }






    /* **********************************
     *
     *           USER RIGHTS
     *
     */

    function canSendRequestForQuotation() {
        return $this->checkRoleSimpleRights(array(false,true,false,true));
    }
    function canUserMenageClients() {
        return $this->checkRoleSimpleRights(array(false,true,false,false));
    }



    function canSeeQuotesList() {
        return $this->checkRoleSimpleRights(array(false,true,true,true));
    }
    function canSeeUserList() {
        return $this->checkRoleSimpleRights(array(true,false,false,false));
    }
    function canSeeDevList() {
        return $this->checkRoleSimpleRights(array(true,false,false,false));
    }
    function canSeeDeleted() {
        return $this->checkRoleSimpleRights(array(false,true,false,false));
    }
    function canSeeReportList() {
        return $this->checkRoleSimpleRights(array(false,true,true,true));
    }
    function canSeeProjectList() {
        return $this->checkRoleSimpleRights(array(false,true,true,true));
    }
    function canSeeTaskList() {
        return $this->checkRoleSimpleRights(array(false,true,true,true));
    }
    function canSeeDashboard() {
        return $this->checkRoleSimpleRights(array(false,true,true,true));
    }


    function checkRoleSimpleRights($rights) {
        if ($this->isCurrentUserAdmin()) {
            return $rights[0];
        } else if ($this->isCurrentUserManager()) {
            return $rights[1];
        } else if ($this->isCurrentUserDev()) {
            return $rights[2];
        } else if ($this->isCurrentUserClient()) {
            return $rights[3];
        } else {
            return false;
            //throw $this->exception('Wrong role');
        }
    }
}
