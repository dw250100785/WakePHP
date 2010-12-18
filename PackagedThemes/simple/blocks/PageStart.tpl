<!DOCTYPE html> 
<html lang="{$req->locale}"> 
<head> 
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" /> 
<title>{$block->parentNode->title} - WakePHP</title> 
<link href="/css/jquery-ui.css" rel="stylesheet" type="text/css" /> 
<link href="/css/jquery.tabs.css" rel="stylesheet" type="text/css" />
<link href="/css/jquery.contextMenu.css" rel="stylesheet" type="text/css" /> 
<link href="/css/main.css" rel="stylesheet" type="text/css" /> 
<link href="/favicon.ico" rel="icon" type="image/x-icon" /> 
<link href="/favicon.ico" rel="shortcut icon" type="image/x-icon" /> 
{if $req->locale != $req->appInstance->config->defaultlocale->value}
<link href="/locale/{$req->locale}/Account.json" lang="{$req->locale}" rel="gettext"/>
<link href="/locale/{$req->locale}/Blocks.json" lang="{$req->locale}" rel="gettext"/>
<link href="/locale/{$req->locale}/MUChat.json" lang="{$req->locale}" rel="gettext"/>
{/if}
<meta name="description" content="WakePHP — PHP that never sleeps" /> 
<meta name="keywords" content="phpDaemon, php" /> 
{?$libs = "https://ajax.googleapis.com/ajax/libs/"}{?$jqmin = false}
<script src="{$libs}jquery/1.4.4/jquery{$jqmin?".min":""}.js"></script>
<script src="{$libs}jqueryui/1.8.6/jquery-ui{$jqmin?".min":""}.js"></script>
<script src="/js/jquery.keyboard.js" type="text/javascript"></script>
<script src="/js/tiny_mce/jquery.tinymce.js" type="text/javascript"></script>
<script src="/js/jquery.contextMenu.js" type="text/javascript"></script>
<script src="/js/jquery.form.js" type="text/javascript"></script>
<script src="/js/jquery.i18n.js" type="text/javascript"></script>
<script src="/js/jquery.gettext.js" type="text/javascript"></script>
<script src="/js/jquery.sprintf.js" type="text/javascript"></script>
<script src="/js/core.js" type="text/javascript"></script>
<script src="/js/config.js" type="text/javascript"></script>
<script src="/js/CmpCAPTCHA.js" type="text/javascript"></script>

<script type="text/javascript" src="/js/jquery.easypassgen.js"></script>
<script type="text/javascript" src="/js/jquery.address-1.3.min.js"></script>
<script type="text/javascript" src="/js/jquery.cookie.js"></script>
<script type="text/javascript" src="/js/jquery.json.js"></script>
<script type="text/javascript" src="/js/jquery.scrollTo-min.js"></script>
<script type="text/javascript" src="/js/jquery.tooltip.pack.js"></script>
<script type="text/javascript" src="/js/jquery.keyboard.js"></script>

{if $req->account.logged}
<script src="/js/CmpI18n.js" type="text/javascript"></script>
<script src="/js/CmpBlocks.js" type="text/javascript"></script>
{/if}
</head> 
<body><div class="block" id="{$block->parentNode->_id}">
{getBlock name="UserMiniPanel"}
<div class="pageContent">
