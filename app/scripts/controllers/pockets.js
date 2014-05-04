'use strict';

angular.module('pocketApp')
    .controller('PocketsCtrl', function($scope, pockets, user, $ionicModal) {

        // Load a modal
        $ionicModal.fromTemplateUrl('templates/modal-sync.html', {
            scope: $scope,
            animation: 'slide-in-up'
        }).then(function(modal) {
            $scope.modal = modal;
        });

        // $ionicLoading.show({
        //     template: 'Loading...'
        // });
        $scope.pockets = pockets;
        $scope.user = user;

        $scope.sync = function() {
            $scope.modal.show();
        };
    });
