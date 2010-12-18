{getBlock name="PageStart"}
<form action="/{$req->locale}/account/confirm" class="AccountConfirmForm" method="get">

<h1 class="i18n">Account Confirmation</h1>

<div class="fieldname"><span class="i18n">Code</span>:</div><div class="fieldcontrols">
<input type="text" name="code" size="10" /></div><br class="clearfloat" /><br /><br />

<br /><button type="submit" class="i18n">Confirm my account.</button>
</form>
<br />

{getBlock name="PageEnd"}
