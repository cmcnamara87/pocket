'use strict';

describe('Controller: GoalCreateCtrl', function() {

    // load the controller's module
    beforeEach(module('sliceApp'));

    var GoalCreateCtrl,
        scope;

    // Initialize the controller and a mock scope
    beforeEach(inject(function($controller, $rootScope) {
        scope = $rootScope.$new();
        GoalCreateCtrl = $controller('GoalCreateCtrl', {
            $scope: scope
        });
    }));

    // it('should attach a list of awesomeThings to the scope', function() {
    //     expect(scope.awesomeThings.length).toBe(3);
    // });
});
