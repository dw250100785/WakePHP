<?php

/**
 * OutgoingMailORM
 */
class OutgoingMailORM extends ORM {

	public $outgoingmail;
	public function init() {
		$this->outgoingmail = $this->appInstance->db->{$this->appInstance->dbname . '.outgoingmail'};
	}
	public function mailTemplate($block, $email, $args) {
		$appInstance = $this->appInstance;
		$args['domain'] = $appInstance->config->domain->value;
		$appInstance->renderBlock('mailAccountConfirmation', $args, function ($result) use ($email, $appInstance) {

			$result = str_replace("\r", '', $result);
			$e = explode("\n\n", $result, 2);
			$e[0] = str_replace("\n", "\r\n", $e[0]);
			$appInstance->outgoingmail->mail($email, null, $e[1], $e[0]);
		});
	}
	public function mail() {
		
		$this->outgoingmail->insert(array('ts' => microtime(), 'status' => 'vacant', 'args' => func_get_args()));
				
	}
}