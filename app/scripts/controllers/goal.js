'use strict';

// 153,582
// 51,194 51194 in 9 years, 
// 255,970
// 5x current salary by 55, 29 years
// in 2043 saved 255970 
angular.module('sliceApp')
    .controller('GoalCtrl', function($scope, goal, transactions, $state) {
        $scope.goal = goal;
        $scope.transactions = transactions;

        /**
         * Deletes a goal
         * @param  {[type]} goal [description]
         * @return {[type]}      [description]
         */
        $scope.delete = function(goal) {
            // cancel
            goal.remove().then(function() {
                // Go to goals index
                $state.go('goals.index');
            });
        };

        $scope.cancel = function(goal) {
            // cancel
            goal.all('cancel').post({}).then(function() {
                // Go to goals index
                $state.go('goals.index');
            });
        };
    });
