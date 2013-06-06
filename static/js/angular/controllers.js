'use strict';

/* Controllers */
angular.module('wakephp.controllers', []).
	controller('ExtAuth', ['$scope', '$http', function ($scope, $http) {
		$scope.requests = [];
		$scope.limit = 100;
		$scope.currentTokenId = 0;
		$scope.LC = $('html').attr('lang');

		$scope.sendAnswer = function (request, answer) {
			$http.put('/component/Account/ExtAuthManageRequests/json',
			          {'request_id': request.id, 'answer': answer}, {'params': {'LC': $scope.LC}});
			$scope.requests.splice($scope.requests.indexOf(request), 1);
		};

		$scope.init = function (currentTokenId) {
			$scope.currentTokenId = currentTokenId;
			$http.get('/component/Account/ExtAuthRequestsList/json', {'params': {'LC': $scope.LC, 'limit': $scope.limit}}).
				success(function (response) {
					        console.log(response);
					        $scope.requests = response;
				        });
		};
	}])
	.controller('Dashboard', [function () {

	}]);