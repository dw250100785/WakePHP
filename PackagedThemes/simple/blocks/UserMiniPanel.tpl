<span class="{$block.name}Left"><h2 class="i18n">WakePHP</h2></span>
<span class="{$block.name}Right">
{if $req->account.logged}<a href="/{$req->locale}/account/profile" class="i18n rightMargin">Profile</a>
<a href="#" class="logoutButton i18n">Logout</a> [{$req->account.email|escape}]
{else}<a href="/{$req->locale}/account/login{if isset($quicky.request.backurl)}?backurl={$quicky.requeststring.backurl|escape:'url'}{/if}" class="i18n">Log in</a> <span class="i18n">or</span> <a href="/{$req->locale}/account/signup{if isset($quicky.request.backurl)}?backurl={$quicky.requeststring.backurl|escape:'url'}{/if}" class="i18n">signup</a>
{/if}</span>