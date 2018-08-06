(function () {
    var dateTimeController = function ($scope, $rootScope) {
        $scope.vm = {
            message: "Bootstrap DateTimePicker Directive",
            dateTime: {}
        };

        $scope.$watch('change', function(){
            console.log($scope.vm.dateTime);
        });


        /*
           $scope.$on('emit:dateTimePicker', function (e, value) {
           $scope.vm.dateTime = value.dateTime;
           console.log(value);
           })
        */
    };
    var dateTimePicker = function ($rootScope) {
        return {
            require: '?ngModel',
            restrict: 'AE',
            scope: {
                pick12HourFormat: '@',
                language: '@',
                useCurrent: '@',
                location: '@'
            },
            link: function (scope, elem, attrs) {
                elem.datetimepicker({
                    pick12HourFormat: scope.pick12HourFormat,
                    language: scope.language,
                    useCurrent: scope.useCurrent
                })

                //Local event change
                elem.on('blur', function () {

                    console.info('this', this);
                    console.info('scope', scope);
                    console.info('attrs', attrs);


                    /*// returns moments.js format object
                    scope.dateTime = new Date(elem.data("DateTimePicker").getDate().format());
                    // Global change propagation

                    $rootScope.$broadcast("emit:dateTimePicker", {
                        location: scope.location,
                        action: 'changed',
                        dateTime: scope.dateTime,
                        example: scope.useCurrent
                    });
                    scope.$apply();*/
                })
            }
        };
    }

    angular.module('dateTimeSandbox', []).run(['$rootScope', function ($rootScope) {
    }]).controller('dateTimeController', ['$scope', '$http', dateTimeController
    ]).directive('dateTimePicker', dateTimePicker);
})();