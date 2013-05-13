{getBlock name="PageStart"}
<script src="/js/CmpAccountFinishSignup.js" type="text/javascript"></script>
<form action="account/finishSignup" class="AccountFinishSignupForm" method="post">
	<h1 class="i18n">{$block->title|escape}</h1>

	{if $req->account.logged}
		<div class="i18n">You're logged as <strong class="i18nArg">{$req->account.email|escape}</strong>.
		</div>
		<br/>
	{/if}
	<div class="fieldname"><span class="i18n">E-Mail</span>:</div>
	<input type="text" name="email" size="25"/><br/><br/>
	<br/>
	<button type="submit" class="i18n">Finish</button>
</form>
{getBlock name="PageEnd"}