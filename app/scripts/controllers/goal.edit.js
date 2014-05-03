'use strict';

angular.module('sliceApp')
    .controller('GoalEditCtrl', function($scope, goal, user, Restangular, $auth, $state) {
        // Setup today
        var today = new Date();
        today.setHours(0);
        today.setMinutes(0);
        today.setSeconds(0);

        function calculateAllocation(goal) {
            console.log('goal', goal);
            if (goal.due && goal.amount) {
                var start = today.getTime() / 1000;
                var due = goal.due;
                var daysBetween = Math.ceil((due - start) / (60 * 60 * 24));
                goal.$allocation = (goal.amount - (goal.savings - goal.daily)) / (daysBetween);

                console.log('allocation', goal.$allocation, goal.amount, goal.savings, $scope.originalAllocation, daysBetween);
            }
        }

        // Setup the 'new' goal
        goal.daily = goal.daily || 0;
        $scope.goal = goal;
        $scope.user = user;
        $scope.goal.$dueDate = new Date(goal.due * 1000);
        $scope.goal.$dueMin = new Date(today.getTime() + (60 * 60 * 24 * 1000));

        $scope.$watch('goal.$dueDate', function(dueDate) {
            if (dueDate) {
                goal.due = new Date(dueDate).getTime() / 1000;
                calculateAllocation($scope.goal);
            }
        });
        $scope.$watch('goal.amount', function() {
            calculateAllocation($scope.goal);
        });


        $scope.save = function(goal) {
            console.log('save?');
            // Are we updating or creating?
            if (goal.id) {
                // Updating
                console.log('goal', goal, $auth);
                goal.amount = parseFloat(goal.amount);
                goal.put().then(function() {
                    $state.go('goal.view', {
                        goalId: goal.id
                    });
                });
            }
        };
    });
