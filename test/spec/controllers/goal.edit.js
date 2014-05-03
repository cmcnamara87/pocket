'use strict';

describe('Controller: GoalEditCtrl', function() {

    // load the controller's module
    beforeEach(module('sliceApp'));

    var GoalEditCtrl,
        scope;

    // Initialize the controller and a mock scope
    beforeEach(inject(function($controller, $rootScope) {
        scope = $rootScope.$new();
        GoalEditCtrl = $controller('GoalEditCtrl', {
            $scope: scope
        });
    }));

    // it('should attach a list of awesomeThings to the scope', function() {
    //     expect(scope.awesomeThings.length).toBe(3);
    // });
});
