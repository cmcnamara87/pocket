'use strict';

describe('Controller: PocketsCtrl', function() {

    // load the controller's module
    beforeEach(module('pocketApp'));

    var PocketsCtrl,
        scope, StatusBar;

    // Initialize the controller and a mock scope
    beforeEach(inject(function($controller, $rootScope) {
        scope = $rootScope.$new();
        StatusBar = {
            styleDefault: function() {}
        };
        PocketsCtrl = $controller('PocketsCtrl', {
            $scope: scope,
            pockets: [],
            user: {}
        });
    }));

    it('should provide a pockets property', function() {
        expect(scope.pockets instanceof Array).toBe(true);
    });
    it('should provide a user property', function() {
        expect(scope.user instanceof Object).toBe(true);
    });
});
