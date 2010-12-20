{getBlock name="PageStart"}

<script src="/js/CmpAccountRecovery.js" type="text/javascript"></script>
<form action="Account/Recovery" method="post">
<h1 class="i18n">{$block->title|escape}</h1>
<div class="fieldname"><span class="i18n">E-Mail</span>:</div>
<input type="text" name="email" value="{if isset($quicky.request.email)}{$quicky.requeststring.email|escape}{elseif $req->account.logged}{$req->account.email}{/if}" size="25" /><br /><br />
<div class="fieldname"><span class="i18n">Code</span>:</div>
<input type="password" name="password" size="25" /><br />
<a href="/{$req->locale}/account/recovery" class="forgotPasswordButton i18n">Forgot your password?</a><br />
<br /><button type="submit" class="i18n" disabled="disabled">Log in</button>
</form>

{getBlock name="PageEnd"}
