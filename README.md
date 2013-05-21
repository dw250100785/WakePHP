WakePHP
=======
Web-framework, based on [PHPDaemon](https://github.com/kakserpom/phpdaemon)  

#Draft of tutorial

Framework is based on components:  
* PHPDaemon (requests)  
* Quicky (templates)  
* MongoDB as a database

Implementation of MVC
=====================
#M
ORM-entities contains logic of data storage, domain logic is placed partially in components, mostly in ORM-entities. 
#V
Blocks - views, which can fetch data from models to fill out templates
#C
Components - controllers

How to create new module
========================
##Let's start from component      
1. Add class of component to namespace `\WakePHP\Components\`    
2. each method matching `*Controller` pattern will be controller;
3. name of the controller method and name of the component class is a route for URL to this controller. For example, `Account::ExternalAuthController` will match `/component/Account/ExternalAuth` URL;  
4. controller should handle request (see $this->req) and call `setResult()` method of this request (even if your controller has no results for some case) - while proccess will wait your result, user will see "loading" page;  
5. you can wrap execution of controller into event-methods, such as `$this->onAuth()`, `onSessionStart()` and others. Your code will be executed when these events will occur (events will be called if necessary);  
6. you can use database collections via `$this->appInstance->nameOfCollection` call;
7. you can access the configuration settings by calling `$this->appInstance->config->nameOfSetting->value` (last `value` word is mandatory).

##Now lets add new page
Templates are placed outside of source files, in a `../themes/{name_of_theme}/blocks` folder, for instance, `../themes/simple/blocks/` .  
Templates renderer is [Quicky](https://github.com/kakserpom/quicky)  
1. Add file NameOfPage.obj into templates folder - here will be meta-information in JSON-format;  
2. add file NameOfPage.tpl - here will be content of the template;   
3. in the *.obj*-file, declare path (route) to this page, locale and title. For example:

	{
		"locale": "en",
		"path": "\/account\/signup",
		"title": "Sign up"
	}

##Add MongoDB collection
It's the most simple task: 
1. add class into `\WakePHP\ORM` namespace and name this class with the name of collection in MongoDB;  
2. add `extends \WakePHP\Core\ORM` to class signature - since now you can use this class to access the collection in your database;  
3. to make controller's life easy, add CRUD-methods: 
4. to do this, add protected field with name of the collection  
5. then add method `init()`, where that field will be filled by instance of collection object:

	public function init() {
			$this->myCollection = $this->appInstance->db->{$this->appInstance->dbname . '.myCollection'};
			$this->myCollection->ensureIndex(['code' => 1, 'email' => 1], ['unique' => true]);
		}
6. if you need indexes, you can define them in `init()` method (see in example). Indexes will be defined only once, not in each initialization.

