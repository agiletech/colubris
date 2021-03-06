/**
 * Created by vadym on 6/13/14.
 */

'use strict';

app_module.controller(
    'inlineCrud',
    ['$scope','$document','$http','Requirement','Comment','Task','Quote','User','Project','$filter',
        function ($scope,  $document,  $http,  Requirement,  Comment,  Task,  Quote,  User, Project, $filter) {

            // quote info
            $scope.Quote = Quote;
            Quote.getOneFromServer(app_module.quote_id);
            if(app_module.current_user_rights.indexOf('can_see_finance') != -1){
                //user can see money
                Quote.can_see_finance = 'display:block;';
            }else{
                Quote.can_see_finance = 'display:none;';
            }
            if(app_module.current_user_rights.indexOf('can_see_spent_time') != -1){
                //user can see spent time
                Quote.can_see_spent_time = 'display:block;';
            }else{
                Quote.can_see_spent_time = 'display:none;';
            }
            if(app_module.current_user_rights.indexOf('can_see_rates') != -1){
                //user can see spent time
                Quote.can_see_rates = 'display:block;';
            }else{
                Quote.can_see_rates = 'display:none;';
            }


            // Quote status
            $scope.quote_statuses = Quote.getStatuses('value','text');



            $scope.calc = {};

            // reqv
            $scope.reqv = {};
            $scope.Requirement = Requirement;
            $scope.requirements = Requirement.requirements;

            if(app_module.current_user_rights.indexOf('can_add_requirement') != -1){
                //Quote.can_add_requirement = 'display:block;';
            }else{
                Requirement.can_add_requirement = 'display:none;';
            }

            if(app_module.current_user_rights.indexOf('can_edit_requirement') != -1){
                //Quote.can_add_requirement = 'display:block;';
            }else{
                Requirement.can_edit_requirement = 'display:none;';
            }

            if(app_module.current_user_rights.indexOf('can_delete_requirement') != -1){
                //Quote.can_add_requirement = 'display:block;';
            }else{
                Requirement.can_delete_requirement = 'display:none;';
            }

            if(
                app_module.current_user_rights.indexOf('can_edit_requirement') == -1
                &&
                app_module.current_user_rights.indexOf('can_delete_requirement') == -1
            ){
                Requirement.show_actions = 'display:none;';
            }

            // comm
            $scope.Comment = Comment;
            $scope.comments = Comment.comments;
            $scope.actionButtonSet = {};

            // task
            $scope.Task = Task;
            $scope.tasks = Task.tasks;
            $scope.actionButtonSet = {};

            if(app_module.current_user_rights.indexOf('can_add_task') != -1){
                //Task.can_add_task = 'display:block;';
            }else{
                Task.can_add_task = 'display:none;';
            }


            // properties
            $scope.priorities = Task.getSorted('priorities','value','text');
            $scope.priority = $scope.priorities; // TODO why two variables with exactly same values

            // types
            $scope.types = Task.getSorted('types','value','text');
            $scope.type = $scope.types; // TODO why two variables with exactly same values

            // statuses
            $scope.statuses = Task.getSorted('statuses','value','text');
            $scope.status = $scope.statuses; // TODO why two variables with exactly same values

            // User
            $scope.User = User;

            // Requirements
            Requirement.getFromServer(Quote);




            $scope.$on( 'projects.participants.update', function( event ) {
                console.log('quote.one.inlineCrud.on.projects.participants.update');
                $scope.participants = $scope.Project.getSortedParticipants(0,'value','text');
                $scope.showTaskAssigned = function(task) {
                    var selected = $filter('filter')($scope.participants, {value: task.assigned_id});
                    return (task.assigned && selected.length) ? selected[0].text : 'Not set';
                };
                $scope.showTaskRequester = function(task) {
                    var selected = $filter('filter')($scope.participants, {value: task.requester_id});
                    return (task.requester && selected.length) ? selected[0].text : 'Not set';
                };

            });

            $scope.$on( 'requesters.update', function( event ) {
                $scope.requesters = User.users;
            });
            $scope.$on( 'assigneds.update', function( event ) {
                $scope.assigneds = User.users;
            });

            $scope.$on( 'comments.need_update', function( event, args ) {
                Comment.getFromServer($scope.Requirement.requirements[$scope.Requirement.current_index].id);
            });
            $scope.$on( 'reqv_tasks.update', function( event, args ) {
                $scope.showTaskStatus = function(task) {
                    var selected = $filter('filter')($scope.statuses, {value: task.status},true);
                    return (task.status && selected.length) ? selected[0].text : 'Not set';
                };
                $scope.showTaskType = function(task) {
                    var selected = $filter('filter')($scope.types, {value: task.type});
                    return (task.status && selected.length) ? selected[0].text : 'Not set';
                };
            });
            $scope.$on( 'tasks.need_update', function( event, args ) {
                Task.getFromServer($scope.Requirement.requirements[$scope.Requirement.current_index].id);
            });
            $scope.$on( 'task.clear', function( event ) {
                $scope.tasks = {};
                $('#task_view textarea, #task_view input').val('');
            });
            $scope.$on( 'comm.clear', function( event ) {
                $scope.comments = {};
                $('#comment_view textarea, #comment_view input').val('');
            });
            $scope.$on( 'tasks.need_update', function( event, args ) {
                Task.getFromServerByReqvId($scope.Requirement.requirements[$scope.Requirement.current_index].id);
            });
            $scope.$on( 'quote.update', function( event) {

                $scope.showStatus = function(quote) {
                    var selected = $filter('filter')($scope.quote_statuses, {value: quote.status});
                    return (quote.status && selected.length) ? selected[0].text : 'Not set';
                };

                $scope.quote = Quote.quotes;
                $scope.quote[0].deadline_obj = new Date($scope.quote[0].deadline);
                $scope.quote[0].warranty_end_obj = new Date($scope.quote[0].warranty_end);

                $scope.Project = Project;
                $scope.Project.withParticipants();
                Project.getFromServer('id',$scope.quote[0].project_id);

            });
            $scope.$on('projects.update', function(event){
                $scope.projects = Project.projects;
            });
            $scope.$on( 'reqv_tasks.update', function( event, args ) {
                $scope.tasks = args;
            });
            $scope.$on( 'reqv.update', function( event, args ) {
                $scope.reqv = args;
                User.getFromServerByProjectId(args.project_id,['requesters.update','assigneds.update']);
            });
            $scope.$on( 'requirements.reload', function( event ) {
                $scope.requirements = Requirement.requirements;
                Requirement.getFromServer();
            });
            $scope.$on( 'requirements.update', function( event ) {
                console.log('requirements.update - start');
                $scope.requirements = Requirement.requirements;
                $scope.calc.net = 0;
                $scope.calc.spent = 0;
                $.each($scope.requirements,function(key,value){
                    if(value.is_included == 1 && value.estimate!=null){
                        $scope.calc.net = $scope.calc.net + parseFloat(value.estimate);
                    }
                    if(parseFloat(value.spent_time)){
                        $scope.calc.spent = $scope.calc.spent + parseFloat(value.spent_time);
                    }
                });
                $scope.calc.subtotal = $scope.calc.net;
                $scope.calc.pm = Math.ceil($scope.calc.subtotal/5);
                $scope.calc.total = $scope.calc.subtotal + $scope.calc.pm;
                $scope.calc.spent = Math.ceil($scope.calc.spent);
                if($scope.calc.spent > $scope.calc.total){
                    $scope.calc.color = 'atk-effect-danger';
                }else{
                    $scope.calc.color = 'atk-effect-success';

                }

                $('#sortable-reqirements').sortable({
                    placeholder: "ui-state-highlight",
                    items: 'tr',
                    cursor: "move"
                });

                console.log('requirements.update - end');

            });
            $scope.$on( 'comments.update', function( event ) {
                $scope.comments = Comment.comments;
                $.each($scope.comments,function(key,value) {
                    $.each(value,function(key2,value2) {
                        if(key2 == 'user_avatar_thumb'){
                            if(value2 == null){
                                $scope.comments[key][key2] = app_module.base_url + 'images/no-user-image.gif';
                            }else{
                                $scope.comments[key][key2] = app_module.base_url + 'upload/' + value2;
                            }
                        }
                        if(key2 == 'file_thumb'){
                            if(value2 != null){
                                $scope.comments[key][key2] = 'http://' + document.domain + window.location.pathname + value2;
                            }
                        }
                    });
                });
            });
            //    $scope.$on( 'tasks.update', function( event ) {
            //        $scope.tasks = Task.tasks;
            //    });

            // ESC key close requirement div
            $(document).on('keydown', function(evt){
                evt = evt || window.event;
                if (evt.keyCode == 27) {
                    $('#close-button').trigger('click');
                }
            });

            $scope.toggle = function(show,hide) {
                $('#'+show).removeClass('ui-helper-hidden');
                $('#'+hide).addClass('ui-helper-hidden');
            };

            $scope.toggleIsIncluded = function(args){
                var reqv = angular.copy(args);
                reqv.is_included = !reqv.is_included;
                args = reqv;
                Requirement.save(args);



                ///   floating total
                if(args.is_included == true){
                    $scope.calc.net = $scope.calc.net + parseFloat(args.estimate);
                }else{
                    $scope.calc.net = $scope.calc.net - parseFloat(args.estimate);
                }
                $scope.calc.subtotal = $scope.calc.net;
                $scope.calc.pm = Math.ceil($scope.calc.subtotal/5);
                $scope.calc.total = $scope.calc.subtotal + $scope.calc.pm;
                var reqv_cloned = angular.copy( args);
            }
        }])
;