'use strict';

angular.module('sliceApp')
    .controller('PocketCtrl', function($scope, goal, transactions, $state) {
        $scope.goal = goal;
        $scope.transactions = transactions;

        $scope.empty = function(goal) {
            goal.all('empty').post({}).then(function() {
                // Go to goals index
                $state.go('goals.index');
            });
        };
    });
