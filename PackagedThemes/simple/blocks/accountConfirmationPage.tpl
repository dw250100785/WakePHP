{getBlock name="PageStart"}
<form action="/{$req->locale}/account/confirm" class="AccountConfirmForm" method="get">

<h1 class="i18n">Account Confirmation</h1>

{if $status != 'accountNotFound'}
<div class="fieldname"><span class="i18n">Code</span>:</div><div class="fieldcontrols">
<input type="text" name="code" class="biginput" size="6" maxlength="6" /></div><br class="clearfloat" />
{/if}
{if $status == 'incorrectCode'}<div class="errorMessage i18n">Incorrect code.</div>
{elseif $status == 'alreadyConfirmed'}<div class="errorMessage i18n">Your account already confirmed.</div>
{elseif $status == 'accountNotFound'}<div class="errorMessage i18n">Account not found. Please register again.</div>
{/if}<br /><br />
{if $status != 'accountNotFound'}
<br /><button type="submit" class="i18n">Confirm my account.</button>
{/if}
</form>
<br />

{getBlock name="PageEnd"}
