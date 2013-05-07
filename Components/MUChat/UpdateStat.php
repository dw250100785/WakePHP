<?php
namespace WakePHP\Components\Muchat;

use PHPDaemon\Request;

class UpdateStat extends Request {

	public function run() {

		$appInstance = $this->appInstance;
		$appInstance->db->distinct(array(
									   'col' => $appInstance->dbname . '.muchatsessions',
									   'key' => 'tags'
								   ), function ($result) use ($appInstance) {
			foreach ($result['values'] as $tag) {
				if ($tag == '%private') {
					continue;
				}
				$appInstance->db->{$appInstance->dbname . '.muchatsessions'}->count(function ($result) use ($tag, $appInstance) {
					$appInstance->db->{$appInstance->dbname . '.tags'}->upsert(
						array('tag' => $tag),
						array('$set' => array(
							'tag'    => $tag,
							'number' => $result['n'],
							'atime'  => time(),
						)
						));
				}, array(
					   'atime'    => array('$gt' => microtime(true) - 20),
					   'tags'     => $tag,
					   'username' => array('$exists' => true)
				   ));
			}
		});
		$appInstance->db->{$appInstance->dbname . '.tags'}->remove(array('atime' => array('$lt' => microtime(true) - 20)));
		$appInstance->db->{$appInstance->dbname . '.tags'}->find(function ($cursor) use ($appInstance) {
			$appInstance->statTags = array();
			foreach ($cursor->items as $item) {
				$appInstance->statTags[$item['tag']] = $item;
			}
			$cursor->destroy();
		});
		$this->sleep(5);
	}
}