{getBlock name="PageStart"}

<script src="/js/CmpAccountRecovery.js" type="text/javascript"></script>
<form action="Account/Recovery" class="AccountRecoveryForm" method="post">
<h1 class="i18n">{$block->title|escape}</h1>
<div class="fieldname"><span class="i18n">E-Mail</span>:</div>
<input type="text" name="email" value="{if isset($quicky.request.email)}{$quicky.requeststring.email|escape}{elseif $req->account.logged}{$req->account.email}{/if}" size="25" /><br /><br />
<div class="codeField"{if !isset($quicky.request.code)} style="display:none"{/if}><div class="fieldname"><span class="i18n">Code</span>:</div>
<input type="code" name="code" size="10" value="{$quicky.requeststring.code|escape}" /><br />
</div>
<br /><button type="submit" class="i18n" disabled="disabled">Get confirmation code</button>
<div class="popupMsg" style="display:none"></div>
</form>

{getBlock name="PageEnd"}
