/**
 * Created by alf on 7/25/14.
 */

'use strict';

app_module.service( 'Right', [ '$rootScope','$http','API', function( $rootScope, $http, API ) {
    var current_index = null;
    var service = {
        rights: [],
        all_rights:[],

        getFromServer: function(broadcast_message) {


            API.getAll(
                'right',
                undefined,
                undefined,
                function(obj) {
                    service.rights = obj.data;
                    $rootScope.$broadcast( broadcast_message );
                }
            );
        },
        getAllRights: function(user,broadcast_message) {
            console.log('-----> getAllRights()---START');
            var that = this;
            API.getOne(
                'right',
                'getAvailableRights',
                undefined,
                function(obj) {
                    if (obj.result === 'success') {
                        that.getForUser(user,broadcast_message, obj.data);
                    } else {
                        alert('Error! getAllRights method failed');
                    }
                }
            );
            console.log('-----> getAllRights()---END');
        },
        getForUser: function(user,broadcast_message, all_rights) {
            console.log('-----> getForUser()---START');
            var that = this;
            API.getOne(
                'right',
                'getByField',
                {field: 'user_id', value:user.id},
                function(obj) {
                    var data;
                    data = that.prepareArray(obj,all_rights,user.id);
                    service.rights = data;
                    $rootScope.$broadcast( broadcast_message );
                }
            );
            console.log('-----> getForUser()---END');
        },
        prepareArray: function(obj,all_rights,user_id){
            console.log('-----> prepareArray()---START');
            var object = {};
            var rights_obj = {};
            object.user_id = user_id;

            //If no record in database at all
            if(!obj.data[0]){
                $.each(all_rights, function(index, value) {
                    rights_obj[index] = [value,false];
                });
                object.data = rights_obj;
                return object;
            }

            var can_rights = obj.data[0].right.split(',');
            $.each(all_rights, function(index, value) {
                if(can_rights.indexOf(value) === -1){
                    rights_obj[index] = [value,false];
                }else{
                    rights_obj[index] = [value,true];
                }
            });

            object.id = obj.data[0].id;
            object.data = rights_obj;
            console.log('-----> prepareArray()---END');
            return object;
        },
        save: function ( right ) {

            console.log('------>save()---START');
            var t = angular.copy(right);
            if (typeof t.user_id === 'undefined' ) {
                service.rights.push( jQuery.extend({}, t)  );
            } else {
                // send new data to the server
            }

            this.saveOnServer(t);
            $rootScope.$broadcast('right.update', {});
            $rootScope.$broadcast('rights.update' );
            $rootScope.$broadcast('form.to_regular_place');

            this.resetBackupRight();
            console.log('------>save()---END');
        },
        saveOnServer: function(right) {
            console.log('------>saveOnServer()---START');
            var that = this;
            var arr = that.mergeArray(right.data);
            var user_id = right.user_id;
            API.saveOne(
                'right',
                'setRights',
                {id: right.id},
                angular.toJson({user_id:user_id,right:arr}),
                function(obj) {
                    if (obj.result === 'success') {
                        $rootScope.showSystemMsg('Saved successfully');
                    } else {
                        $rootScope.showSystemMsg('Error! No success message received');
                    }
                }
            );
            console.log('------>saveOnServer()---END');
        },
        mergeArray: function(arr){
            console.log('------>mergeArray()---START');

            var new_arr = [];
            $.each(arr, function(index, value) {
                if(value[1]){
                    new_arr.push(value[0]);
//                    new_arr[] = value[0];
                }
            });
            console.log('------>mergeArray()---END');
            return new_arr;
        },
        remove: function(index) {
            service.rights.splice(index, 1);
            this.saveOnServer();
            $rootScope.$broadcast( 'rights.update' );
        },
        edit: function(index) {
            console.log('------> edit');
            this.backupUser(index);
            $rootScope.$broadcast('right.update', service.rights[index]);
            $rootScope.$broadcast('form.to_fixed_position',service.rights[index]);
        },
        backupUser: function(index) {
            current_index = index;
            service.rights[index].backup = jQuery.extend({}, service.rights[index]);
//            console.log(service.rights[current_index].backup);
        },
        cancel: function() {
            this.restoreRight();
            this.resetBackupRight();
            $rootScope.$broadcast('right.update', {});
            $rootScope.$broadcast( 'rights.update' );
            $rootScope.$broadcast('form.to_regular_place');
        },
        clear: function(){
            service.rights = [];
            $rootScope.$broadcast( 'rights.update' );
        },
        resetBackupRight: function() {
            if (current_index) {
                service.rights[current_index].backup = {};
                current_index = null;
            }
        },
        restoreRight: function() {
            if (
                typeof service.rights[current_index] !== 'undefined' &&
                    typeof service.rights[current_index].backup !== 'undefined'
                ) {
                service.rights[current_index] = service.rights[current_index].backup;
            }
        }
    }

    return service;
}]);