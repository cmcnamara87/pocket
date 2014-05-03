'use strict';

angular.module('sliceApp')
    .controller('TransactionsImportCtrl', function($scope, transactions) {
        $scope.transactions = transactions;
    });
