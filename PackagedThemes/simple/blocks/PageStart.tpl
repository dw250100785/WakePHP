<!DOCTYPE html> 
<html lang="{$req->locale}"> 
<head> 
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" /> 
<title>{$block->parentNode->title} - WakePHP</title> 
<link href="/css/jquery-ui.css" rel="stylesheet" type="text/css" /> 
<link href="/css/jquery.tabs.css" rel="stylesheet" type="text/css" />
<link href="/css/jquery.contextMenu.css" rel="stylesheet" type="text/css" /> 
<link href="/css/main.css" rel="stylesheet" type="text/css" /> 
<link href="/css/jquery.autocomplete.css" rel="stylesheet" type="text/css" /> 
<link href="/favicon.ico" rel="icon" type="image/x-icon" /> 
<link href="/favicon.ico" rel="shortcut icon" type="image/x-icon" /> 
{if $req->locale != 'en'}
<link href="/locale/{$req->locale}/Account.json" lang="{$req->locale}" rel="gettext"/>
<link href="/locale/{$req->locale}/Blocks.json" lang="{$req->locale}" rel="gettext"/>
<link href="/locale/{$req->locale}/MUChat.json" lang="{$req->locale}" rel="gettext"/>
{if in_array('Superusers',$req->account.aclgroups)}<link href="/locale/{$req->locale}/ACP.json" lang="{$req->locale}" rel="gettext"/>
{/if}{/if}
<meta name="description" content="WakePHP — PHP that never sleeps" /> 
<meta name="keywords" content="phpDaemon, php" /> 
{?$libs = "https://ajax.googleapis.com/ajax/libs/"}{?$jqmin = false}
<script src="{$libs}jquery/1.4.4/jquery{$jqmin?".min":""}.js"></script>
<script src="/js/jquery.autocomplete.js" type="text/javascript"></script>
<script src="{$libs}jqueryui/1.8.6/jquery-ui{$jqmin?".min":""}.js"></script>
<script src="/js/jquery/jquery.keyboard.js" type="text/javascript"></script>
<script src="/js/tiny_mce/jquery.tinymce.js" type="text/javascript"></script>
<script src="/js/jquery/jquery.contextMenu.js" type="text/javascript"></script>
<script src="/js/jquery/jquery.form.js" type="text/javascript"></script>
<script src="/js/jquery/jquery.i18n.js" type="text/javascript"></script>
<script src="/js/jquery/jquery.gettext.js" type="text/javascript"></script>
<script src="/js/jquery/jquery.sprintf.js" type="text/javascript"></script>
<script src="/js/core.js" type="text/javascript"></script>
<script src="/js/config.js" type="text/javascript"></script>
<script src="/js/CmpCAPTCHA.js" type="text/javascript"></script>

<script src="/js/jquery/jquery.easypassgen.js" type="text/javascript"></script>
<script src="/js/jquery/jquery.address-1.3.min.js" type="text/javascript"></script>
<script src="/js/jquery/jquery.cookie.js" type="text/javascript"></script>
<script src="/js/jquery/jquery.json.js" type="text/javascript"></script>
<script src="/js/jquery/jquery.scrollTo-min.js" type="text/javascript"></script>
<script src="/js/jquery/jquery.tooltip.pack.js" type="text/javascript"></script>
<script src="/js/jquery/jquery.keyboard.js" type="text/javascript"></script>
<script type="text/javascript">var $user = {array(
	'logged' => $req->account.logged,
	'username' => $req->account.username,
	'aclgroups' => $req->account.aclgroups,
	'acl' => $req->account.acl,
)|json_encode}</script>
{if $req->components->Blocks->checkRole('Webmaster')}
<script src="/js/CmpI18n.js" type="text/javascript"></script>
<script src="/js/CmpBlocks.js" type="text/javascript"></script>
{/if}
</head> 
<body><div class="block" id="{$block->parentNode->_id}">
{getBlock name="UserMiniPanel"}
<div class="pageContent">
