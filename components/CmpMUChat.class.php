<?php
/*
DRAFT:

 db.muchatsessions.ensureIndex({id:1},{unique: true});
 db.muchatsessions.ensureIndex({tags:1});
 db.muchatignore.ensureIndex({user:1,blocked:1},{unique: true});
 db.createCollection("muchatevents", {capped:true, size:100000})
*/

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
			'dbname' => 'WakePHP',
			'adminpassword' => 'lolz',
			'enable' => true,
		);
	}
	public function __construct($req) {
		$this->req = $req;
		$this->appInstance = $req->appInstance;
		$this->dbname =& $this->appInstance->dbname;
		Daemon::log(__CLASS__ . ' up.');
		$this->db = $this->appInstance->db;
		$this->ORM = new MUChatORM($this);
		$this->tags = array();
		$this->minMsgInterval = 1;

		$this->cache = MemcacheClient::getInstance();
		$this->ipcId = sprintf('%x', crc32(Daemon::$process->pid .'-' . microtime(true)));
		
		$this->config = isset($this->appInstance->config->{get_class($this)}) ? $this->appInstance->config->{get_class($this)} : null;
		$defaults = $this->getConfigDefaults();
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
		return $this->tags[$name] = new MUChatTag($name, $this);
		
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
			'type' => 'kickUsers',
			'users' => $users,
			'tags' => ($tags !== '' ? explode(',', $tags) : array('%all')),
			'reason' => $reason,
		));
		return true;
		
	}
	
	public function compareMask($username,$masks = array()) {
	
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
			'type' => 'forceChangeNick',
			'username' => $name,
			'changeto' => $newname,
			'tags' => '%all',
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
	
		return $this->sessions[$client->connId] = new MUChatSession($client, $this);
		
	}
	public function onReady() {
	
		if ($this->config->enable->value) {
			$this->WS = WebSocketServer::getInstance();
			if ($this->WS) {
				$this->WS->addRoute('MUChat', array($this, 'onHandshake'));
			}
			$appInstance = $this;
			
			$req = new MUChat_MsgQueueRequest($this, $this);
			
			$req = new MUChat_IdleCheck($appInstance, $appInstance);
			
			$this->LockClient = LockClient::getInstance();
			
			$this->LockClient->job(__CLASS__,true,function($jobname) use ($appInstance) {
				$appInstance->pushRequest(new MUChat_UpdateStat($appInstance, $appInstance));
			});
		}
	}
}

class MUChatAntifloodPlugin {
	public function onMessage() {
	}
}

