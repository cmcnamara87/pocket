'use strict';

describe('Controller: TransactionCtrl', function() {

    // load the controller's module
    beforeEach(module('sliceApp'));

    var TransactionCtrl,
        scope, $rootScope, $state, Restangular, $httpBackend, baseUrl = 'slim/index.php';

    // Initialize the controller and a mock scope
    beforeEach(inject(function($controller, _$rootScope_, _$state_, _Restangular_, _$httpBackend_) {
        $rootScope = _$rootScope_;
        scope = $rootScope.$new();
        $state = _$state_;
        Restangular = _Restangular_;
        $httpBackend = _$httpBackend_;
        TransactionCtrl = $controller('TransactionCtrl', {
            $scope: scope,
            user: {
                userId: 1
            },
            transaction: {},
            goals: []
        });

        spyOn($state, 'go').andCallFake(function() {
            // This replaces the 'go' functionality for the duration of your test
        });
        // $httpBackend.expectGET(baseUrl + '/member/goals').respond(200, []);
        // $httpBackend.expectGET(baseUrl + '/member/user').respond(200, {
        // id: 1
        // });
    }));

    // it('should attach a list of awesomeThings to the scope', function() {
    //     expect(scope.awesomeThings.length).toBe(3);
    // });

    it('should provide a assignTransactionToSalary function', function() {
        expect(typeof scope.assignTransactionToSalary).toBe('function');
    });

    it('should set a transaction as salary', function() {
        var transaction = {
            id: 1
        };
        var rTransaction = Restangular.restangularizeElement(null, transaction, 'transactions');
        spyOn(rTransaction, 'put').andCallThrough();

        $httpBackend.expectPUT(baseUrl + '/transactions/1').respond(200, transaction);

        scope.assignTransactionToSalary(rTransaction);
        expect(rTransaction.account).toBe('salary');
        expect(rTransaction.put).toHaveBeenCalled();
        expect($state.go).toHaveBeenCalledWith('transactions.import');
    });

    it('should set a transaction as ignored', function() {
        var transaction = {
            id: 1
        };
        var rTransaction = Restangular.restangularizeElement(null, transaction, 'transactions');
        spyOn(rTransaction, 'put').andCallThrough();

        $httpBackend.flush();

        $httpBackend.expectPUT(baseUrl + '/transactions/1').respond(200, transaction);

        scope.ignoreTransaction(rTransaction);
        $httpBackend.flush();
        expect(rTransaction.account).toBe('ignore');
        expect(rTransaction.put).toHaveBeenCalled();

        expect($state.go).toHaveBeenCalledWith('transactions.import');
    });
});
