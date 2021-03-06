/**
 * Created by alf on 7/25/14.
 */

'use strict';

app_module.directive('colArchived', ['$compile', function($compile){
    return {
        restrict : 'A',
        scope : {
            colArchivedWatch : '='
        },
        compile : function() {

            return {
                post : function(scope, element, attributes){

                    scope.$watch('colArchivedWatch', function(newVal, oldVal){
                        if (element.attr('data') == 1) {
                            var html = '<span class="icon-archive"> Archived.</span>';
                        } else {
                            var html = '<span class="icon-flash"> Active.</span>';
                        }
                        var template = angular.element(html);
                        var linkFn = $compile(template);
                        var archived = linkFn(scope);
                        element.html(archived);
                    });
                }
            }
        }
    }
}]);