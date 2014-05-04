'use strict';
// Ionic Starter App, v0.9.20

// angular.module is a global place for creating, registering and retrieving Angular modules
// 'starter' is the name of this angular module example (also set in a <body> attribute in index.html)
// the 2nd parameter is an array of 'requires'
// 'starter.services' is found in services.js
// 'starter.controllers' is found in controllers.js
angular.module('pocketApp', ['ionic', 'Pocket.controllers', 'Pocket.services', 'restangular', 'angularMoment'])

// .run(function($ionicPlatform) {
//     $ionicPlatform.ready(function() {
//         StatusBar.styleDefault();
//     });
// })

.config(function($stateProvider, $urlRouterProvider, RestangularProvider) {

    RestangularProvider.setBaseUrl('slim/index.php');

    // Ionic uses AngularUI Router which uses the concept of states
    // Learn more here: https://github.com/angular-ui/ui-router
    // Set up the various states which the app can be in.
    // Each state's controller can be found in controllers.js
    $stateProvider.state('tab', {
        url: '/tab',
        abstract: true,
        templateUrl: 'templates/tabs.html'
    }).state('tab.pockets', {
        url: '/pockets',
        views: {
            'tab-pockets': {
                resolve: {
                    pockets: ['Restangular', '$filter',
                        function(Restangular, $filter) {
                            return Restangular.all('member/goals').getList().then(function(pockets) {
                                return $filter('filter')(pockets, function(pocket) {
                                    return pocket.spending !== null && (pocket.due || pocket.spending !== 0);
                                });
                            });
                        }
                    ],
                    user: ['Restangular',
                        function(Restangular) {
                            return Restangular.one('member/user').get();
                        }
                    ],
                },
                templateUrl: 'templates/tab-pockets.html',
                controller: 'PocketsCtrl'
            }
        }
    }).state('tab.pockets-detail', {
        url: '/pocket/:pocketId',
        views: {
            'tab-friends': {
                templateUrl: 'templates/friend-detail.html',
                controller: 'FriendDetailCtrl'
            }
        }
    }).state('tab.goals', {
        url: '/goals',
        views: {
            'tab-goals': {
                resolve: {
                    goals: function(Restangular, $filter) {
                        return Restangular.all('member/goals').getList().then(function(goals) {
                            return $filter('filter')(goals, function(goal) {
                                return goal.savings >= 0;
                            });
                        });
                    }
                },
                templateUrl: 'templates/tab-goals.html',
                controller: 'GoalsCtrl'
            }
        }
    }).state('tab.dash', {
        url: '/dash',
        views: {
            'tab-dash': {
                templateUrl: 'templates/tab-dash.html',
                controller: 'DashCtrl'
            }
        }
    }).state('tab.friends', {
        url: '/friends',
        views: {
            'tab-friends': {
                templateUrl: 'templates/tab-friends.html',
                controller: 'FriendsCtrl'
            }
        }
    }).state('tab.friend-detail', {
        url: '/friend/:friendId',
        views: {
            'tab-friends': {
                templateUrl: 'templates/friend-detail.html',
                controller: 'FriendDetailCtrl'
            }
        }
    }).state('tab.account', {
        url: '/account',
        views: {
            'tab-account': {
                templateUrl: 'templates/tab-account.html',
                controller: 'AccountCtrl'
            }
        }
    });

    // if none of the above states are matched, use this as the fallback
    $urlRouterProvider.otherwise('/tab/pockets');

});
