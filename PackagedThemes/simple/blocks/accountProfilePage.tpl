{getBlock name="PageStart"}
<script src="/js/CmpAccountProfile.js" type="text/javascript"></script>
<script src="/js/jquery.capslock.js" type="text/javascript"></script>
<script src="http://jquery-ui.googlecode.com/svn/trunk/ui/i18n/jquery.ui.datepicker-{$req->locale}.js" type="text/javascript"></script>
<form action="Account/Profile" class="AccountProfileForm" method="post">

<h1 class="i18n">Profile</h1>


<div class="fieldname"><span class="i18n">Your city</span>:</div><div class="fieldcontrols">
<input type="text" name="location" size="50"{if isset($req->account.location)} value="{$req->account.location|escape}"{/if} /></div><br class="clearfloat" /><br /><br />

<div class="fieldname"><span class="i18n">First name</span>:</div><div class="fieldcontrols">
<input type="text" name="location" size="50"{if isset($req->account.firstname)} value="{$req->account.firstname|escape}"{/if} /></div><br class="clearfloat" /><br /><br />

<div class="fieldname"><span class="i18n">Last name</span>:</div><div class="fieldcontrols">
<input type="text" name="location" size="50"{if isset($req->account.lastname)} value="{$req->account.lastname|escape}"{/if} /></div><br class="clearfloat" /><br /><br />

<div class="fieldname"><span class="i18n">Gender</span>:</div><div class="fieldcontrols">

<input type="radio" name="gender" id="gender_male" value="male"{if isset($req->account.gender) && $req->account.gender == 'male'} checked{/if} />
<label for="gender_male" class="i18n">Male</label>

<input type="radio" name="gender" id="gender_female" value="female"{if isset($req->account.gender) && $req->account.gender == 'female'} checked{/if} />
<label for="gender_female" class="i18n">Female</label>

<input type="radio" name="gender" id="gender_na" value="" {if !isset($req->account.gender) || $req->account.gender == ''} checked{/if} />
<label for="gender_male" class="i18n">Not specified</label>

</div><br class="clearfloat" /><br /><br />

<div class="fieldname"><span class="i18n">Birthdate</span>:</div><div class="fieldcontrols">
<input type="text" name="birthdate" size="20"{if isset($req->account.bithdate)} value="{$req->account.birthdate|escape}"{/if} /></div><br class="clearfloat" /><br /><br />

<div class="fieldname"><span class="i18n">Subscription</span>:</div><div class="fieldcontrols">

<input type="radio" name="subscription" id="subscription_daily" value="male"{if isset($req->account.gender) && $req->account.gender == 'male'} checked{/if} />
<label for="subscription_daily" class="i18n">Daily</label>

<input type="radio" name="subscription" id="subscription_thematic" value="female"{if isset($req->account.gender) && $req->account.subscription == 'thematic'} checked{/if} />
<label for="subscription_thematic" class="i18n">Thematic</label>

<input type="radio" name="subscription" id="subscription_never" value="" {if !isset($req->account.subscription) || $req->account.subscription == ''} checked{/if} />
<label for="subscription_never" class="i18n">Never</label>

</div><br class="clearfloat" /><br /><br />


<div class="additionalFields" style="display:none">

<div class="fieldname"><span class="i18n">Current password</span>:</div><div class="fieldcontrols">
<input type="password" name="currentpassword" size="50" autocomplete="off" /></div><br class="clearfloat" /><span class="capslock" style="display:none">Caps Lock</span><br /><br />

<div class="fieldname"><span class="i18n">Choose a password</span>:</div><div class="fieldcontrols">
<input type="password" name="password" size="50" autocomplete="off" /></div><br class="clearfloat" />
<a class="i18n buttonGeneratePassword" href="#">Generate password</a><span style="display:none"> - <a class="i18n containerGeneratedPassword" href="#">...</a></span><br /><br /><br />
</div>

<div><a class="additionalFieldsButton i18n" href="#">Change a password</a></div>
<br />

<div class="i18n">By clicking on 'Saves the changes' below you are agreeing to the Terms of Service above and the Privacy Policy.</div>

<br /><button type="submit" class="i18n" disabled="disabled">Save the changes</button>
</form>
<br />

{getBlock name="PageEnd"}
