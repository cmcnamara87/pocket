'use strict';

describe('Controller: SyncCtrl', function() {

    // load the controller's module
    beforeEach(module('pocketApp'));

    var SyncCtrl,
        scope;

    // Initialize the controller and a mock scope
    beforeEach(inject(function($controller, $rootScope) {
        scope = $rootScope.$new();
        SyncCtrl = $controller('SyncCtrl', {
            $scope: scope
        });
    }));

    it('should exist', function() {
        expect( !! SyncCtrl).toBe(true);
    });

    describe('when created', function() {
        // Add specs
        it('should provide a boolean isLoading property', function() {
            expect(typeof scope.isLoading).toBe('boolean');
        });
        it('should provide a loadTransactions function', function() {
            expect(typeof scope.loadTransactions).toBe('function');
        });
        it('should load in new transactions', function() {
            expect(scope.isLoading).toBe(true);
        });
    });

    describe('when destroyed', function() {
        // Add specs
    });
});