class MUChatTag {
	public $appInstance;
	public $sessions = array();
	public $tag;
	public $cursor;
	public $counter = 0;
	public function __construct($tag,	$appInstance) {
		$this->tag = $tag;
		$this->appInstance = $appInstance;
	}
	public function touch() {
		if (!$this->cursor || $this->cursor->destroyed) {
			$tag = $this;
			$this->appInstance->db->{$this->appInstance->config->dbname->value . '.muchatevents'}->find(function($cursor) use ($tag) {
				$tag->cursor = $cursor;
				foreach ($cursor->items as $k => &$item) {
					if ($item['type'] === 'kickUsers') {
					foreach ($tag->sessions as $id => $v) {
						$sess = $tag->appInstance->sessions[$id];
						if (($sess->username !== null) && ($tag->appInstance->compareMask($sess->username, $item['users']))) {
							$sess->removeTags(array($tag->tag), true);
							$sess->sysMsg('You were kicked from #'.$tag->tag . '.'.($item['reason'] !== '' ? ' Reason: '.$item['reason'] : ''));
							$sess->send(array('type' => 'youWereKicked', 'reason' => $item['reason']));
							$tag->appInstance->broadcastEvent(array(
								'type' => 'msg',
								'mtype' => 'system',
								'text' => ' Kicked: '.$sess->username . ($item['reason'] !== '' ? ', reason: '.$item['reason'] : ''),
								'color' => 'green',
								'tags' => $tag->tag,
							));
						}
					}
				}
				elseif ($item['type'] === 'forceChangeNick') {
					foreach ($tag->sessions as $id => $v) {
						$sess = $tag->appInstance->sessions[$id];
						if (($sess->username !== null) && ($sess->username === $item['username'])) {
							$sess->setUsername($item['changeto'], true);
						}
					}
				}
				else {
					$item['_id'] = (string) $item['_id'];
					if (isset($item['sid'])) {
						$item['sid'] = (string) $item['sid'];
					}
					$packet = MUChatSession::serialize($item);
					foreach ($tag->sessions as $id => $v) {
						$s = $tag->appInstance->sessions[$id];
						if (is_string($item['tags'])) {
							$item['tags'] = array($item['tags']);
						}
						if (in_array('%private', $item['tags'])) {
							if (!isset($item['to'])) {
								continue;
							}
							if (!in_array($s->username,$item['to']) && ($s->username != $item['from'])) {
								continue;
							}
						}
						if ($s->putMsgId($item['_id'])) {
							$s->client->sendFrame($packet);
						}
					}
				}
				unset($cursor->items[$k]);
			}
		}, array(
			'tailable' => true,
			'sort' => array('$natural' => 1),
			'where' => array(
				'ts' => array(
					'$gt' => microtime(true)
				),
				'tags' => array(
					'$in' => binarySubstr($this->tag,0,1) == '%'?array($this->tag):array($this->tag,'%all')
				)
			)));
		}
		elseif (!$this->cursor->session->busy) {
			try {
				$this->cursor->getMore();
			}
			catch (MongoClientSessionFinished $e) {
				$this->cursor = false;
			}
		}
	}
}

class MUChatSession { 
	
	public $client;
	
	public $username;
	
	public $tags = array();
	
	public $sid;
	
	public $lastMsgTS;
	
	public $su = false;
 
	public $lastMsgIDs = array();
	
	public $statusmsg;
	
	public function __construct($client, $appInstance) {
	
		$this->client = $client;
		$this->lastMsgIDs = new SplStack();
		$this->appInstance = $appInstance;
		$this->sid = new MongoId();
		$this->updateSession(array(
			'atime' => microtime(true),
			'ltime' => microtime(true),
			'mtime' => microtime(true),
			'worker' => $this->appInstance->ipcId
		));
	}
	
	public function gracefulShutdown() {
		return true;
	}
	
	public function putMsgId($s) {
		for ($i = 0, $c = count($this->lastMsgIDs); $i < $c; ++$i) {
			if ($this->lastMsgIDs[$i] === $s) {
				return false;
			}
		}
		$this->lastMsgIDs[] = $s;
		if ($c >= 4) {
			$this->lastMsgIDs->shift();
		}
		return true;
	}
	public function onFinish() {
		$a = array(
			'type' => 'msg',
			'mtype' => 'system',
			'text' => 'Disconnected user: ' . $this->username,
			'color' => 'green',
			'tags' => array_values(array_diff($this->tags, array('%private'))),
		);
		$this->setTags(array());
		$this->broadcastEvent($a);
		$this->appInstance->db->{$this->appInstance->config->dbname->value . '.muchatsessions'}->remove(array('id' => $this->sid));
		unset($this->appInstance->sessions[$this->client->connId]);
	}
	public function onAddedTags($tags, $silence = false) {
		
		foreach ($tags as $tag) {
			++$this->appInstance->getTag($tag)->counter;
			$this->appInstance->getTag($tag)->sessions[$this->client->connId] = true;
		}
		$tags = array_values(array_diff($tags, array('%private')));
		if ($this->username !== null) {
			$this->broadcastEvent(array(
				'type' => 'joinsUser',
				'sid' => (string) $this->sid,
				'username' => $this->username,
				'tags' => $tags,
				'statusmsg' => $this->statusmsg,
			));
			if (!$silence) {
				$this->broadcastEvent(array(
					'type' => 'msg',
					'mtype' => 'system',
					'text' => 'Joins: ' . $this->username,
					'color' => 'green',
					'tags' => $tags,
				));
			}
		}
	}
	
