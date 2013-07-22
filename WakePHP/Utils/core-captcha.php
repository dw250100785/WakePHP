<?php
/**************************************************************************/
/* 0xENGINE: Web Site	                                           	      */
/* ===========================                                      	  */
/* (c)oded 2006 by white phoenix                                		  */
/* http://whitephoenix.ru                                                 */
/*                                                                        */
/* This program is free software. You can't redistribute it and/or modify */
/* it under the terms of the GNU General Public License as published by	  */
/* the Free Software Foundation; either version 2 of the License.         */
/* 																          */
/* core-captcha.php: CAPTCHA											  */
/**************************************************************************/
require_once QUICKY_DIR.'Quicky.form.class.php';
class QCAPTCHA extends QCAPTCHA_Abstract
{
 public $_id;
 public $_text;
 public $skin = 'black';
 public function __construct($properties = array())
 {
  require_once QUICKY_DIR.'plugins/Captcha/Captcha_draw.class.php';
  $CAPTCHA = new CAPTCHA_draw;
  $CAPTCHA->generate_text();
  if (!isset($properties['type'])) {$properties['type'] = 'captcha';}
  foreach ($properties as $k => $v) {$this->$k = $v;}
 }
 public function init()
 {
  if ($this->_id !== NULL) {return;}
  $this->_id = gpcvar_str($_REQUEST[$this->name.'_id']);
  $this->_text = gpcvar_str($_REQUEST[$this->name.'_text']);
 }
 public function generateId()
 {
  $this->init();
  while (xE::$memcache->get($captcha_id = chr_gen('ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789',16),TRUE) !== FALSE) {}
  xE::$memcache->set('captcha.'.$captcha_id,chr_gen('ABCDEFGHKLMNPQRSTUVWXYZ23456789abcdefghkmnpqrstuvwxyz23456789',4),3600);
  return $captcha_id;
 }
 public function display($captcha_id)
 {
  $this->init();
  $text = xE::$memcache->get('captcha.'.$captcha_id,TRUE);
  if ($text === FALSE) {exit('Not found.');}
  if (!xE::$memcache->add('captchaseen.'.$captcha_id,1,3600)) {exit('Already viewed.');}
  require_once QUICKY_DIR.'plugins/Captcha/Captcha_draw.class.php';
  $image = new CAPTCHA_draw;
  $image->skin = $this->skin;
  $image->text = $text;
  $image->show();
 }
 public function validate($errMsg = NULL)
 {
  $this->init();
  $orig = xE::$memcache->get('captcha.'.$this->_id,TRUE);
  $success = ($orig !== FALSE) && (strtolower($orig) === strtolower($this->_text));
  if (!$success && ($errMsg !== NULL)) {$this->error($errMsg);}
  return $success;
 }
 public function devalidate($errMsg = NULL)
 {
  $this->init();
  xE::$memcache->delete('captchaseen.'.$this->_id);
  $success = xE::$memcache->delete('captcha.'.$this->_id);
  if (!$success && ($errMsg !== NULL)) {$this->error($errMsg);}
  return $success;
 }
}
