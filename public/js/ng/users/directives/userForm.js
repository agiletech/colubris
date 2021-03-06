/**
 * Created by vadym on 6/13/14.
 */

'use strict';

app_module.directive('userForm', function factory($q,$http, $templateCache,$compile) {
    return function(scope,element,attrs) {


        scope.$on( 'form.to_fixed_position', function( event, user, right ) {
            console.log('form.to_fixed_position');
            element.addClass('fixed');

            if(user){
                scope.User.getFromServer(user.id);
                scope.Right.getAllRights(user,'rights.update');
                if(app_module.current_user_rights.indexOf('can_manage_users') != -1){
                    scope.can_see_save_button = 'display:block!important;';
                }
            }
        });
        scope.$on( 'form.to_regular_place', function( event ) {
            console.log('form.to_fixed_position');
            element.removeClass('fixed');
        });
    }
})
;