	public function onRemovedTags($tags, $silence = false) {
		$tags = array_values(array_diff($tags, array('%private')));
		foreach ($tags as $tag) {
			--$this->appInstance->getTag($tag)->counter;
			unset($this->appInstance->tags[$tag]->sessions[$this->client->connId]);
		}
		if ($this->username !== null) {
			$this->broadcastEvent(array(
				'type' => 'partsUser',
				'sid' => (string) $this->sid,
				'username' => $this->username,
				'tags' => $tags,
			));
			if (!$silence) {
				$this->broadcastEvent(array(
					'type' => 'msg',
					'mtype' => 'system',
					'text' => 'Parts: '.$this->username,
					'color' => 'green',
					'tags' => $tags,
				));
			}
		}
	}
	
	public function addTags($tags) {
		$this->setTags(array_unique(array_merge($this->tags,$tags)));
	}
	public function removeTags($tags, $silence = false) {
		$this->setTags(array_diff($this->tags,$tags),$silence);
	}
	public function setTags($tags, $silence = false) {
		$i = 0;
		foreach ($tags as $k => $t) {
			if (binarySubstr($t, 0, 1) == '%') {
				continue;
			}
		if ($i > $this->appInstance->tagsLimit) {
			unset($tags[$k]);
		}
		if (!in_array($t, $this->authkey['tags'])) {
			unset($tags[$k]);
		}
   ++$i;
	}
	$tags = array_values($tags);
  $removetags = array();
  $addtags = array();
  foreach ($this->tags as $tag) {
		if (!in_array($tag,$tags)) {
			$removetags[] = $tag;
		}
	}
	foreach ($tags as $tag) {
		if (!in_array($tag,$this->tags)) {
			$addtags[] = $tag;
		}
	}
	if ($this->tags != $tags) {
		$this->tags = $tags;
		$this->updateSession(array(
			'atime' => microtime(true),
			'tags' => $this->tags,
		));
	}
	if (sizeof($addtags)) {
		$this->onAddedTags($addtags, $silence);
	}
	if (sizeof($removetags)) {
		$this->onRemovedTags($removetags, $silence);
	}
 }
	public function updateSession($a) {
		$a['id'] = $this->sid;
		$this->appInstance->db->{$this->appInstance->config->dbname->value . '.muchatsessions'}->upsert(
			array('id' => $this->sid),
			array('$set' => $a)
		);
		if (isset($a['statusmsg'])) {
			$this->sendMessage(array(
				'mtype' => 'status',
				'tags' => array_diff($this->tags,array('%private')),
				'from' => $this->username,
				'mtype' => 'status',
				'text' => $a['statusmsg'],
				'color' => 'green',
			));
			$this->statusmsg = $a['statusmsg'];
		}
		if (isset($a['tags'])) {
			$this->send(array(
				'type' => 'tags',
				'tags' => $a['tags'],
			));
		}
	}
	public function send($packet) {
		return $this->client->sendFrame(MUChatSession::serialize($packet));
	}
	public static function serialize($o) {
		return urlencode(json_encode($o));
	}
	public function setUsername($name, $silence = false, $tab = '') {
		$name = trim($name);
		if ($name === '') {
			if (!$silence) {
				$this->sysMsg('/nick <name>: insufficient parameters',$tab);
			}
			return 4;
		}
		if (!$this->appInstance->validateUsername($name)) {
			if (!$silence) {
				$this->sysMsg('/nick: errorneus username');
			}
			return 2;
		}
		if ($this->username === $name) {
			return 3;
		}
		$clientId = $this->client->connId;
		$appInstance = $this->appInstance;
		$this->appInstance->db->{$this->appInstance->config->dbname->value . '.muchatsessions'}->findOne(function($item) use ($clientId, $appInstance, $name, $silence) {
			if (!isset($appInstance->sessions[$clientId])) {
				return;
			}
			$session = $appInstance->sessions[$clientId];
			if ($item) { // we have got the same username		
				if (!$silence) {
					$session->sysMsg('/nick: the username is taken already');
				}
				return;
			}
			$session->updateSession(array(
				'atime' => microtime(true),
				'username' => $name,
			));
			$session->send(array(
				'type' => 'cstatus',
				'username' => $name,
			));
			if ($session->username !== null) {
				$session->broadcastEvent(array(
					'type' => 'msg',
					'mtype' => 'astatus',
					'from' => $session->username,
					'text' => 'is now known as '.$name,
					'color' => 'green',
				));
				$session->broadcastEvent(array(
					'type' => 'changedUsername',
					'sid' => (string) $session->sid,
					'old' => $session->username,
					'new' => $name,
				));
			}
			else {
				$session->broadcastEvent(array(
					'type' => 'joinsUser',
					'sid' => (string) $session->sid,
					'username' => $name,
					'tags' => array_diff($session->tags, array('%private')),
					'statusmsg' => $session->statusmsg,
				));
				$session->broadcastEvent(array(
					'type' => 'msg',
					'mtype' => 'system',
					'text' => 'Joins: '.$name,
					'color' => 'green',
				));
			}
			$session->username = $name;
		},array('where' => array(
			'username' => $name,
			'atime' => array('$gt' => microtime(true) - 20),
		)));
		return 1;
	}
	public function onFrame($data,$type) {
		$packet = json_decode($data, true);
		if (!$packet) {
			return;
		}
		if (!isset($packet['cmd'])) {
			return;
		}
		$cmd = $packet['cmd'];
		if (($cmd === 'setUsername') && isset($packet['username'])) {
			if ($this->username !== null) {
				return;
			}
			//$this->setUsername($packet['username']);
		}
		elseif (($cmd === 'hello') && isset($packet['authkey'])) {
			if ($this->username !== null) {
				return;
			}
			$clientId = $this->client->connId;
			$appInstance = $this->appInstance;
			$appInstance->ORM->getAuthKey($packet['authkey'], function($authkey) use ($clientId, $appInstance, $packet) {
				if (!isset($appInstance->sessions[$clientId])) {
					return;
				}
				$session = $appInstance->sessions[$clientId];

				if (!$authkey) {
					$session->send(array('type' => 'youWereKicked', 'reason' => 'Incorrect auth. data.'));
					return;
				}
				if (isset($authkey['su'])) {
					$session->su = $authkey['su'];
				}
				$session->authkey = $authkey;
				$appInstance->db->{$appInstance->config->dbname->value . '.akicks'}->findOne(function($akick) use ($authkey, $packet, $clientId, $appInstance) {
					if (!isset($appInstance->sessions[$clientId])) {
						return;
					}
					$session = $appInstance->sessions[$clientId];
					if ($akick) {
						$session->send(array('type' => 'youWereKicked', 'reason' => $akick['reason']));
						return;
					}
					$session->updateAvailTags();     
					Daemon::log('Incorrect auth. data.');
					$session->setUsername($authkey['username'], false, $packet['tab']);
				});
			});
		}
		elseif ($cmd === 'getAvailTags') {
			$this->updateAvailTags($packet['_id']);
		}
		elseif ($cmd === 'setTags') {
			if ($this->username === null) {
				return;
			}
			$this->setTags($packet['tags']);
		}
		elseif ($cmd === 'setmuchatignore') {
			if ($this->username === null) {
				return;
			}
			$doc = array(
				'username' => $this->username,
				'blocked' => $packet['username'],
			);
			if ($doc['username'] == $doc['blocked']) {
				return;
			}
			if ($packet['action']) {
				$this->appInstance->db->{$this->appInstance->config->dbname->value . '.muchatignore'}->insert($doc);
			}
			else {
				$this->appInstance->db->{$this->appInstance->config->dbname->value . '.muchatignore'}->remove($doc);
			}
		}
		elseif ($cmd === 'keepalive') {
			$this->updateSession(array(
				'atime' => microtime(true),
			));
		}
		elseif ($cmd == 'getHistory') {
			if ($this->username === null) {
				return;
			}
			$session = $this;
			$condts = array('$lt' => microtime(true));
			$lastTS = isset($packet['lastTS']) ? (float) $packet['lastTS'] : 0;
			if ($lastTS > 0) {
				$condts['$gt'] = $lastTS;
			}
			$this->appInstance->db->{$this->appInstance->config->dbname->value . '.muchatevents'}->find(function($cursor) use ($session) {
				$tag->cursor = $cursor;
				$cursor->items = array_reverse($cursor->items);
				foreach ($cursor->items as $k => &$item) {
					$item['_id'] = (string) $item['_id'];
					if (isset($item['sid'])) {
						$item['sid'] = (string) $item['sid'];
					}
					if (is_string($item['tags'])) {
						$item['tags'] = array($item['tags']);
					}
					if (in_array('%private',$item['tags'])) {
						if (!isset($item['to'])) {
							continue;
						}
						if (!in_array($session->username,$item['to']) && ($session->username != $item['from'])) {
							continue;
						}
					}
					$item['history'] = true;
					$session->send($item);
					unset($cursor->items[$k]);
				}
				$cursor->destroy();
			},array(
				'sort' => array('ts' => -1),
				'where' => array('ts' => $condts,'tags' => array('$in' => $packet['tags'])),
				'limit' => -20,
			));
		}
		elseif ($cmd == 'getUserlist') {
			if ($this->username === null) {
				return;
			}
			$session = $this;
			$this->appInstance->db->{$this->appInstance->config->dbname->value . '.muchatsessions'}->find(function($cursor) use ($session) {
				$tag->cursor = $cursor;
				$cursor->items = array_reverse($cursor->items);
				foreach ($cursor->items as $k => &$item) {
					unset($item['_id']);
					$item['id'] = isset($item['id'])?(string) $item['id']:'';
				}
				$session->send(array('type' => 'userlist', 'userlist' => $cursor->items));
				$cursor->destroy();
			}, array(
				'sort' => array('ctime' => -1),
				'where' => array(
					'tags' => array('$in' => array_diff($packet['tags'],array('%private'))),
					'atime' => array('$gt' => microtime(true)-20),
					'username' => array('$exists' => true),
				),
				'limit' => -2000,
			));
		}
		elseif ($cmd === 'sendMessage') {
			if (!isset($packet['tags'])) {
				return false;
			}
			if (!$this->username) {
				return false;
			}
			$username = $this->username;
			if ((!isset($packet['text'])) || (trim($packet['text']) === '')) {
				return false;
			}
			$text = $packet['text'];
			$color = isset($packet['color']) ? (string) $packet['color'] : '';
			static $colors = array('black', 'red', 'green', 'blue');
			if (!in_array($color, $colors)) {
				$color = $colors[0];
			}
			$c = substr($text,0,1);
			if ($c === '/') {
				$e = explode(' ', $text, 2);
				$m = strtolower(substr($e[0], 1));
				$text = isset($e[1]) ? trim($e[1]) : '';
				if ($m === 'me') {
					if ($text === '') {
						$this->sysMsg('/me <message>: insufficient parameters', $packet['tab']);
					}
					else {
						$this->updateSession(array('statusmsg' => $text));
					}
				}
				elseif ($m === 'tags') {
					$tags = trim($text);
					if ($tags !== '') {
						$this->setTags(array_map('trim',explode(',', $tags)));
					}
					$this->sysMsg('/tags: '.implode(', ', $this->tags), $packet['tab']);
				}
				elseif ($m === 'join') {
					$tags = $text;
					if ($tags !== '') {
						$this->addTags(array_map('trim', explode(',', $tags)));
					}
					else {
						$this->sysMsg('/join <tag1>{,<tagN>}: insufficient parameters', $packet['tab']);
					}
				}
				elseif ($m === 'part') {
					$tags = $text;
					if ($tags !== '') {
						$this->removeTags(array_map('trim', explode(',', $tags)));
					}
					else {
						$this->sysMsg('/part <tag1>{,<tagN>}: insufficient parameters', $packet['tab']);
					}
				}
				elseif ($m === 'nick') {
					//$this->setUsername($text);
				}
				elseif ($m === 'thetime') {
					$this->sysMsg('Current time: '.date('r'),$packet['tab']);
				}
				elseif ($m === 'su') {
					$password = $text;
					if ($this->su || (($password !== '') && ($password === $this->appInstance->config->adminpassword->value))) {
						$this->su = true;
						$this->send(array('type' => 'youAreModerator'));
						$this->sysMsg('You\'ve got the power.', $packet['tab']);
					}
					else {
						$this->sysMsg('Your powers are weak, old man.', $packet['tab']);
					}
				}
				elseif ($m === 'kick') {
					$e = explode(' ', $text, 3);
					$users = isset($e[0]) ? trim($e[0]) : '';
					$tags = isset($e[1]) ? trim($e[1]) : '';
					$reason = isset($e[2]) ? trim($e[2]) : '';
					if ($users === '') {
						$this->sysMsg('/kick <name> [<tags>] [<reason>]: insufficient parameters', $packet['tab']);
					}
					else {
						if (!$this->su) {
							$this->sysMsg('Your powers are weak, old man.', $packet['tab']);
						}
						else {
							$this->appInstance->kickUsers($users,$tags,$reason);
						}
					}
				}
				elseif ($m === 'fchname') {
					$e = explode(' ', $text);
					$name = isset($e[0]) ? trim($e[0]) : '';
					$newname = isset($e[1]) ? trim($e[1]) : '';
					if (($name === '') || ($newname === '')) {
						$this->sysMsg('/fchname <name> <newname>: insufficient parameters', $packet['tab']);
					}
					elseif (!$this->appInstance->validateUsername($newname)) {
						$this->sysMsg('/fchname: newname>', $packet['tab']);
					}
					else {
						if (!$this->su) {
							$this->sysMsg('Your powers are weak, old man.', $packet['tab']);
						}
						else {
							$this->appInstance->forceChangeNick($name, $newname);
						}
					}
				}
				else {
					$this->sysMsg($m . ' Unknown command', $packet['tab']);
				}
			}
			else {
				$doc = array(
					'mtype' => 'pub',
					'tags' => array_intersect($packet['tags'], $this->tags),
					'from' => $username,
					'text' => $text,
					'color' => $color,
					'tab' => isset($packet['tab']) ? $packet['tab'] : null,
				);
				if (preg_match_all('~(?<=^|\s)@([A-Za-z\-_!0-9\.\wА-Яа-я]+)~u',$text,$m)) {
					$doc['to'] = $m[1];
					if (sizeof($doc['to']) == 1) {
						if (binarySubstr($doc['text'], 0, $n = strlen($s = '@' . $doc['to'][0] . ': ')) == $s) {
							$doc['text'] = binarySubstr($doc['text'], $n);
						}
					}
				}
				if (in_array('%private', $packet['tags'])) {
					$clientId = $this->client->connId;
					$appInstance = $this->appInstance;
					$this->appInstance->db->{$this->appInstance->config->dbname->value . '.muchatignore'}->findOne(function($item) use ($clientId, $appInstance, $doc) {
						if (!$item) {
							if (!isset($appInstance->sessions[$clientId])) {
								return;
							}	
							$session = $appInstance->sessions[$clientId];
							$session->sendMessage($doc);
						}
					},array('where' => array(
						'username' => $this->username,
						'blocked' => $doc['to'][0],
					)));
				}
				else {
					$this->sendMessage($doc);
				}
			}
		}
	}
	
