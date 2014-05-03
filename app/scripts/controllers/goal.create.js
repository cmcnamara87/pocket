'use strict';

angular.module('sliceApp')
    .controller('GoalCreateCtrl', function($scope, goal, user, Restangular, $auth, $state) {
        $scope.goal = goal;
        $scope.user = user;

        var today = new Date();
        today.setHours(0);
        today.setMinutes(0);
        today.setSeconds(0);
        $scope.goal.$dueMin = new Date(today.getTime() + (60 * 60 * 24 * 1000));

        $scope.$watch('goal.$dueDate', function(dueDate) {
            if (dueDate) {
                goal.due = new Date(dueDate).getTime() / 1000;
                calculateAllocation();
            }
        });
        $scope.$watch('goal.amount', function() {
            calculateAllocation();
        });

        function calculateAllocation() {
            if (goal.due && goal.amount) {

                var start = today.getTime() / 1000;
                var due = goal.due;
                var daysBetween = Math.ceil((due - start) / (60 * 60 * 24));
                console.log('today is', daysBetween);
                goal.$allocation = goal.amount / daysBetween;
            }
        }

        $scope.save = function(goal) {
            // Creating
            console.log('goal', goal, $auth);
            goal.amount = parseFloat(goal.amount);
            Restangular.all('member/goals').post(goal).then(function() {
                $state.go('goals.index');
            });
        };
    });
