'use strict';

angular.module('wakephp', ['bitfile.filters', 'bitfile.services', 'bitfile.directives', 'bitfile.controllers']).
	config(function ($interpolateProvider) {
		       $interpolateProvider.startSymbol('[[').endSymbol(']]');
	       }
);
