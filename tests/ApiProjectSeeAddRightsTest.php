<?php
class ApiProjectSeeAddRightsTest extends PHPUnit_Framework_TestCase {

    use Trait_Temp_Post;
    use Trait_Temp_Proxy;

    /**
     * Creates AgileToolkit application to user it in other tests
     */
    public function testAddApp()
    {
        $app = new App_CLI();
        $app->pathfinder->addLocation(array(
            'addons'=>array('atk4-addons','addons','vendor'),
            'php'=>array('shared','shared/lib','../lib'),
            'mail'=>array('templates/mail'),
        ))->setBasePath('.');
        $app->dbConnect();
        $app->page = '';
        $app->add('Auth')
            ->usePasswordEncryption('md5')
            ->setModel('Model_User', 'email', 'password')
        ;
        $app->is_test_app = true;
        return $app;
    }

    /**
     * Creates test user to use it with other tests.
     * This user must be deleted after tests are finished.
     *
     * @depends testAddApp
     */
    public function testCreateUser(App_CLI $app)
    {
        $this->app = $app;

        $user_hash = time();
        $m = $app->add('Model_Mock_User');
        $m
            ->set('name','TestUser_'.$user_hash)
            ->set('email','tu_'.$user_hash)
            ->set('password','123123')
            ->save()
        ;
        $this->current_user = $m;
        $app->addMethod('currentUser',function($user){return $this->current_user;});
        return $m;
    }

    /**
     * Add newly created user all permission for projects.
     *
     * @depends testAddApp
     * @depends testCreateUser
     */
    public function testCreatePermissions(App_CLI $app, Model_User $user)
    {
        $this->app = $app;

        $m = $app->add('Model_Mock_User_Right');
        //$m->set = true;
        $m
            ->set('user_id',$user['id'])
            ->set('right','can_see_projects,can_add_projects')
            ->save()
        ;

        return $m;
    }

    /**
     * Login to API with credentials of newly created user
     *
     * @depends testAddApp
     * @depends testCreateUser
     * @depends testCreatePermissions
     */
    public function testApiLogin(App_CLI $app, Model_User $user, Model_User_Right $rights)
    {
        $this->app = $app;

        $url = 'v1/auth/login/';
        $data = array('u'=>$user['email'],'p'=>'123123');
        $obj = json_decode($this->do_post_request($url,$data));

        // obj :: result
        $this->assertObjectHasAttribute('result',$obj,'No result is returned form API after login');
        $this->assertTrue(is_string($obj->result),'Result was converted not to string by json_encode()');
        $this->assertEquals($obj->result,'success','Result of login is not successful');

        // obj :: hash
        $this->assertObjectHasAttribute('hash',$obj,'');
        $this->assertTrue(is_a($obj->hash,'stdClass'),'Hash is not an object of class stdClass after convertation of API respond on user login');

        // obj :: hash :: lhash
        $this->assertObjectHasAttribute('lhash',$obj->hash,'No lhash is returned form API after login');
        $this->assertTrue(is_string($obj->hash->lhash),'lhash was converted not to string by json_encode()');

        // obj :: hash :: lhash_exp
        $this->assertObjectHasAttribute('lhash_exp',$obj->hash,'No lhash_exp is returned form API after login');
        $this->assertTrue(is_string($obj->hash->lhash_exp),'lhash_exp was converted not to string by json_encode()');

        return $obj;
    }

    /**
     * @depends testAddApp
     * @depends testCreateUser
     * @depends testCreatePermissions
     * @depends testApiLogin
     */
    public function testCreateProject(
        App_CLI $app, Model_User $user, Model_User_Right $rights, $login_res_success
    ) {
        $this->app = $app;

        $hash = time();
        $url = 'v1/project/saveParams&lhash='.$login_res_success->hash->lhash;
        $data = ['name' => 'TestProject_ApiProjectSeeAddRightsTest_'.$hash];
        $obj = json_decode($this->do_post_request($url,$data));

        // obj :: result
        $this->assertObjectHasAttribute('result',$obj,'No result is returned form API after creating a project');
        $this->assertTrue(is_string($obj->result),'Result was converted not to string by json_encode()');
        $this->assertEquals($obj->result,'success','Result of creating a project is not successful');

        // obj :: data
        $this->assertObjectHasAttribute('data',$obj,'No data is returned form API after creating a project');
        $this->assertTrue(is_a($obj->data,'stdClass'),'Data is not an object of class stdClass after convertation of API respond on creating a project');

        // obj :: data :: id
        $this->assertObjectHasAttribute('id',$obj->data,'Returned data form API doesn\'t have ID');

        return $obj;
    }

