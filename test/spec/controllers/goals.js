'use strict';

describe('Controller: GoalsCtrl', function() {

    // load the controller's module
    beforeEach(module('pocketApp'));

    var GoalsCtrl,
        scope;

    // Initialize the controller and a mock scope
    beforeEach(inject(function($controller, $rootScope) {
        scope = $rootScope.$new();
        GoalsCtrl = $controller('GoalsCtrl', {
            $scope: scope,
            goals: []
        });
    }));

    it('should provide a goals property', function() {
        expect(scope.goals instanceof Array).toBe(true);
    });
});
