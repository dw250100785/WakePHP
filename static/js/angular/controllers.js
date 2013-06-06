'use strict';

/* Controllers */
angular.module('wakephp.controllers', []).
	controller('ExtAuth', ['$scope', '$http', function($scope, $http) {
		$scope.requests = [];
		$scope.currentTokenId = 0;
		$scope.yes = function(request) {
			$http.put('/route/yes', {'request_id': request.id});
			$scope.delRequestItem(request);
		};
		$scope.no = function(request) {
			$http.put('/route/no', {'request_id': request.id});
			$scope.delRequestItem(request);
		};
		$scope.notSure = function(request) {
			$http.put('/route/notSure', {'request_id': request.id});
			$scope.delRequestItem(request);
		};
		$scope.init = function(currentTokenId) {
			//$scope.currentTokenId = angular.fromJson(currentTokenId);
			console.log($scope.currentTokenId+' from '+currentTokenId);
//				$http.get('/route/get_requests');
			$scope.requests = [
				{'id': 1, 'ip': '127.0.0.2', 'ctime': '11124'},
				{'id': 2, 'ip': '127.0.0.3', 'ctime': '11125'},
				{'id': 3, 'ip': '127.0.0.4', 'ctime': '11123'},
				{'id': 4, 'ip': '127.0.0.5', 'ctime': '11127'},
				{'id': 5, 'ip': '127.0.0.6', 'ctime': '11128'},
				{'id': 6, 'ip': '8.8.8.8', 'ctime': '11129'}
			];
		};
		$scope.getClass = function(request) {
			if (request.id==$scope.currentTokenId)
			{
				return 'current_request';
			}
			else
			{
				return 'token_request';
			}
		};
		$scope.delRequestItem = function(request) {
			$scope.requests.splice($scope.requests.indexOf(request), 1);
		};
	}])
	.controller('Dashboard', [function() {

	}]);