    /**
     * @depends testAddApp
     * @depends testApiLogin
     * @depends testCreateProject
     */
    public function testGetProject(
        App_CLI $app, $user_login_res, $create_object_res
    ) {
        $this->app = $app;

        $url = 'v1/project/getById&id='.$create_object_res->data->id.'&lhash='.$user_login_res->hash->lhash;
        $obj = json_decode($this->do_get_request($url));

        // obj :: result
        $this->assertObjectHasAttribute('result',$obj,'No result is returned form API after getting a project');
        $this->assertTrue(is_string($obj->result),'Result was converted not to string by json_encode()');
        $this->assertEquals($obj->result,'success','Result of getting a project is not successful');

        // obj :: data
        $this->assertObjectHasAttribute('data',$obj,'No data is returned form API after getting a project');
        $this->assertTrue(is_array($obj->data),'Data is not an array after convertation of API respond on getting a project');

        // obj :: data[0]
        $this->assertTrue(isset($obj->data[0]),'Data do not contain project');
        $this->assertTrue( (count($obj->data)==1),'There is more then one project in API respond on getting a project by ID');
        $this->assertTrue(is_a($obj->data[0],'stdClass'),'Data[0] is not an object of class stdClass after convertation of API respond on getting a project by ID');

        // obj :: data :: id
        $this->assertObjectHasAttribute('id',$obj->data[0],'Returned data form API doesn\'t have ID');

        return $obj;
    }

    /**
     * @depends testAddApp
     * @depends testCreateUser
     * @depends testCreatePermissions
     * @depends testApiLogin
     * @depends testGetProject
     */
    public function testUpdateProject(
        App_CLI $app, Model_User $user, Model_User_Right $rights, $user_login_res, $project_create_res
    ) {
        $this->app = $app;

        $hash = time();
        $new_name = 'TestProject_'.$hash.'_Updated';
        $url = 'v1/project/saveParams&id='.$project_create_res->data[0]->id.'&lhash='.$user_login_res->hash->lhash;
        $data = ['name' => $new_name];
        $obj = json_decode($this->do_post_request($url,$data));

        // obj :: result
        $this->assertObjectHasAttribute('result',$obj,'No result is returned form API after updating a project with no rights to update');
        $this->assertTrue(is_string($obj->result),'Result was converted not to string by json_encode()');
        $this->assertEquals($obj->result,'error','We updated the project while we have no rights to do this');

        // obj :: code
        $this->assertObjectHasAttribute('code',$obj,'No error code is returned form API after updating a Project');
        $this->assertTrue(is_string($obj->code),'Error code was converted not to string by json_encode()');
        $this->assertEquals($obj->code,'5312','Result of request has unexpected error "code" value');

        // obj :: message
        $this->assertObjectHasAttribute('message',$obj,'No error message is returned form API after updating a Project');
        $this->assertTrue(is_string($obj->message),'Error message was converted not to string by json_encode()');

        return $obj;
    }

    /**
     * @depends testAddApp
     * @depends testCreateUser
     * @depends testCreatePermissions
     * @depends testApiLogin
     * @depends testGetProject
     * @depends testUpdateProject
     */
    public function testDeleteProject(
        App_CLI $app, Model_User $user, Model_User_Right $rights, $user_login_res, $project_create_res, $project_update_res
    ) {
        $this->app = $app;

        $url = 'v1/project/deleteById&id='.$project_create_res->data[0]->id.'&lhash='.$user_login_res->hash->lhash;
        $obj = json_decode($this->do_get_request($url));

        // obj :: result
        $this->assertObjectHasAttribute('result',$obj,'No result is returned form API after deleting a project with no rights to delete');
        $this->assertTrue(is_string($obj->result),'Result was converted not to string by json_encode()');
        $this->assertEquals($obj->result,'error','We deleted the project while we have no rights to do this');

        // obj :: code
        $this->assertObjectHasAttribute('code',$obj,'No error code is returned form API after deleting a Project');
        $this->assertTrue(is_string($obj->code),'Error code was converted not to string by json_encode()');
        $this->assertEquals($obj->code,'5313','Result of request has unexpected error "code" value');

        // obj :: message
        $this->assertObjectHasAttribute('message',$obj,'No error message is returned form API after deleting a Project');
        $this->assertTrue(is_string($obj->message),'Error message was converted not to string by json_encode()');

        // obj :: deleted_record_id
        $this->assertFalse(isset($obj->deleted_record_id),'Property deleted_record_id was returned form API after deleting a project with no rights to delete');

        return $obj;
    }

    /**
     * @depends testAddApp
     * @depends testCreateUser
     * @depends testCreatePermissions
     * @ depends testApiLogin
     * @depends testCreateProject
     * @ depends testGetProject
     * @ depends testUpdateProject
     * @ depends testDeleteProject
     */
    public function testCleanDB(
        App_CLI $app, Model_User $user, Model_User_Right $rights, /*$login_res,*/
        $create_project_res/*, $get_project_res, $update_project_res, $delete_project_res*/
    ) {
        $this->app = $app;

        $project_id = $create_project_res->data->id;

        $this->app->add('Model_Project')->load($project_id)->forceDelete();
        $user->forceDelete();
        $rights->delete();
    }

}




/*


        try {
            $user->forceDelete();
        } catch (Exception $e) {
            echo $e->getMessage()."\n";
            echo $e->getFile()."\n";
            echo $e->getLine()."\n";
            echo $e->getTraceAsString();

        }


 */