<?php defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Example
 *
 * This is an example of a few basic user interaction methods you could use
 * all done with a hardcoded array.
 *
 * @package		CodeIgniter
 * @subpackage	Rest Server
 * @category	Controller
 * @author		Phil Sturgeon
 * @link		http://philsturgeon.co.uk/code/
*/

// This can be removed if you use __autoload() in config.php OR use Modular Extensions
require APPPATH.'/libraries/REST_Controller.php';
require APPPATH.'/libraries/AfricasTalkingGateway.php';
require APPPATH.'/libraries/OAuth.php';

class Flexipay_server extends REST_Controller
{
    /*function __construct(){
        //Constructor Code
        //Not Working find out ---> should construct the loading of the model
    }*/

	function user_get()
    {

        if(!$this->get('id'))
        {
        	$this->response(NULL, 400);
        }

        // $user = $this->some_model->getSomething( $this->get('id') );
        if($user)
        {
            $this->response($user, 200); // 200 being the HTTP response code
        }

        else
        {
            $this->response(array('error' => 'User could not be found'), 404);
        }

    }
    
    function user_post()
    {  
        $this->load->model('EzAuth_Model', 'ezauth');
        $this->ezauth->program = 'FlexiPay';     
        $inp = array(
            'ez_users'  =>  array(
                 'first_name'     =>  $this->post('first_name'),      //  **  not a default ezauth field!
                 'last_name'      =>  $this->post('last_name'),       //  **  not a default ezauth field!
                 'email'         =>  $this->post('email') ,          //  **  only required if using verification
                 'mobile_number' =>  $this->post('mobile_number') , //  **  only required if using verification
            ),
            'passcode'   =>  $this->post('passcode'),
            'account_id' =>  $this->post('account_id'), // This is the account-type that the user wants to open  
             
             'merchant_description'  => array(
                    'business_id' =>  $this->post('business_id'),      
                    'business_name' =>$this->post('business_name'),
                    'business_location'=> $this->post('business_location'),
                    'business_address' => $this->post('business_address'),
                ),

             'agent_description'  => array(
                    'business_name' =>$this->post('business_name'),
                    'business_location'=> $this->post('business_location'),
                    'business_address' => $this->post('business_address'),
                )
        );
                   
        $verify_yesno = true; //set to verify the phone_number of the user;

        //Logic for Access Keys
        //If merchant--->means(merchant program)---> hence give a merchant user access key and so on
        if($inp['account_id']== 1) //Merchant Access
        {
            $inp['ez_access_keys']['merchants_board']='merchant_user';
        }
        else if($inp['account_id']== 2) //Agent Access Key
        {
            $inp['ez_access_keys']['agents_board']='agent_user';
        }
        else{
            $inp['ez_access_keys']['customer_board']='customer_user';
        }

        $user_reg = $this->ezauth->register($inp, $verify_yesno); //verify parameter set to true, so verification code will be returned, which can be sent to user
        
        if ($user_reg['reg_ok'] == 'yes') {        
            $v_code = $user_reg['code'];
            $user_id= $user_reg['user_id'];

            //  send user e-mail with verification code.
            /*
            $message_email = '<p>This e-mail address was used to sign up to FlexiPay. To begin using Flexipay, you must verify your e-mail
            address by entering this verification code to your application. The verification code is:-'.$v_code;
    
             $this->_send_mail($inp['ez_users']['email'], 'Verify your e-mail address!', $message_email);
            */

            $message_sms = 'Your FlexiPay verification code is '.strtoupper($v_code). '.Open FlexiPay and Enter this code. Thank-you';
            
            $this->_send_sms('+'.$inp['ez_users']['mobile_number'],  $message_sms);  

            $data['success'] = true; 
            $data['v_code']=$v_code;
            $this->response($data, 200); // 200 being the HTTP response code
        }

        else {
            $user_reg['success'] = false; 
            $this->response($user_reg, 400); 
         }
    }
    
    function user_delete()
    {
    	//$this->some_model->deletesomething( $this->get('id') );
        $message = array('id' => $this->get('id'), 'message' => 'DELETED!');
        
        $this->response($message, 200); // 200 being the HTTP response code
    }
       

//-------------customer dashboard --------------------
    function customer_dashboard() {
    //Logic for customer--- 
    //1. Authorise the user for specific actions
        $auth = $this->ezauth->authorize($method, true);
        if ($auth['authorize'] == true) {
            //  redirect with method arguments
            $this->response($users, 200); // 200 being the HTTP response code
        } else {
        // user login information incorrect, so show login screen again
            $this->response("Not Authorised: Need to Login", 404);
        }


    //2. Load all transactions for this user

    }

