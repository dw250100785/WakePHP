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

How to create new "page"
========================
Let's start from controller:    
1. Add class of controller to namespace `\WakePHP\Components\`  
2. 
