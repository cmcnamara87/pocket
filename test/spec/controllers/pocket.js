'use strict';

describe('Controller: PocketCtrl', function() {

    // load the controller's module
    beforeEach(module('sliceApp'));

    var PocketCtrl,
        scope;

    // Initialize the controller and a mock scope
    beforeEach(inject(function($controller, $rootScope) {
        scope = $rootScope.$new();
        PocketCtrl = $controller('PocketCtrl', {
            $scope: scope
        });
    }));

    // it('should attach a list of awesomeThings to the scope', function() {
    //     expect(scope.awesomeThings.length).toBe(3);
    // });
});
