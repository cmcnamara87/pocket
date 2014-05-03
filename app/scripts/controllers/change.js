'use strict';

angular.module('sliceApp')
    .controller('ChangeCtrl', function($scope, user, transactions) {
        $scope.user = user;
        $scope.transactions = transactions;
    });
