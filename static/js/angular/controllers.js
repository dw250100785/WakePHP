'use strict';

/* Controllers */
angular.module('bitfile.controllers', []).
	controller('ExtAuth', ['$scope', '$http', function ($scope, $http) {
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
			if (requests) {
				$scope.requests = angular.fromJson(requests);
			}
			else {
				$scope.requests = [
					{'id': 1, 'ip': '127.0.0.2', 'time': '11124'},
					{'id': 2, 'ip': '127.0.0.3', 'time': '11125'},
					{'id': 3, 'ip': '127.0.0.4', 'time': '11123'},
					{'id': 4, 'ip': '127.0.0.5', 'time': '11127'},
					{'id': 5, 'ip': '127.0.0.6', 'time': '11128'},
					{'id': 6, 'ip': '8.8.8.8', 'time': '11129'}
				];
			}
		};
	}])
	.controller('Dashboard', [function () {

	}]);