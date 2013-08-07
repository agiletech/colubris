<?
class Model_Attach extends Model_Table {
	public $table='attach';
	function init(){
		parent::init();
		$this->hasOne('Task');
		$this->addField('description')->type('text');
		$this->add('filestore\Field_File','file_id')->setModel('filestore/File')->mandatory(true);

        $this->addField('created_dts');
        $this->addField('updated_dts');
        
        $this->addHook('beforeInsert', function($m,$q){
        	$q->set('created_dts', $q->expr('now()'));
        });
        
       	$this->addHook('beforeSave', function($m){
       		$m['updated_dts']=date('Y-m-d G:i:s', time());
       	
       		$task=$m->add('Model_Task')->load($m->get('task_id'));
       		$to='';
        	if ($task->get('requester_id')>0){
        		$u=$m->add('Model_User')->load($task->get('requester_id'));
        		if ($u['email']!='') $to=$u['email'];
        	}
        	if ($task->get('assigned_id')>0){
        		$u=$m->add('Model_User')->load($task->get('assigned_id'));
        		if ($u['email']!=''){
        			if ($to=='') $to=$u['email']; else $to.=', '.$u['email'];
        		}
        	}
        	if ($to!=''){
        		$m->api->mailer->sendMail($to,'task_attachment_changed',array(
        				'link'=>$m->api->url('/manager/tasks'),
        				'task_name'=>$task->get('name'),
        				));
        	}
       	});
        
       	$this->addHook('beforeDelete', function($m){
       		$task=$m->add('Model_Task')->load($m->get('task_id'));
       		$to='';
        	if ($task->get('requester_id')>0){
        		$u=$m->add('Model_User')->load($task->get('requester_id'));
        		if ($u['email']!='') $to=$u['email'];
        	}
        	if ($task->get('assigned_id')>0){
        		$u=$m->add('Model_User')->load($task->get('assigned_id'));
        		if ($u['email']!=''){
        			if ($to=='') $to=$u['email']; else $to.=', '.$u['email'];
        		}
        	}
        	if ($to!=''){
        		$m->api->mailer->sendMail($to,'task_attachment_deleted',array(
        				'link'=>$m->api->url('/manager/tasks'),
        				'task_name'=>$task->get('name'),
        				));
        	}
       	});
        
       	$this->setOrder('updated_dts',true);
	}
}
