'use strict';

angular.module('sliceApp')
    .controller('GoalsCtrl', function($scope, goals, user, $filter) {
        // Split the goals into now and future

        $scope.goals = {};
        $scope.user = user;
        $scope.goals.now = $filter('filter')(goals, function(goal) {
            if (goal.spending === null) {
                return false;
            }
            if (goal.spending === 0 && goal.due === null) {
                return false;
            }
            return true;
        });
        $scope.goals.now = $filter('orderBy')($scope.goals.now, function(goal) {
            if (goal.spending === 0) {
                return goal.due * 100;
            } else {
                return goal.due;
            }
            // its empty
            // its got cash
        });
        $scope.goals.future = $filter('filter')(goals, function(goal) {
            return goal.due;
        });

        console.log('goals', goals);

        // Work out if we havent synced for today
        var today = new Date();
        today.setHours(0);
        today.setMinutes(0);
        today.setSeconds(0);
        var todayTime = today.getTime() / 1000;
        $scope.isOld = todayTime > $scope.user.synced;

        // Stuff for change
        $scope.change = {};
        $scope.change.debtDayEstimate = Math.ceil(-1 * user.change / (user.dailyIncome - user.dailyIncomeAllocated));
        /*
            Anatomy of a goal
        { 
            name: 'Fuel', - string
            amount: 30, - float
            due: 1397206664, - int??
            repeat: 'week', - string
            spending: 25, - float
            savings: 28 - float
        }   
         */
    });
