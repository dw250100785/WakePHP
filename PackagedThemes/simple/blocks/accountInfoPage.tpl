{getBlock name="PageStart"}
<h1 class="i18n heading">{$block->title|escape}</h1> 
{if !$account}<div class="i18n">The specified Account was not found. It might have been deleted.</div>
{else}
<span class="i18n fieldname">Username</span>
<span class="fieldvalue">{$account.username|escape}</span>
{/if}

{getBlock name="PageEnd"}
