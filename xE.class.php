<?php
/*
из основного функционала

    сессии
        статистика посещений
        geoip и фиксация просмотренных страниц 
    автоизация
        пользователи системы
        могут быть свои под-пользователи для каждого пользователя
    контент
        шаблоны страниц (подключен Quicky)
        мультиязычность (htityj)
        блоки динамического контента вставляемые в шаблоны (html, js, графка, текст)
    
развитие

    проекты
        пользовательские страницы вида masha.site.com
        админ панели для пользвательского управления
    статистика
        билинг пользователей
        срезы статистики посещений
    т.д. (пока в работе)
  */
  
  /*
   db.users.ensureIndex({username: 1},{unique: true});
  */
  
  
 /* Основной класс приложения, хранит ссылки на осноные подсистемы (Quicky, MongoClient, ...)
 */
class xE extends AppInstance
{
 public $quicky;
 public $statistics;
 public $db;
 public $languages = array('ru','en');
 public $dbname = 'xE';
 public function init() {
  Daemon::log('xE up.');
  ini_set('display_errors','On');
  $appInstance = $this;
  $appInstance->db = Daemon::$appResolver->getInstanceByAppName('MongoClient');
  $appInstance->quicky = new Quicky;
  $appInstance->quicky->template_dir = $this->config->templatedir->value;
  $appInstance->quicky->compile_dir = '/tmp/templates_c/';
  $appInstance->statistics = new xEstatistics($this);
  $appInstance->placeholders = new xEplaceholders($this);
 }
 protected function getConfigDefaults()
 {
  return array(
   'templatedir' => './templates/',
  );
 }
 	/**
	 * Creates Request.
	 * @param object Request.
	 * @param object Upstream application instance.
	 * @return object Request.
	 */
	public function beginRequest($req, $upstream) {
		return new xErequest($this, $upstream, $req);
	}
}

