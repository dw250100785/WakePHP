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
2. Each method matching `*Controller` pattern will be controller.
3. Name of the controller method and name of the component class is a route for URL to this controller. For example, `Account::ExternalAuthController` will match `/component/Account/ExternalAuth` URL.  
4. Controller should handle request (see $this->req) and call `setResult()` method of this request (even if your controller has no results for some case) - while proccess will wait your result, user will see "loading" page.  
5. You can wrap execution of controller into event-methods, such as `$this->onAuth()`, `onSessionStart()` and others. Your code will be executed when these events will occur (events will be called if necessary).  
6. You can use database collections via `$this->appInstance->nameOfCollection` call.
7. You can access the configuration settings by calling `$this->appInstance->config->nameOfSetting->value` (last `value` word is mandatory).

##Now lets add new page
Templates are placed outside of source files, in a `../themes/{name_of_theme}/blocks` folder, for instance, `../themes/simple/blocks/` .  
Templates renderer is [Quicky](https://github.com/kakserpom/quicky)  
1. Add file NameOfPage.obj into templates folder - here will be meta-information in JSON-format;  
2. Add file NameOfPage.tpl - here will be content of template.   
3. 