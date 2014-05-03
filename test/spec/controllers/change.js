'use strict';

describe('Controller: ChangeCtrl', function() {

    // load the controller's module
    beforeEach(module('sliceApp'));

    var ChangeCtrl,
        scope;

    // Initialize the controller and a mock scope
    beforeEach(inject(function($controller, $rootScope) {
        scope = $rootScope.$new();
        ChangeCtrl = $controller('ChangeCtrl', {
            $scope: scope
        });
    }));

    // it('should attach a list of awesomeThings to the scope', function() {
    //     expect(scope.awesomeThings.length).toBe(3);
    // });
});
