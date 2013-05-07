<?php
namespace WakePHP\Components\Muchat;

use PHPDaemon\Clients\MongoClientSessionFinished;

class Tag {
	public $appInstance;
	public $sessions = array();
	public $tag;
	public $cursor;
	public $counter = 0;

	public function __construct($tag, $appInstance) {
		$this->tag         = $tag;
		$this->appInstance = $appInstance;
	}

	public function touch() {
		if (!$this->cursor || $this->cursor->destroyed) {
			$tag = $this;
			$this->appInstance->db->{$this->appInstance->config->dbname->value . '.muchatevents'}->find(function ($cursor) use ($tag) {
				$tag->cursor = $cursor;
				foreach ($cursor->items as $k => &$item) {
					if ($item['type'] === 'kickUsers') {
						foreach ($tag->sessions as $id => $v) {
							$sess = $tag->appInstance->sessions[$id];
							if (($sess->username !== null) && ($tag->appInstance->compareMask($sess->username, $item['users']))) {
								$sess->removeTags(array($tag->tag), true);
								$sess->sysMsg('You were kicked from #' . $tag->tag . '.' . ($item['reason'] !== '' ? ' Reason: ' . $item['reason'] : ''));
								$sess->send(array('type' => 'youWereKicked', 'reason' => $item['reason']));
								$tag->appInstance->broadcastEvent(array(
																	  'type'  => 'msg',
																	  'mtype' => 'system',
																	  'text'  => ' Kicked: ' . $sess->username . ($item['reason'] !== '' ? ', reason: ' . $item['reason'] : ''),
																	  'color' => 'green',
																	  'tags'  => $tag->tag,
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
						$item['_id'] = (string)$item['_id'];
						if (isset($item['sid'])) {
							$item['sid'] = (string)$item['sid'];
						}
						$packet = Session::serialize($item);
						foreach ($tag->sessions as $id => $v) {
							$s = $tag->appInstance->sessions[$id];
							if (is_string($item['tags'])) {
								$item['tags'] = array($item['tags']);
							}
							if (in_array('%private', $item['tags'])) {
								if (!isset($item['to'])) {
									continue;
								}
								if (!in_array($s->username, $item['to']) && ($s->username != $item['from'])) {
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
				   'sort'     => array('$natural' => 1),
				   'where'    => array(
					   'ts'   => array(
						   '$gt' => microtime(true)
					   ),
					   'tags' => array(
						   '$in' => binarySubstr($this->tag, 0, 1) == '%' ? array($this->tag) : array($this->tag, '%all')
					   )
				   )));
		}
		elseif (!$this->cursor->session->busy) {
			try {
				$this->cursor->getMore();
			} catch (MongoClientSessionFinished $e) {
				$this->cursor = false;
			}
		}
	}
}