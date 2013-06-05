{getBlock name="PageStart"}
<h1 class="i18n" xmlns="http://www.w3.org/1999/html">External authentication</h1>
<div ng-controller="ExtAuth" ng-init="init()">
	<div ng-repeat="request in requests | orderBy:'time'">
		<div>[[request.ip]]</div>
		<br/>
		<button ng-click="yes(request.id)">Yes</button>
		<br/>
		<button ng-click="no(request.id)">No</button>
		<br/>
		<button ng-click="notSure(request.id)">Not sure</button>
	</div>
</div>

{getBlock name="PageEnd"}
