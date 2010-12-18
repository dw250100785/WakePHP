{getBlock name="PageStart"}
<form action="/{$req->locale}/account/confirm" class="AccountConfirmForm" method="get">

<h1 class="i18n">Account Confirmation</h1>

<div class="fieldname"><span class="i18n">Code</span>:</div><div class="fieldcontrols">
<input type="text" name="code" class="biginput" size="6" maxlength="6" /></div><br class="clearfloat" />
{if $status == 'incorrectCode'}<div class="errorMessage i18n">Incorrect code.</div>
{elseif $status == 'alreadyConfirmed'}<div class="errorMessage i18n">Your account already confirmed.</div>
{/if}<br /><br />

<br /><button type="submit" class="i18n">Confirm my account.</button>
</form>
<br />

{getBlock name="PageEnd"}