	public function sendMessage($doc) {
		$doc['type'] = 'msg';
		$t = microtime(true);
		if ($this->lastMsgTS !== null) {
			$d = $t - $this->lastMsgTS;
			$this->updateSession(array('mtime' => microtime(true)));
			if ($d < $this->appInstance->minMsgInterval) {
				$this->sysMsg('Too fast. Min. interval is ' . $this->appInstance->minMsgInterval . ' sec. You made '.round($d,4) . '.', $doc['tab']);
				return;
			}
		}
		$this->lastMsgTS = $t; 
		$this->broadcastEvent($doc);
	}
	
	public function updateAvailTags($rId = null) {
		$tags = array();
		foreach ($this->authkey['tags'] as $t) {
			$tags[$t] = isset($this->appInstance->statTags[$t]) ? $this->appInstance->statTags[$t] : array('number' => 0);
		}
		$this->send(array('_id' => $rId, 'type' => 'availableTags', 'tags' => $tags));
	}
	
	public function broadcastEvent($doc) {
		if (!isset($doc['ts'])) {
			$doc['ts'] = microtime(true);
		}
		if (!isset($doc['tags'])) {
			$doc['tags'] = $this->tags;
		}
		$doc['sid'] = $this->sid;
		$this->appInstance->db->{$this->appInstance->config->dbname->value . '.muchatevents'}->insert($doc);
	}
	public function sysMsg($msg, $tab = null) {
		$this->send(array(
			'type' => 'msg',
			'mtype' => 'system',
			'text' => $msg,
			'color' => 'green',
			'ts' => microtime(true),
			'tab' => $tab,
		));
	}
}

