<script src="/js/CmpAccountLogin.js" type="text/javascript"></script>
<script src="/js/jquery/jquery.capslock.js" type="text/javascript"></script>
<form action="Account/Authentication" method="post">
<h1 class="i18n">{$block->title|escape}</h1>
{if $req->account.logged}<div class="i18n">You're logged as <strong class="i18nArg">{$req->account.email|escape}</strong>.</div><br />{/if}
<div class="fieldname"><span class="i18n">E-Mail</span>:</div>
<input type="text" name="username" size="25" /><br /><br />
<div class="fieldname"><span class="i18n">Password</span>: <span class="capslock" style="display:none">Caps Lock</span></div>
<input type="password" name="password" size="25" /><br />
<a href="/{$req->locale}/account/recovery" class="forgotPasswordButton i18n">Forgot your password?</a><br />
<br /><button type="submit" class="i18n" disabled="disabled">Log in</button>
</form>