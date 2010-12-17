<span class="{$block.name}Left"><h2 class="i18n">WakePHP</h2></span>
<span class="{$block.name}Right">
{if $req->account.logged}<a href="#" class="logoutButton i18n">Logout</a> [{$req->account.email|escape}]
{else}<a href="/{$req->locale}/account/login" class="i18n">Log in</a> <span class="i18n">or</span> <a href="/{$req->locale}/account/signup" class="i18n">signup</a>
{/if}</span>