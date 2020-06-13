<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Api extends CI_Controller {

	/**
	 * Index Page for this controller.
	 *
	 * Maps to the following URL
	 * 		http://example.com/index.php/welcome
	 *	- or -
	 * 		http://example.com/index.php/welcome/index
	 *	- or -
	 * Since this controller is set as the default controller in
	 * config/routes.php, it's displayed at http://example.com/
	 *
	 * So any other public methods not prefixed with an underscore will
	 * map to /index.php/welcome/<method_name>
	 * @see https://codeigniter.com/user_guide/general/urls.html
	 */
    public function __construct() {
    //load database in autoload libraries 
      parent::__construct(); 
       
       $this->load->library('form_validation');
   }
    
	public function index()
	{
	   echo "Welcome to Game Api.";
    }
    
    public function signup(){
        $this->load->helper('string');
        $refer_id=strtoupper(random_string('alnum',5));
        $data=array();
          /* add registers table validation */
        $this->form_validation->set_rules('first_name', 'First Name', 'trim|required|alpha');
        $this->form_validation->set_rules('last_name', 'Last Name', 'trim|required|alpha');
        $this->form_validation->set_rules('username', 'Username', 'trim|required|is_unique[users.username]',  'callback_username_check');
        $this->form_validation->set_rules('email', 'Email', 'trim|required|valid_email|is_unique[users.email]');
        $this->form_validation->set_rules('password', 'Password', 'trim|required');
       
        if ($this->form_validation->run() == FALSE) {
            $data["response"] = false;
            $data["error"] = strip_tags($this->form_validation->error_string());
        }else {
           $insert_data=array(
                "refer_code" => $refer_id,
                "fname" => $this->input->post("first_name"),
                "lname" => $this->input->post("last_name"),
                "username" => $this->input->post("username"),
                "email" => $this->input->post("email"),
                "password" => md5($this->input->post("password")),
                "refer_by" => $this->input->post("refer_by")
           );
      
            if( $this->db->insert("users",  $insert_data)){
                 $user_id = $this->db->insert_id();
                 $user=$this->db->get_where('users', array('id' => $user_id))->row();
               
                if(!empty($user)){
                        $user_details=array(
                        "Auto No."=>$user->id,
                        "User ReferCode"=>$user->refer_code,
                        "UserMame"=>$user->username,
                        "FirstName"=>$user->fname,
                        "LastName"=>$user->lname,
                        "Email"=>$user->email,
                        "Password"=>$user->password,
                        "Refer By"=>$user->refer_by,
                        "Creation Date"=>$user->created_at
                    );
                 }else{
                    $user_details=array();
                }
                
                
                $data["response"] = true;
                $data["userDetails"]=$user_details;
            }else{
                $data["response"] = false;
                $data["error"]="Oops Something went wrong.";
            }
            

        }
        
       echo json_encode($data);
    }
    
     public function signin() {
        $data = array();
        $_POST = $_REQUEST;
        $this->load->library('form_validation');
        $this->form_validation->set_rules('username', 'Username ', 'trim|required');
        $this->form_validation->set_rules('password', 'Password', 'trim|required');

        if ($this->form_validation->run() == FALSE) {
            $data["response"] = false;
            $data["error"] = strip_tags($this->form_validation->error_string());
        } else {
            $q = $this->db->query("Select * from users where  username='" . $this->input->post('username') . "' and password='" . md5($this->input->post('password')) . "' Limit 1");

       }       
              
            if ($q->num_rows() > 0) {
                $row = $q->row();
             } else {
                $data["response"] = false;
                $data["error"] = 'Invalide Username or Passwords';
            }
//         print_r($q);die;
          echo json_encode($data);
    }
    
       public function forgot_password() {
        $data = array();
        $this->load->library('form_validation');
        $this->form_validation->set_rules('email', 'Email', 'trim|required');
        if ($this->form_validation->run() == FALSE) {
            $data["response"] = false;
            $data["error"] = 'Warning! : ' . strip_tags($this->form_validation->error_string());
        } else {
            $request = $this->db->query("Select * from users where email = '" . $this->input->post("email") . "' limit 1");
            if ($request->num_rows() > 0) {

                $user = $request->row();
                
                //$token = uniqid(uniqid());
                //$this->db->update("registers",array("varified_token"=>$token),array("user_id"=>$user->user_id)); 
                //$this->load->library('email');
                //$this->email->from($this->config->item('default_email'), $this->config->item('email_host'));

                if ($this->form_validation->run() == TRUE) {
                    $code = mt_rand(1000, 9999);
                    $email = $this->input->post('email');
                    $name = 'username';
                    $update = $this->db->query("UPDATE `users` SET password='" . md5($code) . "' where email='" . $email . "' ");
                    // $email = $user->user_email;
                    // $name = $user->user_fullname;
                    //$return = $this->send_email_verified_mail($email,$token,$name);


                    $to = $email;
                    $subject = "Forgot Password";
                    $message = '<p>Hi ' . $name . ' your new password is ' . $code . '</p>';


                    $headers = "From: Kadaknath <info@liquoradrive.com>" . "\r\n" .
                            'MIME-Version: 1.0' . "\r\n" .
                            'Content-type: text/html; charset=iso-8859-1' . "\r\n" .
                            'X-Mailer: PHP/' . phpversion();

//                    $send = mail($to, $subject, $message, $headers);


                    if ($update) {
                        $data["response"] = true;
                        $data["error"] = 'Success! : New Password : ' . $code;
                    } else {
                        $data["response"] = false;
                        $data["error"] = 'Warning! : Something is wrong with system.';
                    }
                } else {
                    $data["response"] = false;
                    $data["error"] = 'Account is not Verified.';
                }
            } else {
                $data["response"] = false;
                $data["error"] = 'Warning! : No user found with this email.';
            }
        }
        echo json_encode($data);
    }

    public function change_password() {
        $data = array();
        $this->load->library('form_validation');
        /* add users table validation */
        $this->form_validation->set_rules('username', 'Username', 'trim|required');
        $this->form_validation->set_rules('current_password', 'Current Password', 'trim|required');
        $this->form_validation->set_rules('new_password', 'New Password', 'trim|required');

        if ($this->form_validation->run() == FALSE) {
            $data["response"] = false;
            $data["error"] = strip_tags($this->form_validation->error_string());
        } else {
//            $this->load->model("common_model");
            $q = $this->db->query("select * from users where username = '" . $this->input->post("username") . "' and  password = '" . md5($this->input->post("current_password")) . "' limit 1");
            $user = $q->row();
            $newpass = 'new_password';
            $username = 'username';
            if (!empty($user)) {
                 
                $q = $this->db->query("UPDATE `users` SET password='" . md5($newpass) . "' where username='" . $username . "' ");
//                  print_r($q);die;
//                $this->input->data_update("users", array(
//                    "password" => md5($this->input->post("new_password"))
//                        ), array("username" => $user->username));

                $data["response"] = true;
                $data["success"] = 'Password Changed';
            } else {
                $data["response"] = false;
                $data["error"] = 'Current password do not match';
            }
        }

        echo json_encode($data);
    }

    
}
