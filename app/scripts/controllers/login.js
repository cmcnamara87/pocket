'use strict';

angular.module('sliceApp')
    .controller('LoginCtrl', function($scope, $http, cfpLoadingBar, authService) {
        $scope.awesomeThings = [
            'HTML5 Boilerplate',
            'AngularJS',
            'Karma'
        ];

        $scope.isShowingLogin = false;
        $scope.credentials = {
            email: 'cmcnamara87@gmail.com',
            password: 'test'
        };

        $scope.$on('event:auth-loginRequired', function() {
            cfpLoadingBar.complete();
            $scope.isShowingLogin = true;
        });

        $scope.login = function(credentials) {
            cfpLoadingBar.start();
            $http.post('slim/index.php/login', credentials).then(function() {
                $scope.isShowingLogin = false;
                authService.loginConfirmed();
            }, function() {});
        };
    });
