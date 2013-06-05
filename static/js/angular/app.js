'use strict';

angular.module('wakephp', ['wakephp.filters', 'wakephp.services', 'wakephp.directives', 'wakephp.controllers']).
	config(function ($interpolateProvider) {
		       $interpolateProvider.startSymbol('[[').endSymbol(']]');
	       }
);
