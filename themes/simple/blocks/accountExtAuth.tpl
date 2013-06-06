{getBlock name="PageStart"}
<h1 class="i18n" xmlns="http://www.w3.org/1999/html">External authentication</h1>
<div ng-controller="ExtAuth" ng-init="init({$currentTokenId|json_encode|escape})">
	<div ng-repeat="request in requests | orderBy:'ctime'"
			ng-class="getClass(request)">
		<div>[[request.ip]]</div>
		<button ng-click="yes(request)">Yes</button>
		<button ng-click="no(request)">No</button>
		<button ng-click="notSure(request)">Not sure</button>
	</div>
</div>

{getBlock name="PageEnd"}
