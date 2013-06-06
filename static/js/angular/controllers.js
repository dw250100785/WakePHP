'use strict';

/* Controllers */
angular.module('wakephp.controllers', []).
	controller('ExtAuth', ['$scope', '$http', function ($scope, $http) {
		$scope.requests = [];
		$scope.limit = 100;
		$scope.currentTokenId = 0;
		$scope.LC = $('html').attr('lang');
		$scope.yes = function (request) {
			$http.put('/route/yes', {'request_id': request.id});
			$scope.delRequestItem(request);
		};
		$scope.no = function (request) {
			$http.put('/route/no', {'request_id': request.id});
			$scope.delRequestItem(request);
		};
		$scope.notSure = function (request) {
			$http.put('/route/notSure', {'request_id': request.id});
			$scope.delRequestItem(request);
		};
		$scope.init = function (currentTokenId) {
			$scope.currentTokenId = currentTokenId;
			$http.get('/component/Account/ExtAuthRequestsList/json', {'params': {'LC': $scope.LC, 'limit': $scope.limit}}).
				success(function (response) {
					        console.log(response);
					        $scope.requests = response;
				        });
//			$scope.requests = [
//				{'id': 1, 'ip': '127.0.0.2', 'ctime': '11124'},
//				{'id': '51b06e3ff77ae87f577761fb', 'ip': '127.0.0.3', 'ctime': '11125'},
//				{'id': 3, 'ip': '127.0.0.4', 'ctime': '11123'},
//				{'id': 4, 'ip': '127.0.0.5', 'ctime': '11127'},
//				{'id': 5, 'ip': '127.0.0.6', 'ctime': '11128'},
//				{'id': 6, 'ip': '8.8.8.8', 'ctime': '11129'}
//			];
		};
		$scope.delRequestItem = function (request) {
			$scope.requests.splice($scope.requests.indexOf(request), 1);
		};
	}])
	.controller('Dashboard', [function () {

	}]);