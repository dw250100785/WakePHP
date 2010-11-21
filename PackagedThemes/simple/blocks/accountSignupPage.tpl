{getBlock name="PageStart"}

<form action="Account/Signup" class="AccountSignupForm" method="post">

<h1 class="i18n">Account Registration</h1>

<div class="fieldname"><span class="i18n">Desired Login Name</span>:</div><div class="fieldcontrols">
<input type="text" name="username" size="50" /><div class="usernameAvailability i18n"></div></div><br class="clearfloat" /><br />


<div class="fieldname"><span class="i18n">Choose a password</span>:</div><div class="fieldcontrols">
<input type="password" name="password" size="50" autocomplete="off" /></div><br class="clearfloat" /><br /><br />

<div class="fieldname"><span class="i18n">E-Mail</span>:</div><div class="fieldcontrols">
<input type="text" name="email" size="50" /></div><br class="clearfloat" /><br /><br />


<div class="fieldname"><span class="i18n">CAPTCHA</span>:</div><div class="fieldcontrols"><div class="CAPTCHA"><span class="i18n">Loading CAPTCHA...</span></div></div><br class="clearfloat" /><br /><br />

<div class="fieldname"><span class="i18n">Terms of Service</span>:</div><div class="fieldcontrols">
<textarea cols="40" rows="5" onfocus="this.rows=10">...Terms here.....Terms here.....Terms here.....Terms here.....Terms here.....Terms here.....Terms here.....Terms here.....Terms here.....Terms here.....Terms here.....Terms here.....Terms here.....Terms here...</textarea>
<br /><br /><div class="i18n">By clicking on 'I accept' below you are agreeing to the Terms of Service above and the Privacy Policy.</div>
</div><br class="clearfloat" /><br /><br />

<br /><button type="submit" class="i18n" disabled="disabled">I accept. Create my account.</button>
</form>
<br />

{getBlock name="PageEnd"}