    function merchant_dashboard() {
    //1. Load all transactions for this user

    }
    
     function agent_dashboard() {
    //1. Load all transactions for this user

    //2.
    }   
    
    //TODO:::Later Implement a functionality for the admin user.
    function admin() {
    }


//----------Key functions In System----------------------   
    function external_top_up($method,$amount, $user_id){
        //--Top-up Via Pesa-Pal ---> This would need to function call to PesaPal     
        //--Top-up via M-PESA

        //Top-up via Airtel


        //Top-up via credit-card

    }
//--------- Normal Transaction from the system ----------------
    function do_transaction_post($sender_id='', $recepient_id='', $amount=''){
    //Take money from Account with sender_id to Account with recepient_id
        $this->load->model('EzAuth_Model', 'ezauth');
        $this->ezauth->program = 'FlexiPay';     

        $inp= array(
            'sender_id' => $this->post('sender_id'),
            'recepient_id' => $this->post('recepient_id'),
            'amount' => $this->post('amount'),
            'transaction_type' => $this->post('transaction_type')
            );

        $response = $this->ezauth->transaction($inp); //verify parameter set to true, so verification code will be returned, which can be sent to user
       
    
      if ($response['success']){
        $this->response($response, 200); // 200 being the HTTP response code
// Take user to the Necessary page
        } else {
             $this->response($response, 404);//Reject code
        }
    }

//--------- PesaPal Transactions ----------------
    function pesapal_post($sender_id='', $recepient_id='', $amount=''){
        //pesapal params
        $token = $params = NULL;

        $consumer_key = 'NpQGkKRXdyEfAlMA5ouaE6Wm8MPh9Oge';//Register a merchant account on                 
        $consumer_secret = '7vZehCW2JpTL3m7A14Of3qUzIJs=';// Use the secret from your test
        $signature_method = new OAuthSignatureMethod_HMAC_SHA1();
        $iframelink = 'http://demo.pesapal.com/api/PostPesapalDirectOrderV4';//change to      
                     //https://www.pesapal.com/API/PostPesapalDirectOrderV4 when you are ready to go live!

        //get form details
        $amount = $this->post('amount');
        $amount = number_format($amount, 2);//format amount to 2 decimal places

        $desc = $this->post('description');
        $type = $this->post('type'); //default value = MERCHANT
        $reference = $this->post('reference');//unique order id of the transaction, generated by merchant
        $first_name = $this->post('first_name');
        $last_name = $this->post('last_name');
        $email = $this->post('email');
        $phonenumber = '';//ONE of email or phonenumber is required

        $callback_url = ''; //redirect url, the page that will handle the response from pesapal.

        $post_xml = "<?xml version=\"1.0\" encoding=\"utf-8\"?><PesapalDirectOrderInfo xmlns:xsi=\"http://www.w3.org/2001/XMLSchema-instance\" xmlns:xsd=\"http://www.w3.org/2001/XMLSchema\" Amount=\"".$amount."\" Description=\"".$desc."\" Type=\"".$type."\" Reference=\"".$reference."\" FirstName=\"".$first_name."\" LastName=\"".$last_name."\" Email=\"".$email."\" PhoneNumber=\"".$phonenumber."\" xmlns=\"http://www.pesapal.com\" />";
        $post_xml = htmlentities($post_xml);

        $consumer = new OAuthConsumer($consumer_key, $consumer_secret);

        //post transaction to pesapal
        $iframe_src = OAuthRequest::from_consumer_and_token($consumer, $token, "GET", $iframelink, $params);
        $iframe_src->set_parameter("oauth_callback", $callback_url);
        $iframe_src->set_parameter("pesapal_request_data", $post_xml);
        $iframe_src->sign_request($signature_method, $consumer, $token);

        echo '<iframe src='.$iframe_src.'width="100%" height="620px"  scrolling="no" frameBorder="0">';

        //display pesapal - iframe and pass iframe_src
        //Do I send the iframe source to mobile??
        $this->response($iframe_src, 200); // 200 being the HTTP response code
    }

//--------- PesaPal Transactions ----------------
function pesapalreceive_get(){
    $consumer_key = 'NpQGkKRXdyEfAlMA5ouaE6Wm8MPh9Oge';//Register a merchant account on                 
    $consumer_secret = '7vZehCW2JpTL3m7A14Of3qUzIJs=';// Use the secret from your test
    $statusrequestAPI = 'https://demo.pesapal.com/api/querypaymentstatus';//change to    
 
     
    // Parameters sent to you by PesaPal IPN
    $pesapalNotification=$this->get('pesapal_notification_type');
    $pesapalTrackingId=$this->get('pesapal_transaction_tracking_id');
    $pesapal_merchant_reference=$this->get('pesapal_merchant_reference');
     
    if($pesapalNotification=="CHANGE" && $pesapalTrackingId!='')
    {
        $this->_send_sms('+254729472421', "The notification has come finally!");    
     
       $token = $params = NULL;
       $consumer = new OAuthConsumer($consumer_key, $consumer_secret);
       $signature_method = new OAuthSignatureMethod_HMAC_SHA1();

       //get transaction status
       $request_status = OAuthRequest::from_consumer_and_token($consumer, $token, "GET", $statusrequestAPI, $params);
       $request_status->set_parameter("pesapal_merchant_reference", $pesapal_merchant_reference);
       $request_status->set_parameter("pesapal_transaction_tracking_id",$pesapalTrackingId);
       $request_status->sign_request($signature_method, $consumer, $token);
     
       $ch = curl_init();
       curl_setopt($ch, CURLOPT_URL, $request_status);
       curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
       curl_setopt($ch, CURLOPT_HEADER, 1);
       curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
       if(defined('CURL_PROXY_REQUIRED')) if (CURL_PROXY_REQUIRED == 'True')
       {
          $proxy_tunnel_flag = (defined('CURL_PROXY_TUNNEL_FLAG') && strtoupper(CURL_PROXY_TUNNEL_FLAG) == 'FALSE') ? false : true;
          curl_setopt ($ch, CURLOPT_HTTPPROXYTUNNEL, $proxy_tunnel_flag);
          curl_setopt ($ch, CURLOPT_PROXYTYPE, CURLPROXY_HTTP);
          curl_setopt ($ch, CURLOPT_PROXY, CURL_PROXY_SERVER_DETAILS);
       }

     
       $response = curl_exec($ch);
       curl_close ($ch);
     
       $header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
       $raw_header  = substr($response, 0, $header_size - 4);
       $headerArray = explode("\r\n\r\n", $raw_header);
       $header      = $headerArray[count($headerArray) - 1];
     
       //transaction status
       $elements = preg_split("/=/",substr($response, $header_size));
       $status = $elements[4];
     
       //UPDATE YOUR DB TABLE WITH NEW STATUS FOR TRANSACTION WITH pesapal_transaction_tracking_id $pesapalTrackingId
       if(DB_UPDATE_IS_SUCCESSFUL)
       {
          $resp="pesapal_notification_type=$pesapalNotification&pesapal_transaction_tracking_id=$pesapalTrackingId&pesapal_merchant_reference=$pesapal_merchant_reference";
          $this->_send_sms('+254729472421', "The notification has come finally!".$resp);    
          ob_start();
          echo $resp;
          ob_flush();
          exit;
       }
     }
}//End of the Transaction


