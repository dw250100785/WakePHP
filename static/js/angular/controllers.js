'use strict';

/* Controllers */
angular.module('bitfile.controllers', []).
	controller('ExtAuth', [function ($scope, $http) {
		$scope.requests = [];
		$scope.yes = function (request_id) {
			$http.put('/route/yes', {'request_id': request_id});
		};
		$scope.no = function (request_id) {
			$http.put('/route/no', {'request_id': request_id});
		};
		$scope.notSure = function (request_id) {
			$http.put('/route/notSure', {'request_id': request_id});
		};
		$scope.init = function (requests) {
			$scope.requests = angular.fromJson(requests);
		};
	}])
	.controller('Dashboard', [function () {

	}]);