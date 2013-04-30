<?php

class AuthTokensORM extends ORM {
	public function init() {
		$this->authtokens = $this->appInstance->db->{$this->appInstance->dbname.'.authtokens'};
	}

	public function addToken($token, $secret, $cb = null) {
		return $this->authtokens->insert(['token' => $token, 'secret' => $secret], $cb);
	}
}