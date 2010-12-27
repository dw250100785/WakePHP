{getBlock name="PageStart"}
<link href="/css/ACP.css" rel="stylesheet" type="text/css" /> 
<link href="/css/table.css" rel="stylesheet" type="text/css" /> 
<script src="/js/CmpAccountACPaccounts.js" type="text/javascript"></script>
<script src="/js/jquery/jquery.dataTables.js" type="text/javascript"></script>
<script src="/js/jquery/query.jeditable.js" type="text/javascript"></script>
<script src="/js/smartTable.js" type="text/javascript"></script>

<div class="{$block->name|escape}">

<h1 class="i18n heading">{$block->title|escape}</h1>

<div class="tableWrapper">
<table cellpadding="0" cellspacing="0" border="0" data-source="Account/ManageAccounts" class="display table"> 
	<thead> 
		<tr>
		{capture name="fields"}
			<th width="20%" class="i18n">E-Mail</th> 
			<th width="10%" class="i18n">Username</th> 
			<th class="i18n">Reg. date</th> 
			<th class="i18n">IP address</th> 
			<th class="i18n">First name</th> 
			<th class="i18n">Last name</th> 
			<th class="i18n">Location</th> 
			<th class="i18n">Groups</th> 
			<th class="i18n disabledSorting"></th> 
		{/capture}{$quicky.capture.fields}
		</tr> 
	</thead> 
	<tbody> 
		<tr> 
			<td colspan="5" class="dataTables_empty i18n">Loading data from server...</td> 
		</tr> 
	</tbody> 
	<tfoot> 
		<tr> 
			{$quicky.capture.fields}
		</tr> 
	</tfoot> 
</table> 
</div>
</div>

{getBlock name="PageEnd"}