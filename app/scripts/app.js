'use strict';

angular.module('sliceApp', [
    'ui.router',
    'ui.bootstrap',
    'restangular',
    'angularMoment',
    'ngSanitize',
    'ngAnimate',
    'chieffancypants.loadingBar',
    'http-auth-interceptor'

])
    .run(function($rootScope, $state, $stateParams) {
        $rootScope.$state = $state;
        $rootScope.$stateParams = $stateParams;
    })
    .config(function($stateProvider, $urlRouterProvider, RestangularProvider) {
        RestangularProvider.setBaseUrl('slim/index.php');

        $urlRouterProvider.otherwise('/');

        $stateProvider
            .state('register', {
                url: '/register',
                controller: 'RegisterCtrl',
                templateUrl: 'views/register.html'
            })
            .state('transactions', {
                abstract: true,
                url: '/transactions',
                template: '<ui-view></ui-view>'
            })
            .state('transactions.import', {
                url: '/import',
                resolve: {
                    transactions: ['Restangular',
                        function(Restangular) {
                            return Restangular.all('member/transactions/sync').getList();
                        }
                    ]
                },
                controller: 'TransactionsImportCtrl',
                templateUrl: 'partials/transactions.import.html'
            })
            .state('transactions.create', {
                url: '/create',
                resolve: {
                    transaction: function() {
                        return {};
                    }
                },
                controller: 'TransactionCtrl',
                templateUrl: 'partials/transaction.edit.html'
            })
            .state('transaction', {
                abstract: true,
                url: '/transaction/:transactionId',
                resolve: {
                    transaction: ['Restangular', '$stateParams',
                        function(Restangular, $stateParams) {
                            return Restangular.one('member/transactions', $stateParams.transactionId).get();
                        }
                    ],
                },
                template: '<ui-view></ui-view>'
            })
            .state('transaction.view', {
                url: '/view',
                controller: 'TransactionCtrl',
                templateUrl: 'partials/transaction.html',
            })
            .state('transaction.edit', {
                url: '/edit',
                resolve: {
                    user: ['Restangular',
                        function(Restangular) {
                            return Restangular.one('member/user').get();
                        }
                    ],
                    goals: ['Restangular', '$filter',
                        function(Restangular, $filter) {
                            return Restangular.all('member/goals').getList().then(function(goals) {
                                return $filter('filter')(goals, function(goal) {
                                    console.log('goal', goal);
                                    return goal.spending !== null;
                                });
                            });
                        }
                    ]
                },
                controller: 'TransactionCtrl',
                templateUrl: 'partials/transaction.edit.html',
            })
            .state('change', {
                url: '/change',
                resolve: {
                    user: ['Restangular',
                        function(Restangular) {
                            return Restangular.one('member/user').get();
                        }
                    ],
                    transactions: ['Restangular',
                        function(Restangular) {
                            return Restangular.all('member/transactions/change').getList();
                        }
                    ]
                },
                controller: 'ChangeCtrl',
                templateUrl: 'partials/change.html'
            })
            .state('goals', {
                abstract: true,
                url: '/',
                template: '<div ui-view autoscroll="false"></div>'
            })
            .state('goals.index', {
                url: '',
                resolve: {
                    goals: ['Restangular',
                        function(Restangular) {
                            return Restangular.all('member/goals').getList();
                        }
                    ],
                    user: ['Restangular',
                        function(Restangular) {
                            return Restangular.one('member/user').get();
                        }
                    ]
                },
                controller: 'GoalsCtrl',
                templateUrl: 'partials/goals.html'
            }).state('goals.create', {
                url: 'goals/create',
                resolve: {
                    goal: function() {
                        return {};
                    },
                    user: ['Restangular',
                        function(Restangular) {
                            return Restangular.one('member/user').get();
                        }
                    ]
                },
                controller: 'GoalCreateCtrl',
                templateUrl: 'partials/goal.edit.html'
            }).state('pocket', {
                url: '/pocket/:goalId',
                resolve: {
                    goal: ['Restangular', '$stateParams',
                        function(Restangular, $stateParams) {
                            return Restangular.one('member/goals', $stateParams.goalId).get();
                        }
                    ],
                    transactions: ['Restangular', '$stateParams', '$filter',
                        function(Restangular, $stateParams, $filter) {
                            return Restangular.one('member/goals', $stateParams.goalId).all('transactions').getList().then(function(transactions) {
                                return $filter('filter')(transactions, function(transaction) {
                                    return transaction.account === 'spending';
                                });
                            });
                        }
                    ]
                },
                templateUrl: 'views/pocket.html',
                controller: 'PocketCtrl'
            }).state('goal', {
                abstract: true,
                url: '/goal/:goalId',
                resolve: {
                    goal: ['Restangular', '$stateParams',
                        function(Restangular, $stateParams) {
                            return Restangular.one('member/goals', $stateParams.goalId).get();
                        }
                    ]
                },
                template: '<ui-view></ui-view>'
            }).state('goal.view', {
                url: '',
                resolve: {
                    transactions: ['Restangular', '$stateParams', '$filter',
                        function(Restangular, $stateParams, $filter) {
                            return Restangular.one('member/goals', $stateParams.goalId).all('transactions').getList().then(function(transactions) {
                                return $filter('filter')(transactions, function(transaction) {
                                    return transaction.account === 'savings';
                                });
                            });
                        }
                    ]
                },
                controller: 'GoalCtrl',
                templateUrl: 'partials/goal.html'
            }).state('goal.edit', {
                url: '/edit',
                resolve: {
                    user: ['Restangular',
                        function(Restangular) {
                            return Restangular.one('member/user').get();
                        }
                    ]
                },
                controller: 'GoalEditCtrl',
                templateUrl: 'partials/goal.edit.html'
            });
    });
