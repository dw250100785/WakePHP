{getBlock name="PageStart"}
<h1 class="i18n">External authentication</h1>
<div ng-controller="ExtAuth" ng-init="init({$authRequests|json_encode})">
	<div ng-repeat="request in requests | orderBy:'time'">
		<div>[[request.ip]]</div>
		<br/>
		<a href="" ng-click="yes(request.id)">Yes</a><br/>
		<a href="" ng-click="no(request.id)">No</a><br/>
		<a href="" ng-click="notSure(request.id)">Not sure</a>
	</div>
</div>

{getBlock name="PageEnd"}
