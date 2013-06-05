'use strict';

angular.module('bitfile', ['bitfile.filters', 'bitfile.services', 'bitfile.directives', 'bitfile.controllers']).
	config(function ($interpolateProvider) {
		       $interpolateProvider.startSymbol('[[').endSymbol(']]');
	       }
);
