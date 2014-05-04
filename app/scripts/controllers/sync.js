'use strict';

angular.module('pocketApp')
    .controller('SyncCtrl', function($scope, Restangular) {
        $scope.isLoading = false;

        $scope.loadTransactions = function() {
            $scope.isLoading = true;
            Restangular.all('member/transactions/sync').getList().then(function(transactions) {
                $scope.isLoading = false;
                $scope.transactions = transactions;
            });
        };

        $scope.loadTransactions();
    });
