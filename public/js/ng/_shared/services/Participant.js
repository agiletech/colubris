/**
 * Created by vadym on 7/7/14.
 */

'use strict';

app_module.service( 'Participant', [ '$rootScope','$http', 'API', function( $rootScope, $http, API ) {
    var current_index = null;
    var service = {

        participants: [],

        getFromServerByProject: function(project, broadcast_message) {
            if(!project)return;
            service.getFromServerByProjectId(project.id,broadcast_message);
        },
        getFromServerByProjectId: function(project_id, broadcast_message) {
            if(!project_id)return;
            if (typeof broadcast_message == 'undefined') broadcast_message = 'participants.update';

            API.getAll(
                'participant',
                undefined,
                {field:'project_id',value:project_id},
                function(obj) {
                    service.participants = obj.data;
                    if (Array.isArray(broadcast_message)) {
                        $.each(broadcast_message,function(key,value) {
                            $rootScope.$broadcast( value, obj.data, project_id );
                        });
                    } else {
                        $rootScope.$broadcast( broadcast_message, obj.data, project_id );
                    }
                }
            );
        },

//        getFromServerByProject: function(project) {
//            API.getAll(
//                'participant',
//                undefined,
//                {field:'project_id',value:project.id},
//                function(obj) {
//                    service.participants = obj.data;
//                    //console.log(obj.data);
//                    $rootScope.$broadcast( 'participants.update', project );
//                }
//            );
//        },
        /*clear: function(){
            service.times = [];
            $rootScope.$broadcast( 'times.update' );
        },*/
        remove: function(id,project) {
            try {
                API.removeOne(
                    'participant',
                    'deleteById',
                    {id:id},
                    function(obj) {
                        if (obj.result === 'success') {
                            $rootScope.$broadcast( 'participants.update');
                            $rootScope.$broadcast('participants.need_update', project );
                        } else {
                            console.log(obj);
                            alert('Error! No success message received.');
                        }
                    }
                );
            } catch (e) {
                console.log(e);
                alert('Error! No data received.');
            }
        },
        save: function(participant,project) {
            console.log('-----> Save participant');
            API.saveOne(
                'participant',
                null,
                null,
                angular.toJson({"project_id":project.id,"user_id":participant.id}),
                function(obj) {
                    $rootScope.$broadcast('participants.need_update', project );
                }
            );
        },

        clear: function(){
            service.participants = [];
            $rootScope.$broadcast( 'participants.clear' );
        }
        /*backupTime: function(index) {
            this.current_index = index;
            service.times[index].backup = angular.copy(service.times[index]);
        },
        resetBackupTime: function() {
            if (this.current_index) {
                service.times[this.current_index].backup = {};
                this.current_index = null;
            }
        },
        restoreTime: function() {
            if (
                typeof service.times[this.current_index] !== 'undefined' &&
                typeof service.times[this.current_index].backup !== 'undefined'
            ) {
                service.times[this.current_index] = service.times[this.current_index].backup;
            }
        }*/
    };

  return service;
}]);