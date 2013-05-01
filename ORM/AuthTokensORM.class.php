<?php

class AuthTokensORM extends ORM {
	public function init() {
		$this->authTokens = $this->appInstance->db->{$this->appInstance->dbname.'.authtokens'};
	}

	public function addToken($token, $secret, $cb = null) {
		return $this->authTokens->insert(['token' => $token, 'secret' => $secret], $cb);
	}
}