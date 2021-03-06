<?php
namespace go\core\jmap;

use go\core\auth\State as AbstractState;
use go\core\auth\model\Token;
use go\core\auth\model\User;
use go\core\jmap\Request;

class State extends AbstractState {
	
	private function getFromHeader() {
		
		$auth = Request::get()->getHeader('Authorization');
		if(!$auth) {
			return false;
		}
		preg_match('/Bearer (.*)/', $auth, $matches);
    if(!isset($matches[1])){
      return false;
    }
		
		return $matches[1];
	}
	
	/**
	 *
	 * @var Token 
	 */
	private $token;
	
	/**
	 * Get the authorization token by reading the request header "Authorization"
	 * 
	 * @return boolean|Token 
	 */
	public function getToken() {

		
		if(!isset($this->token)) {
						
			$tokenStr = $this->getFromHeader();
//			if(!$tokenStr && GO()->getRequest()->getMethod() == 'GET' && isset($_COOKIE['accessToken'])) {
//				$tokenStr = $_COOKIE['accessToken'];
//			}
			

			if(!$tokenStr) {
				return false;
			}
		
			$this->token = Token::find()->where(['accessToken' => $tokenStr])->single();

			if(!$this->token) {
				return false;
			}		

			if($this->token->isExpired()) {				
				$this->token->delete();				
				$this->token = false;
			}
		}
		
		return $this->token;
	}
  
  public function setToken(Token $token) {
    $this->token = $token;
  }
	
	public function isAuthenticated() {
		return $this->getToken() !== false;
	}
	
	/**
	 * 
	 * @return User
	 */
	public function getUser() {
		return $this->getToken() ? $this->getToken()->getUser() : null;
	}

}
