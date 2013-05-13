{getBlock name="PageStart"}
<script src="/js/CmpAccountFinishSignup.js" type="text/javascript"></script>
<form action="Account/finishSignup" class="AccountFinishSignupForm" method="post">
	<h1 class="i18n">{$block->title|escape}</h1>

	{if $req->account.logged}
		<div class="i18n">You're logged as <strong class="i18nArg">{$req->account.email|escape}</strong>.
		</div>
		<br/>
	{/if}
	<div class="fieldname"><span class="i18n">E-Mail</span>:</div>
	<input type="text" name="email" size="25"/><br/><br/>

	<div class="codeField"{if !isset($quicky.request.code)} style="display:none"{/if}>
		<div class="fieldname"><span class="i18n">Code</span>:</div>
		<input type="code" name="code" size="10" value="{$quicky.requeststring.code|escape}"/><br/>
	</div>
	<br/>
	<button type="submit" class="i18n" disabled="disabled">Finish</button>
	<div class="popupMsg" style="display:none"></div>
</form>
{getBlock name="PageEnd"}