class MUChat_MsgQueueRequest extends Request {
	public function run() {
		foreach ($this->appInstance->tags as $tag) {
			$tag->touch();
		}
		$this->sleep(0.1);
	}
}
class MUChat_IdleCheck extends Request {
	public function run() {
		$appInstance = $this->appInstance;
		$this->appInstance->db->{$this->appInstance->config->dbname->value . '.muchatsessions'}->find(function($cursor) use ($appInstance) {
			$users = array();
			foreach ($cursor->items as $sess) {
				$users[] = $sess['username'];
			}
			$appInstance->kickUsers($users, '', 'Idle');
			$cursor->destroy();
		},array(
			'where' => array(
				'mtime' => array('$lt' => microtime(true)-$this->appInstance->idleTimeout),
				'worker' => $this->appInstance->ipcId,
				'username' => array('$exists' => true)
			)
		));
		$this->sleep(5);
	}
}
class MUChat_UpdateStat extends Request {
	
	public function run() {
	
		$appInstance = $this->appInstance;
		$appInstance->db->distinct(array(
			'col' => $appInstance->dbname . '.muchatsessions',
			'key' => 'tags'
		), function($result) use ($appInstance) {
			foreach ($result['values'] as $tag) {
				if ($tag == '%private') {
					continue;
				}
				$appInstance->db->{$appInstance->dbname . '.muchatsessions'}->count(function($result) use ($tag, $appInstance) {
					$appInstance->db->{$appInstance->dbname . '.tags'}->upsert(
						array('tag' => $tag),
						array('$set' => array(
							'tag' => $tag,
							'number' => $result['n'],
							'atime' => time(),
						)
					));
				},array(
					'atime' => array('$gt' => microtime(true) - 20),
					'tags' => $tag,
					'username' => array('$exists' => true)
				));
			}
		});
		$appInstance->db->{$appInstance->dbname . '.tags'}->remove(array('atime' => array('$lt' => microtime(true) - 20)));
		$appInstance->db->{$appInstance->dbname . '.tags'}->find(function($cursor) use ($appInstance) {
			$appInstance->statTags = array();
			foreach ($cursor->items as $item) {
				$appInstance->statTags[$item['tag']] = $item;
			}
			$cursor->destroy();
		});
		$this->sleep(5);
	}
}
