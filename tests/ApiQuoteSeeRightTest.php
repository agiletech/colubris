<?php
class ApiQuoteSeeRightTest extends PHPUnit_Framework_TestCase {

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
     * Add newly created user some permission to see quotes only.
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
            ->set('right','can_see_quotes')
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
        $m = $app->add('Model_Project');
        $m['name'] ='TestProject_ApiQuoteSeeRightTest_'.$hash;
        $m->save();

        // obj :: data :: id
        $this->assertObjectHasAttribute('id',$m,'Saved Project doesn\'t have ID');
        $this->assertTrue(!is_null($m->id),'Saved Project doesn\'t have ID');

        return $m;
    }

    /**
     * @depends testAddApp
     * @depends testCreateUser
     * @depends testCreatePermissions
     * @depends testApiLogin
     * @depends testCreateProject
     */
    public function testCreateQuote(
        App_CLI $app, Model_User $user, Model_User_Right $rights, $login_res_success, Model_Project $project
    ) {
        $this->app = $app;

        $hash = time();
        $url = 'v1/quote/saveParams&lhash='.$login_res_success->hash->lhash;
        $data = [
            'name'       => 'TestQuote_ApiQuoteSeeRightTest_'.$hash,
            'project_id' => $project->id,
        ];
        $obj = json_decode($this->do_post_request($url,$data));

        // obj :: result
        $this->assertObjectHasAttribute('result',$obj,'No result is returned form API after creating a quote');
        $this->assertTrue(is_string($obj->result),'Result was converted not to string by json_encode()');
        $this->assertEquals($obj->result,'error','Result of request has unexpected "result" value');

        // obj :: code
        $this->assertObjectHasAttribute('code',$obj,'No code is returned form API after creating a quote');
        $this->assertTrue(is_string($obj->code),'Code was converted not to string by json_encode()');
        $this->assertEquals($obj->code,'5311','Result of request has unexpected "code" value');

        // obj :: message
        $this->assertObjectHasAttribute('message',$obj,'No message is returned form API after creating a quote');
        $this->assertTrue(is_string($obj->message),'Message was converted not to string by json_encode()');

        return $obj;
    }

    /**
     * @depends testAddApp
     * @depends testApiLogin
     * @depends testCreateProject
     */
    public function testGetQuote(
        App_CLI $app, $user_login_res, $project
    ) {
        $this->app = $app;

        // create quote
        $hash = time();
        $q = $app->add('Model_Quote');
        $q
            ->set('name','TestQuote_ApiQuoteSeeRightTest_'.$hash)
            ->set('project_id',$project->id)
            ->save()
        ;
        $this->assertObjectHasAttribute('id',$q,'Saved Quote doesn\'t have ID');
        $this->assertTrue(!is_null($q->id),'Saved Quote doesn\'t have ID');

        $url = 'v1/quote/getById&id='.$q->id.'&lhash='.$user_login_res->hash->lhash;
        $obj = json_decode($this->do_get_request($url));

        // obj :: result
        $this->assertObjectHasAttribute('result',$obj,'No result is returned form API after getting a quote');
        $this->assertTrue(is_string($obj->result),'Result was converted not to string by json_encode()');
        $this->assertEquals($obj->result,'success','Result of getting a quote is not successful');

        // obj :: data
        $this->assertObjectHasAttribute('data',$obj,'No data is returned form API after getting a quote');
        $this->assertTrue(is_array($obj->data),'Data is not an array after convertation of API respond on getting a quote');

        // obj :: data[0]
        $this->assertTrue(isset($obj->data[0]),'Data do not contain quote');
        $this->assertTrue( (count($obj->data)==1),'There is more then one quote in API respond on getting a quote by ID');
        $this->assertTrue(is_a($obj->data[0],'stdClass'),'Data[0] is not an object of class stdClass after convertation of API respond on getting a quote by ID');

        // obj :: data :: id
        $this->assertObjectHasAttribute('id',$obj->data[0],'Returned data form API doesn\'t have ID');

        return $q;
    }

    /**
     * @depends testAddApp
     * @depends testCreateUser
     * @depends testCreatePermissions
     * @depends testApiLogin
     * @depends testGetQuote
     */
    public function testUpdateQuote(
        App_CLI $app, Model_User $user, Model_User_Right $rights, $user_login_res, Model_Quote $quote
    ) {
        $this->app = $app;

        $hash = time();
        $new_name = 'TestQuote_'.$hash.'_Updated_'.$hash;
        $url = 'v1/quote/saveParams&id='.$quote->id.'&lhash='.$user_login_res->hash->lhash;
        $data = ['name' => $new_name];
        $obj = json_decode($this->do_post_request($url,$data));

        // obj :: result
        $this->assertObjectHasAttribute('result',$obj,'No result is returned form API after updating a quote');
        $this->assertTrue(is_string($obj->result),'Result was converted not to string by json_encode()');
        $this->assertEquals($obj->result,'error','Result of request has unexpected "result" value');

        // obj :: code
        $this->assertObjectHasAttribute('code',$obj,'No code is returned form API after updating a quote');
        $this->assertTrue(is_string($obj->code),'Code was converted not to string by json_encode()');
        $this->assertEquals($obj->code,'5312','Result of request has unexpected "code" value');

        // obj :: message
        $this->assertObjectHasAttribute('message',$obj,'No message is returned form API after updating a quote');
        $this->assertTrue(is_string($obj->message),'Message was converted not to string by json_encode()');

        return $obj;
    }

    /**
     * @depends testAddApp
     * @depends testCreateUser
     * @depends testCreatePermissions
     * @depends testApiLogin
     * @depends testGetQuote
     * @depends testUpdateQuote
     */
    public function testDeleteQuote(
        App_CLI $app, Model_User $user, Model_User_Right $rights, $user_login_res, Model_Quote $quote, $quote_update_res
    ) {
        $this->app = $app;

        $url = 'v1/quote/deleteById&id='.$quote->id.'&lhash='.$user_login_res->hash->lhash;
        $obj = json_decode($this->do_get_request($url));

        // obj :: result
        $this->assertObjectHasAttribute('result',$obj,'No result is returned form API after deleting a quote');
        $this->assertTrue(is_string($obj->result),'Result was converted not to string by json_encode()');
        $this->assertEquals($obj->result,'error','Result of request has unexpected "result" value');

        // obj :: code
        $this->assertObjectHasAttribute('code',$obj,'No code is returned form API after deleting a quote');
        $this->assertTrue(is_string($obj->code),'Code was converted not to string by json_encode()');
        $this->assertEquals($obj->code,'5313','Result of request has unexpected "code" value');

        // obj :: message
        $this->assertObjectHasAttribute('message',$obj,'No message is returned form API after deleting a quote');
        $this->assertTrue(is_string($obj->message),'Message was converted not to string by json_encode()');

        return $obj;
    }

    /**
     * @depends testAddApp
     * @depends testCreateUser
     * @depends testCreatePermissions
     * @ depends testApiLogin
     * @depends testCreateProject
     * @depends testCreateQuote
     * @depends testGetQuote
     * @depends testUpdateQuote
     * @depends testDeleteQuote
     */
    public function testCleanDB(
        App_CLI $app, Model_User $user, Model_User_Right $rights, /*$login_res,*/
        $test_project,$create_quote_res, Model_Quote $quote, $update_res, $delete_res
    ) {

        $this->app = $app;

        $quote->forceDelete();
        $test_project->forceDelete();
        $user->forceDelete();
        $rights->delete();
    }

}