    //--------- PesaPal Transactions ----------------
    function kopokopo_post(){
        $sender_phone = $this->post('sender_phone');
        $this->_send_sms('+254729472421', "KopoKopo is working::".$sender_phone);    
    }

    //--Get all the transactions --
    function user_transactions_get($user_id='')
    {
    //-----Loading of Model should be placed inside the constructor -------------
        $load=$this->load->model('EzAuth_Model', 'ezauth');
        $this->ezauth->program = 'FlexiPay';    
    //-----Loading of Model should be placed inside the constructor -------------

        $user_id = $this->get('user_id');

        $transactions = $this->ezauth->get_transaction($user_id);

        if($transactions)
        {
            $this->response($transactions, 200); // 200 being the HTTP response code
        }

        else
        {
            $this->response(array('error' => 'Couldn\'t find any transactions!'), 404);
        }
    }

    //--Get all the Business Categories --
    function categories_get($user_id)
    {
        $transactions = $this->some_model->getSomething( $this->get('limit') );       
        if($users)
        {
            $this->response($users, 200); // 200 being the HTTP response code
        }

        else
        {
            $this->response(array('error' => 'Couldn\'t find any users!'), 404);
        }
    }


//-------Function to login user ------------------   
    function login_post($data = array()) {
        /*-------Should be Removed from here to a constructor-------------*/
        $this->load->model('EzAuth_Model', 'ezauth');
        $this->ezauth->program = 'FlexiPay'; 
        /*----------------------------------------------*/

         $mobile_number=$this->post('mobile_number');      
         $passcode= $this->post('passcode');


        $login_ok = $this->ezauth->login($mobile_number,$passcode); // $login_ok is true or false depending on user login information
        if($login_ok['authorize'] == true) {
             $this->response($login_ok, 200);
        }else {
           $response = array(
            'message'=>'Incorrect login credentials',
            'success'=>false
            );
         $this->response($response, 404);
        }
    }

//--------------Function to logout user----------------------------
    function logout() {
        $this->ezauth->logout();
        redirect('mystore');
    }


