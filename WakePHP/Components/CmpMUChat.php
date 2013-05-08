<?php
namespace WakePHP\Components;

/*
DRAFT:

 db.muchatsessions.ensureIndex({id:1},{unique: true});
 db.muchatsessions.ensureIndex({tags:1});
 db.muchatignore.ensureIndex({user:1,blocked:1},{unique: true});
 db.createCollection("muchatevents", {capped:true, size:100000})
*/

use PHPDaemon\Core\AppInstance;
use PHPDaemon\Core\ClassFinder;
use PHPDaemon\Core\Daemon;
use WakePHP\ORM\MUChatORM;

class CmpMUChat extends AppInstance {

	public $sessions = array();

	public $db;

	public $dbname;

	public $tags;

	public $minMsgInterval;

	public $tagsLimit = 1;

	public $idleTimeout = 300;

	public $ipcId;

	public $WS;

	public $LockClient;

	public $statTags;

	public $ORM;

	protected function getConfigDefaults() {
		return array(
			'dbname'        => 'WakePHP',
			'adminpassword' => 'lolz',
			'enable'        => true,
		);
	}

	public function __construct($req) {
		$this->req         = $req;
		$this->appInstance = $req->appInstance;
		$this->dbname      =& $this->appInstance->dbname;
		Daemon::log(__CLASS__ . ' up.');
		$this->db             = $this->appInstance->db;
		$this->ORM            = new MUChatORM($this);
		$this->tags           = array();
		$this->minMsgInterval = 1;

		$this->cache  = \PHPDaemon\Clients\Memcache\Pool::getInstance();
		$this->ipcId  = sprintf('%x', crc32(Daemon::$process->pid . '-' . microtime(true)));
		$my_class     = ClassFinder::getClassBasename($this);
		$this->config = isset($this->appInstance->config->{$my_class}) ? $this->appInstance->config->{$my_class} : null;
		$defaults     = $this->getConfigDefaults();
		if ($defaults) {
			$this->processDefaultConfig($defaults);
		}
		$this->dbname = $this->config->dbname->value;
		$this->init();
		$this->onReady();

	}

	public function getTag($name) {

		if (isset($this->tags[$name])) {
			return $this->tags[$name];
		}
		return $this->tags[$name] = new MUChat\Tag($name, $this);

	}

	public function kickUsers($users, $tags = '', $reason = '') {

		if (is_string($users)) {
			$users = explode(',', trim($users));
		}
		if (!is_array($users) || sizeof($users) === 0) {
			return false;
		}
		$tags = trim($tags);
		$this->broadcastEvent(array(
								  'type'   => 'kickUsers',
								  'users'  => $users,
								  'tags'   => ($tags !== '' ? explode(',', $tags) : array('%all')),
								  'reason' => $reason,
							  ));
		return true;

	}

	public function compareMask($username, $masks = array()) {

		foreach ($masks as $mask) {
			if (fnmatch($mask, $username, FNM_CASEFOLD)) {
				return true;
			}
		}
		return false;

	}

	public function forceChangeNick($name, $newname) {

		$name = trim($name);
		if ($name === '') {
			return false;
		}
		$newname = trim($newname);
		if ($newname === '') {
			return false;
		}
		$this->broadcastEvent(array(
								  'type'     => 'forceChangeNick',
								  'username' => $name,
								  'changeto' => $newname,
								  'tags'     => '%all',
							  ));
		return true;
	}

	public function validateUsername($s) {
		return preg_match('~^(?!@)[A-Za-z\-_!0-9\.\wА-Яа-я]+$~u', $s);
	}

	public function broadcastEvent($doc) {

		if (!isset($doc['ts'])) {
			$doc['ts'] = microtime(true);
		}
		if (!isset($doc['tags'])) {
			$doc['tags'] = array();
		}
		$this->db->{$this->config->dbname->value . '.muchatevents'}->insert($doc);

	}

	public function onHandshake($client) {

		return $this->sessions[$client->connId] = new MUChat\Session($client, $this);

	}

	public function onReady() {

		if ($this->config->enable->value) {
			$this->WS = \PHPDaemon\Servers\WebSocket\Pool::getInstance();
			if ($this->WS) {
				$this->WS->addRoute('MUChat', array($this, 'onHandshake'));
			}
			$appInstance = $this;

			$req = new Muchat\MsgQueueRequest($this, $this);

			$req = new Muchat\IdleCheck($appInstance, $appInstance);

			$this->LockClient = \PHPDaemon\Clients\Lock\Pool::getInstance();

			$this->LockClient->job(__CLASS__, true, function ($jobname) use ($appInstance) {
				$appInstance->pushRequest(new Muchat\UpdateStat($appInstance, $appInstance));
			});
		}
	}
}
