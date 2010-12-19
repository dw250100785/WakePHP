{getBlock name="PageStart"}
<link href="/css/ACP.css" rel="stylesheet" type="text/css" /> 
<link href="/css/table.css" rel="stylesheet" type="text/css" /> 
<script src="/js/CmpAccountACPaccounts.js" type="text/javascript"></script>
<script src="/js/jquery.dataTables.js" type="text/javascript"></script>
<script src="/js/jquery.jeditable.js" type="text/javascript"></script>
<div class="AccountACPaccounts">

<h1 class="i18n heading">{$block->title}</h1>

<div class="tableWrapper">
<table cellpadding="0" cellspacing="0" border="0" class="display table"> 
	<thead> 
		<tr>
		{capture name="fields"}
			<th width="20%" class="i18n">E-Mail</th> 
			<th width="25%" class="i18n">Username</th> 
			<th width="25%" class="i18n">Reg. date</th> 
			<th width="15%" class="i18n">IP address</th> 
			<th width="15%" class="i18n">First name</th> 
			<th width="15%" class="i18n">Last name</th> 
			<th width="15%" class="i18n">Location</th> 
			<th width="15%" class="i18n sorting_disabled"></th> 
		{/capture}{$quicky.capture.fields}
		</tr> 
	</thead> 
	<tbody> 
		<tr> 
			<td colspan="5" class="dataTables_empty">Loading data from server</td> 
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