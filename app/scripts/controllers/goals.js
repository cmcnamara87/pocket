'use strict';

angular.module('pocketApp')
    .controller('GoalsCtrl', function($scope, goals) {
        $scope.goals = goals;
    });
