<?php
class Grid_Tasks extends Grid_Advanced {
    function init() {
        parent::init();
        $this->addClass('zebra bordered');
    }
    function setModel($model, $actual_fields = UNDEFINED) {
        parent::setModel($model, $actual_fields);
        if(!$this->api->currentUser()->isCurrentUserClient()){
            $this->removeColumn('estimate');
        }
    }
    function formatRow() {
        parent::formatRow();
        $this->current_row_html['name'] =
            '<div class="name"><a href="'.$this->api->url('task',array('task_id'=>$this->current_row['id'])).'">'.$this->current_row['name'].'</a></div>'
        ;
        if ($this->current_row['spent_time'] == '') {
            $this->current_row['spent_time'] = '0.00';
        }
        $this->current_row_html['spent_time'] =
                '<div style="white-space:wrap;" class="spent_time">'.$this->current_row['spent_time'].'</div>'.
                '<div style="white-space:wrap;" class="estimate">Est: ('.$this->current_row['estimate'].')</div>'
        ;
        $this->current_row_html['requester'] = '<div class="requester">'.$this->current_row['requester'].'</div>';
        $this->current_row_html['assigned'] = '<div class="assigned">'.$this->current_row['assigned'].'</div>';
        $this->current_row_html['updated_dts'] = '<div class="updated">'.$this->current_row['updated_dts'].'</div>';
    }
    function format_status($field){
    	switch($this->current_row[$field]){
    		case 'started':
    			$this->row_t->setHTML('painted','started');
    			break;
    		case 'finished':
    			$this->row_t->setHTML('painted','finished');
    			break;
            case 'tested':
                $this->row_t->setHTML('painted','tested');
                break;
   			case 'rejected':
    			$this->row_t->setHTML('painted','rejected');
   				break;
			case 'accepted':
    			$this->row_t->setHTML('painted','accepted');
				break;
    						
    		default:
    			$this->row_t->setHTML('painted','');
    			break;
    	}
    }
    function defaultTemplate() {
    	return array('grid/colored');
    }
    
    function precacheTemplate() {
    	$this->row_t->trySetHTML('painted', '<?$painted?>');
    	parent::precacheTemplate();
    }
}