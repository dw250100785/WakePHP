<script type="text/javascript" src="/js/CmpAccountLogin.js"></script>
<form action="Account/Authentication" method="post">
<h1 class="i18n">Authentication</h1>
{if $req->account.logged}<div class="i18n">You're logged as <strong class="i18nArg">{$req->account.username|escape}</strong>.</div><br />{/if}
<div class="fieldname"><span class="i18n">E-Mail</span>:</div>
<input type="text" name="username" size="25" /><br /><br />
<div class="fieldname"><span class="i18n">Password</span>:</div>
<input type="password" name="password" size="25" /><br />
<br /><button type="submit" class="i18n" disabled="disabled">Log in</button>
</form>