    function verify_post() {
    $this->load->model('EzAuth_Model', 'ezauth');
    $this->ezauth->program = 'FlexiPay'; 

    $v_code = $this->post('v_code');
    $login_after_verify=true;
    $response=$this->ezauth->verify_phone($v_code, $login_after_verify);

    if ($response['authorize']){
        $this->response($response, 200); // 200 being the HTTP response code
        // Take user to the Necessary page

    } else {
        $response = array(
            'message'=>'Incorrect verification code Entered',
            'success'=>false
            );
         $this->response($response, 404);
         //Reject code
    }
    }
    
    function forgotpw1() {
        $data = array();
        $fields = array(
            'username'  =>  'trim',
            'email'     =>  'trim'
        );
        $rules = array(
            'username'  =>  'User name',
            'email'     =>  'E-mail address'
        );
        $this->validation->set_rules($rules);
        $this->validation->set_fields($fields);
        if ($this->validation->run()) {
            $user = $this->ezauth->get_userid($this->input->post('username'), $this->input->post('email'));
            $usr = $this->ezauth->get_reset_code($user['user_id']);
            $message = auto_link('here is your reset code: http://bizwidgets.biz/demos/ezauth/mystore/forgotpw2/'.$usr['reset_code']);
            $this->_send_mail($usr['email'], 'Reset Code', $message);
            $data['disp_message'] = 'A reset code was sent to your e-mail address. Check your e-mail!';
        }
        $this->load->view('forgotpw1', $data);
    }
    
    function forgotpw2() {
        $reset_code = $this->uri->segment(3);
        if (empty($reset_code)) return false;
        $usr = $this->ezauth->reset_password($reset_code);
        $message = 'Username: '.$usr['username']. '. Here is your temporary password: '.$usr['temp_pw'];
        $this->_send_mail($usr['email'], 'Temporary Password', $message);
        $data['disp_message'] = 'Your temporary password was e-mailed to you. Check your e-mail!';
        $this->load->view('forgotpw2', $data);
    }
    
    function changepw_post() {
        $this->load->model('EzAuth_Model', 'ezauth');
        $this->ezauth->program = 'FlexiPay';

        $data = array();
     
        $result = $this->ezauth->change_pw($this->ezauth->user->id, $this->post('new_password'));
        echo $this->ezauth->user->id;
        if ($result)
        { 
            $data['disp_message'] = 'Password changed!';
            $data['success'] = true; 
            $this->response($data, 200); // 200 being the HTTP response code

        } 

        else
        {
        $data['disp_message'] = 'Your Password not changed!';
        $data['success'] = false; 
        $this->response($data, 404); // 404 being the HTTP response code
        }
       
    }
    


    //function to send email
    //In my case am only verifying the phone Number this would be done later.
    function _send_mail($to, $subject, $message) {
        $config = Array(
            'protocol' => 'smtp',
            'smtp_host' => 'ssl://smtp.googlemail.com',
            'smtp_port' => 465,
            'smtp_user' => 'tosh0948@gmail.com',
            'smtp_pass' => 'gitonga09',
            'mailtype'  => 'html', 
            'charset'   => 'iso-8859-1'
        );
         $config['mailtype'] = 'html';
         $this->load->library('email',$config);
        // $this->email->from('admin@flexipay.co.ke', 'FlexiPay Admin');

        // $this->email->to($to);
        // $this->email->subject($subject);
        // $this->email->message($message);    

      
         if ($this->email->send()) {
            echo 'Your email was sent, thanks chamil.';
        } else {
            show_error($this->email->print_debugger());
        }
    }


//----------Function to send sms-------------------
    function _send_sms($recipient,$message){
        // Specify your login credentials
        $username    = "TomKim";
        $apiKey      = "1473c117e56c4f2df393c36dda15138a57b277f5683943288c189b966aae83b4"; 

        // Create a new instance of our awesome gateway class
        $gateway  = new AfricaStalkingGateway($username, $apiKey);

        // Thats it, hit send and we'll take care of the rest
        $results  = $gateway->sendMessage($recipient, $message);
        if ( count($results) ) {
          // These are the results if the request is well formed
          foreach($results as $result) {
         /*   echo " Number: " .$result->number;
            echo " Status: " .$result->status;
            echo " Cost: "   .$result->cost."\n";*/
          }
        } else {
            // We only get here if we cannot process your request at all
            // (usually due to wrong username/apikey combinations)
            echo "Oops, No messages were sent. ErrorMessage: ".$gateway->getErrorMessage();
        }
        // DONE!!!
    }
       
}