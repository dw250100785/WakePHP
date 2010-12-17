{getBlock name="PageStart"}

<form action="Account/Signup" class="AccountSignupForm" method="post">

<h1 class="i18n">Account Registration</h1>

<div class="fieldname"><span class="i18n">E-Mail</span>:</div><div class="fieldcontrols">
<input type="text" name="email" size="50" /></div><br class="clearfloat" /><br /><br />

<div class="fieldname"><span class="i18n">Your city</span>:</div><div class="fieldcontrols">
<input type="text" name="location" size="50"{if isset($req->attrs->server.GEOIP_CITY)} value="{$req->attrs->server.GEOIP_CITY|escape}"{/if} /></div><br class="clearfloat" /><br /><br />

<div style="display:none"><div class="fieldname"><span class="i18n">CAPTCHA</span>:</div><div class="fieldcontrols"><div class="CAPTCHA"><span class="i18n">Loading CAPTCHA...</span></div></div><br class="clearfloat" /><br /><br />
</div>
<div class="additionalFields" style="display:none">
<div class="fieldname"><span class="i18n">Choose a password</span> (<span class="i18n">default is <span class="i18nArg generatePassword"></span></span> ):</div><div class="fieldcontrols">
<input type="password" name="password" size="50" autocomplete="off" /></div><br class="clearfloat" /><br /><br />

<div class="fieldname"><span class="i18n">Desired Login Name</span> (<span class="i18n">optional</span>):</div><div class="fieldcontrols">
<input type="text" name="username" size="50" /><div class="usernameAvailability i18n"></div></div><br class="clearfloat" /><br />
</div>

<div><a class="additionalFieldsButton i18n" href="#">Manual login information setup</a></div>
<br />

<div class="i18n">By clicking on 'I accept' below you are agreeing to the Terms of Service above and the Privacy Policy.</div>

<br /><button type="submit" class="i18n" disabled="disabled">I accept. Create my account.</button>
</form>
<br />

{getBlock name="PageEnd"}
