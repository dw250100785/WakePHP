{getBlock name="PageStart"}
<h1 class="i18n" xmlns="http://www.w3.org/1999/html">External authentication</h1>
<div ng-controller="ExtAuth" ng-init="init({$currentTokenId|json_encode|escape})">
	{literal}
	<div ng-repeat="request in requests | orderBy:'-ctime'"
		 ng-class="{'current_request':request.id==currentTokenId,'token_request':request.id!=currentTokenId}">{/literal}
		<div>[[request.useragent]] [[request.ip]]</div>
		<button ng-click="sendAnswer(request,'yes')">Yes</button>
		<button ng-click="sendAnswer(request,'no')">No</button>
		<button ng-click="sendAnswer(request,'not_sure')">Not sure</button>
	</div>
</div>

{getBlock name="PageEnd"}
