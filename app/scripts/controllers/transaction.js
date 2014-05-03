'use strict';

angular.module('sliceApp')
    .controller('TransactionCtrl', function($scope, user, transaction, goals, $state) {
        $scope.transaction = transaction;
        $scope.goals = goals;
        $scope.user = user;

        $scope.assignTransactionToChange = function(transaction) {
            transaction.userId = user.id;
            transaction.account = 'change';
            transaction.put().then(function() {
                $state.go('transactions.import');
            });
        };

        $scope.assignTransactionToGoal = function(transaction, goal) {
            transaction.goalId = goal.id;
            transaction.account = 'spending';
            transaction.put().then(function() {
                $state.go('transactions.import');
            });
        };

        $scope.assignTransactionToSalary = function(transaction) {
            transaction.account = 'salary';
            transaction.put().then(function() {
                $state.go('transactions.import');
            });
        };

        $scope.ignoreTransaction = function(transaction) {
            transaction.account = 'ignore';
            transaction.put().then(function() {
                $state.go('transactions.import');
            });
        };
    });
