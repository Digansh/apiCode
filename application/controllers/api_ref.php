<?php

if (!defined('BASEPATH'))
    exit('No direct script access allowed');

class Api extends CI_Controller {

    public function __construct() {
        parent::__construct();
        // Your own constructor code
        header('Content-type: text/json');
        date_default_timezone_set('Asia/Kolkata');
        $this->load->database();
        $this->load->helper('sms_helper');
        $this->load->helper(array('form', 'url'));
        $this->db->query("SET time_zone='+05:30'");
    }

    /*     * ************ Kadaknath Api start Shashi ****************** */

    public function index() {
        // $to = 'seniorphp.xen@gmail.com';
        //     $subject = "Email Verification";
        //     $message = '<p>Hi '.$name.' OTP for email verification is '.$otp.'</p>';
        //     $headers = "From: Liquora Drive <info@liquoradrive.com>" . "\r\n" .
        //             'MIME-Version: 1.0' . "\r\n" .
        //             'Content-type: text/html; charset=iso-8859-1' . "\r\n" .
        //             'X-Mailer: PHP/' . phpversion();
        //     $send = mail($to, $subject, $message, $headers);
        //     echo $send;
    }

    /* user registration */

    public function signup() {
        $data = array();
        $_POST = $_REQUEST;
        $this->load->library('form_validation');
        /* add registers table validation */
        $this->form_validation->set_rules('user_name', 'Full Name', 'trim|required');
        $this->form_validation->set_rules('user_mobile', 'Mobile Number', 'trim|required|is_unique[registers.user_phone]|numeric|regex_match[/^[0-9]{10}$/]');
        $this->form_validation->set_rules('user_email', 'User Email', 'trim|required|valid_email|is_unique[registers.user_email]');
        $this->form_validation->set_rules('password', 'Password', 'trim|required');

        $email = $this->input->post("user_email");
        $name = $this->input->post("user_name");
        $otp = rand(1000, 2000);

        if ($this->form_validation->run() == FALSE) {
            $data["response"] = false;
            $data["error"] = strip_tags($this->form_validation->error_string());
        } else {

            $date = date('d/m/y');
            $this->db->insert("registers", array("user_phone" => $this->input->post("user_mobile"),
                "user_fullname" => $this->input->post("user_name"),
                "user_email" => $this->input->post("user_email"),
                "user_password" => md5($this->input->post("password")),
                "reg_code" => $otp,
                "status" => 0
            ));

            $user_id = $this->db->insert_id();

            $to = $email;
            $subject = "Email Verification";
            $message = '<p>Hi ' . $name . ' OTP for email verification is ' . $otp . '</p>';


            $headers = "From: Kadaknath <info@liquoradrive.com>" . "\r\n" .
                    'MIME-Version: 1.0' . "\r\n" .
                    'Content-type: text/html; charset=iso-8859-1' . "\r\n" .
                    'X-Mailer: PHP/' . phpversion();

            $send = mail($to, $subject, $message, $headers);

            $data["response"] = $send;
            $data["message"] = "User Register Sucessfully.Check Email for email varification.";
        }

        echo json_encode($data);
    }

    /* user login json */

    public function signin() {
        $data = array();
        $_POST = $_REQUEST;
        $this->load->library('form_validation');
        $this->form_validation->set_rules('user_email', 'Email Id', 'trim|required');
        $this->form_validation->set_rules('password', 'Password', 'trim|required');

        if ($this->form_validation->run() == FALSE) {
            $data["response"] = false;
            $data["error"] = strip_tags($this->form_validation->error_string());
        } else {
            $q = $this->db->query("Select * from registers where  user_email='" . $this->input->post('user_email') . "' and user_password='" . md5($this->input->post('password')) . "' Limit 1");


            if ($q->num_rows() > 0) {
                $row = $q->row();
                if ($row->status == "0") {
                    $data["response"] = false;
                    $data["error"] = 'Your account currently De-active.Please Verified email';
                } else {
                    $data["response"] = true;
                    $data["data"] = array("user_id" => $row->user_id, "user_fullname" => $row->user_fullname,
                        "user_email" => $row->user_email, "user_phone" => $row->user_phone);
                }
            } else {
                $data["response"] = false;
                $data["error"] = 'Invalide Username or Passwords';
            }
        }
        echo json_encode($data);
    }

    public function otp_verification() {
        $data = array();
        $_POST = $_REQUEST;
        $this->load->library('form_validation');
        $this->form_validation->set_rules('user_email', 'Email Id', 'trim|required');
        $this->form_validation->set_rules('otp', 'OTP', 'trim|required');

        if ($this->form_validation->run() == FALSE) {
            $data["response"] = false;
            $data["error"] = strip_tags($this->form_validation->error_string());
        } else {
            $q = $this->db->query("Select * from registers where  user_email='" . $this->input->post('user_email') . "' Limit 1");


            if ($q->num_rows() > 0) {
                $row = $q->row();
                if ($row->reg_code != $this->input->post('otp')) {
                    $data["response"] = false;
                    $data["error"] = 'Incorrect OTP';
                } else {
                    $update = $this->db->query("UPDATE `registers` SET verified=1, status=1  where user_email='" . $this->input->post('user_email') . "' ");
                    $data["response"] = true;
                    $data["data"] = "Your account is activated. Please login";
                }
            } else {
                $data["response"] = false;
                $data["error"] = 'Invalide Email';
            }
        }
        echo json_encode($data);
    }

    /* User Details */

    public function getUserDetails() {
        $data = array();
        $_POST = $_REQUEST;
        $this->load->library('form_validation');
        $this->form_validation->set_rules('user_id', 'User ID', 'trim|required');

        if ($this->form_validation->run() == FALSE) {
            $data["response"] = false;
            $data["error"] = strip_tags($this->form_validation->error_string());
        } else {
            $user = $this->db->query("Select * from registers where  user_id='" . $this->input->post('user_id') . "' Limit 1");
            $row = $user->row();

            if ($row) {

                if ($row->status == "0") {
                    $data["response"] = false;
                    $data["error"] = 'Your account currently De-active.Please Contact Admin';
                } else {
                    $data["response"] = true;
                    $img = "";
                    if ($row->user_image) {
                        $img = base_url() . "uploads/profile/" . $row->user_image;
                    }
                    $data["data"] = array("user_id" => $row->user_id, "user_fullname" => $row->user_fullname,
                        "user_email" => $row->user_email, "user_phone" => $row->user_phone,
                        "User_house_no" => $row->house_no, "user_city" => $row->city, "user_state" => $row->state, "user_pincode" => $row->pincode, "profile_image" => $row->user_image);
                }
            } else {
                $data["response"] = false;
                $data["error"] = 'Wrong User_id';
            }
        }
        echo json_encode($data);
    }

    /* save Address */

    public function updateAddress() {
        $data = array();
        $_POST = $_REQUEST;
        $this->load->library('form_validation');
        $this->form_validation->set_rules('user_id', 'User ID', 'trim|required');
        $this->form_validation->set_rules('house_no', 'House Number', 'trim|required');
        $this->form_validation->set_rules('state', 'State', 'trim|required');
        $this->form_validation->set_rules('city', 'City', 'trim|required');
        $this->form_validation->set_rules('pincode', 'Pincode', 'trim|required');

        if ($this->form_validation->run() == FALSE) {
            $data["response"] = false;
            $data["error"] = strip_tags($this->form_validation->error_string());
        } else {
            $user = $this->db->query("Select * from registers where  user_id='" . $this->input->post('user_id') . "' Limit 1");
            $row = $user->row();

            if ($row) {
                $q = $this->db->query("Update registers SET house_no='" . $this->input->post('house_no') . "',city='" . $this->input->post('city') . "', state='" . $this->input->post('state') . "',pincode='" . $this->input->post('pincode') . "' where  user_id='" . $this->input->post('user_id') . "'");

                if ($row->status == "0") {
                    $data["response"] = false;
                    $data["error"] = 'Your account currently De-active.Please Contact Admin';
                } else {
                    $data["response"] = true;
                    $data["data"] = array("user_id" => $row->user_id, "user_fullname" => $row->user_fullname,
                        "user_email" => $row->user_email, "user_phone" => $row->user_phone,
                        "User_house_no" => $this->input->post('house_no'), "user_city" => $this->input->post('city'), "user_state" => $this->input->post('state'), "user_pincode" => $this->input->post('pincode'));
                }
            } else {
                $data["response"] = false;
                $data["error"] = 'Wrong User_id';
            }
        }
        echo json_encode($data);
    }

    public function update_profile_pic() {
        $data = array();
        $this->load->library('form_validation');
        /* add users table validation */
        $this->form_validation->set_rules('user_id', 'User ID', 'trim|required');

        if ($this->form_validation->run() == FALSE) {
            $data["response"] = false;
            $data["error"] = 'Warning! : ' . strip_tags($this->form_validation->error_string());
        } else {

            if (isset($_FILES["image"]) && $_FILES["image"]["size"] > 0) {
                $config['upload_path'] = './uploads/profile/';
                $config['allowed_types'] = 'gif|jpg|png|jpeg';
                $config['encrypt_name'] = TRUE;
                $this->load->library('upload', $config);

                if (!$this->upload->do_upload('image')) {
                    $data["response"] = false;
                    $data["error"] = 'Error! : ' . $this->upload->display_errors();
                } else {
                    $img_data = $this->upload->data();
                    $this->load->model("common_model");
                    $this->common_model->data_update("registers", array(
                        "user_image" => $img_data['file_name']
                            ), array("user_id" => $this->input->post("user_id")));

                    $data["response"] = true;
                    $data["data"] = base_url() . "uploads/profile/" . $img_data['file_name'];
                }
            } else {
                $data["response"] = false;
                $data["error"] = 'Please choose profile image';
            }
        }

        echo json_encode($data);
    }

    public function change_password() {
        $data = array();
        $this->load->library('form_validation');
        /* add users table validation */
        $this->form_validation->set_rules('user_id', 'User ID', 'trim|required');
        $this->form_validation->set_rules('current_password', 'Current Password', 'trim|required');
        $this->form_validation->set_rules('new_password', 'New Password', 'trim|required');

        if ($this->form_validation->run() == FALSE) {
            $data["response"] = false;
            $data["error"] = strip_tags($this->form_validation->error_string());
        } else {
            $this->load->model("common_model");
            $q = $this->db->query("select * from registers where user_id = '" . $this->input->post("user_id") . "' and  user_password = '" . md5($this->input->post("current_password")) . "' limit 1");
            $user = $q->row();

            if (!empty($user)) {
                $this->common_model->data_update("registers", array(
                    "user_password" => md5($this->input->post("new_password"))
                        ), array("user_id" => $user->user_id));

                $data["response"] = true;
                $data["success"] = 'Password Changed';
            } else {
                $data["response"] = false;
                $data["error"] = 'Current password do not match';
            }
        }

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
            $request = $this->db->query("Select * from registers where user_email = '" . $this->input->post("email") . "' limit 1");
            if ($request->num_rows() > 0) {

                $user = $request->row();

                //$token = uniqid(uniqid());
                //$this->db->update("registers",array("varified_token"=>$token),array("user_id"=>$user->user_id)); 
                //$this->load->library('email');
                //$this->email->from($this->config->item('default_email'), $this->config->item('email_host'));

                if ($user->verified == '1') {
                    $code = mt_rand(1000, 9999);
                    $email = $this->input->post('email');

                    $update = $this->db->query("UPDATE `registers` SET user_password='" . md5($code) . "' where user_email='" . $email . "' ");
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

                    $send = mail($to, $subject, $message, $headers);


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

    public function update_userdata() {
        $data = array();
        $this->load->library('form_validation');
        /* add users table validation */
        $this->form_validation->set_rules('user_id', 'User ID', 'trim|required');
        $this->form_validation->set_rules('user_name', 'Full Name', 'trim');
        $this->form_validation->set_rules('user_mobile', 'Mobile Number', 'trim|numeric|regex_match[/^[0-9]{10}$/]');

        if ($this->form_validation->run() == FALSE) {
            $data["response"] = false;
            $data["error"] = 'Warning! : ' . strip_tags($this->form_validation->error_string());
        } else {
            $insert_array = array();
            if (isset($_POST["user_name"])) {
                $insert_array['user_fullname'] = $this->input->post("user_name");
            }
            if (isset($_POST["user_mobile"])) {
                $insert_array['user_phone'] = $this->input->post("user_mobile");
            }

            $this->load->model("common_model");
            //$this->db->where(array("user_id",$this->input->post("user_id")));
            if (isset($_FILES["image"]) && $_FILES["image"]["size"] > 0) {
                $config['upload_path'] = './uploads/profile/';
                $config['allowed_types'] = 'gif|jpg|png|jpeg';
                $config['encrypt_name'] = TRUE;
                $this->load->library('upload', $config);

                if (!$this->upload->do_upload('image')) {
                    $data["response"] = false;
                    $data["error"] = 'Error! : ' . $this->upload->display_errors();
                } else {
                    $img_data = $this->upload->data();
                    $image_name = $img_data['file_name'];
                    $insert_array["user_image"] = base_url() . "uploads/profile/" . $image_name;
                }
            }

            $this->common_model->data_update("registers", $insert_array, array("user_id" => $this->input->post("user_id")));

            $q = $this->db->query("Select * from `registers` where(user_id='" . $this->input->post('user_id') . "' ) Limit 1");
            $row = $q->row();
            $data["response"] = true;
            $data["data"] = array("user_id" => $row->user_id, "user_fullname" => $row->user_fullname, "user_email" => $row->user_email, "user_phone" => $row->user_phone, "user_image" => $row->user_image, "pincode" => $row->pincode, "city" => $row->city, "state" => $row->state, "house_no" => $row->house_no);
        }

        echo json_encode($data);
    }

    public function coupons() {

        $q = $this->db->query("select * from `coupons`");
        $data["response"] = true;
        $data['data'] = $q->result();
        echo json_encode($data);
    }

    public function get_order_status() {

        $data = array();
        $this->load->library('form_validation');
        $this->form_validation->set_rules('user_id', 'User ID', 'trim|required');
        $this->form_validation->set_rules('order_id', 'Order ID', 'trim|required');
        if ($this->form_validation->run() == FALSE) {
            $data["response"] = false;
            $data["error"] = 'Warning! : ' . strip_tags($this->form_validation->error_string());
        } else {

            $order = $this->db->query("Select sale.status,registers.user_fullname from sale LEFT JOIN registers on registers.user_id=sale.user_id where  sale.user_id='" . $this->input->post('user_id') . "' AND sale.sale_id='" . $this->input->post('order_id') . "' Limit 1");
            $row = $order->row();

            if ($order->num_rows() > 0) {
                $name = ucfirst($row->user_fullname);
                if ($row->status == 0) {
                    $status = 'pending';
                    $msg = $name . ' waiting for order confirmation';
                } elseif ($row->status == 1) {
                    $status = 'Confirmed';
                    $msg = $name . ' your order is confirmed getting ready to dispatch';
                } elseif ($row->status == 3) {
                    $status = 'Cancel';
                    $msg = $name . ' your order is canceled';
                } elseif ($row->status == 4) {
                    $status = 'Complete';
                    $msg = $name . ' your order is completed';
                } elseif ($row->status == 5) {
                    $status = 'Dispatch';
                    $msg = $name . ' your order is dispatched will reach you shortly. ';
                }


                $data["response"] = true;
                $data['response'] = array('status' => $status);
                $data['message'] = $msg;
            } else {
                $data["response"] = false;
                $data['response'] = "Wrong Details";
            }
        }

        echo json_encode($data);
    }

    public function order_dispatch() {

        $data = array();
        $this->load->library('form_validation');
        $this->form_validation->set_rules('user_id', 'User ID', 'trim|required');
        $this->form_validation->set_rules('order_id', 'Order ID', 'trim|required');
        if ($this->form_validation->run() == FALSE) {
            $data["response"] = false;
            $data["error"] = 'Warning! : ' . strip_tags($this->form_validation->error_string());
        } else {

            $update = $this->db->query("Update sale set status = 5 where user_id = '" . $this->input->post("user_id") . "' and  sale_id = '" . $this->input->post("sale_id") . "' ");

            if ($update) {

                $data["response"] = true;
                $data['message'] = "Order status Updated Successfully";
            } else {
                $data["response"] = false;
                $data['message'] = "Something went wrong";
            }
        }

        echo json_encode($data);
    }

    public function addAddress() {
        $data = array();
        $_POST = $_REQUEST;
        $this->load->library('form_validation');
        /* add registers table validation */
        $this->form_validation->set_rules('user_id', 'User Id', 'trim|required');
        // $this->form_validation->set_rules('socity_id', 'Socity ID', 'trim|required');
        $this->form_validation->set_rules('locality', 'Locality', 'trim|required');
        $this->form_validation->set_rules('state', 'State', 'trim|required');
        $this->form_validation->set_rules('city', 'City', 'trim|required');
        $this->form_validation->set_rules('pincode', 'Pincode', 'trim|required');


        if ($this->form_validation->run() == FALSE) {
            $data["response"] = false;
            $data["error"] = strip_tags($this->form_validation->error_string());
        } else {

            $q = $this->db->query("Select * from `registers` where(user_id='" . $this->input->post('user_id') . "' ) Limit 1");
            $row = $q->row();

            if ($q->num_rows() > 0) {


                $this->db->insert("address", array("user_id" => $this->input->post("user_id"),
                    // "socity_id" => $this->input->post("socity_id"),
                    "locality" => $this->input->post("locality"),
                    "city" => ($this->input->post("city")),
                    "state" => ($this->input->post("state")),
                    "pincode" => ($this->input->post("pincode"))
                ));

                $add_id = $this->db->insert_id();

                $data["response"] = true;
                $data["address_id"] = $add_id;
                $data["message"] = "Address Added Sucessfully.";
            } else {
                $data["response"] = false;
                $data["message"] = "User Id is not correct.";
            }
        }

        echo json_encode($data);
    }

    public function deleteAddress() {

        $data = array();
        $_POST = $_REQUEST;
        $this->load->library('form_validation');
        $this->form_validation->set_rules('address_id', 'Address Id', 'trim|required');

        if ($this->form_validation->run() == FALSE) {
            $data["response"] = false;
            $data["error"] = strip_tags($this->form_validation->error_string());
        } else {
            $a = $this->db->query("Delete from `address` where(id ='" . $this->input->post('address_id') . "' ) Limit 1");
            if ($a) {
                $data["response"] = true;
                $data["data"] = 'Address Deleted Successfully';
            } else {
                $data["response"] = false;
                $data["data"] = 'Incorrect Id';
            }
        }

        echo json_encode($data);
    }

    public function getAddress() {

        $data = array();
        $_POST = $_REQUEST;
        $this->load->library('form_validation');
        $this->form_validation->set_rules('user_id', 'User Id', 'trim|required');

        if ($this->form_validation->run() == FALSE) {
            $data["response"] = false;
            $data["error"] = strip_tags($this->form_validation->error_string());
        } else {
            $add = "SELECT * from address where(user_id ='" . $this->input->post('user_id') . "' ) ";
            $result = $this->db->query($add);
            $get_total_rows = $result->num_rows();
            $result_arr = $result->result_array();
            $response['success'] = true;
            $response['message'] = $result_arr;
        }
        echo json_encode($response);
    }

    /*     * ************ Kadaknath Api End ****************** */
    /*     * *******************  Astha *************************************** */

    public function get_categories() {
        $parent = 0;
        if ($this->input->post("parent")) {
            $parent = $this->input->post("parent");
        }
        $categories = $this->get_categories_short($parent, 0, $this);
        $data["response"] = true;
        $data["data"] = $categories;
        echo json_encode($data);
    }

    public function categories() {
        $cardat = "SELECT * from categories where  status='1' ";
        $result = $this->db->query($cardat);
        $get_total_rows = $result->num_rows();
        $result_arr = $result->result_array();
        if ($get_total_rows > 0) {
            foreach ($result_arr as $catrow) {
                $response[] = array(
                    "id" => $catrow['id'],
                    "name" => $catrow['title'],
                );
            }
            echo json_encode(array("success" => true, "data" => $response));
        } else {
            $response['success'] = false;
            $response['message'] = "No Category Found";
            echo json_encode($response);
        }
    }

    //getProduct


    public function getProduct() {
        $category = $this->input->post('category_id');
        $cardat = "SELECT * from products where in_stock='1' AND `category_id`=" . $category . "";
        $result = $this->db->query($cardat);
        $this->db->from('products');
        $this->db->where('in_stock="1"');
        $this->db->where('category_id=' . $category . '');
        $query = $this->db->get();
        $products = $query->result_array();
        $get_total_rows = $result->num_rows();
//        print_r()
        if ($get_total_rows > 0) {
            foreach ($products as $prow) {
                // print_r($prow['product_name']);die('hello'); 
                $response[] = array(
                    'product_id' => $prow['product_id'],
                    'product_name' => $prow['product_name'],
                    'category_id' => $prow['category_id'],
                    'product_description' => $prow['product_description']
                    ,
                    'price' => $prow['price'],
                    'product_image' => base_url('uploads/products' . '/' . $prow['product_image']),
                    //'tax'=>$product->tax,
                    'restaurant_id' => $prow['restaurant_id'],
                    'status' => '0',
                    'in_stock' => $prow['in_stock'],
                );

                //print_r($response);die;
            }
            // print_r(base_url('uploads/products'.$products->product_image));die;  
            echo json_encode(array("success" => true, "data" => $response));
        } else {
            $response['success'] = false;
            $response['message'] = "No Category Found";
            echo json_encode($response);
        }
    }

    public function product() {
        $cardat = "SELECT * from products where in_stock='1'";
        $result = $this->db->query($cardat);
        $this->db->from('products');
        $query = $this->db->get();
        $products = $query->result_array();
        $get_total_rows = $result->num_rows();
        if ($get_total_rows > 0) {
            foreach ($products as $prow) {
                $response[] = array(
                    'product_id' => $prow['product_id'],
                    'product_name' => $prow['product_name'],
                    'category_id' => $prow['category_id'],
                    'product_description' => $prow['product_description']
                    ,
                    'price' => $prow['price'],
                    'product_image' => base_url('uploads/products' . '/' . $prow['product_image']),
                    'restaurant_id' => $prow['restaurant_id'],
                    'status' => '0',
                    'in_stock' => $prow['in_stock'],
                );
            }

            echo json_encode(array("success" => true, "data" => $response));
        } else {
            $response['success'] = false;
            $response['message'] = "No Category Found";
            echo json_encode($response);
        }
    }

    public function order() {

        $data = array();
        $_POST = $_REQUEST;
        $this->load->library('form_validation');
        $this->form_validation->set_rules('user_id', 'User Id', 'trim|required');
        $this->form_validation->set_rules('cart_id', 'Cart Id', 'trim|required');
        $this->form_validation->set_rules('total_amount', 'Total Amount', 'trim|required');
        $this->form_validation->set_rules('date', 'Date', 'trim|required');
        $this->form_validation->set_rules('total_items', 'Total Items', 'trim|required');
        $this->form_validation->set_rules('pincode', 'Pincode', 'trim|required');
        $this->form_validation->set_rules('delivery_address', 'Delivery Address', 'trim|required');
        $this->form_validation->set_rules('country', 'Country', 'trim|required');
        $this->form_validation->set_rules('payment_method', 'Payment Method', 'trim|required');
        $this->form_validation->set_rules('name', 'Name', 'trim|required');
        $this->form_validation->set_rules('full_name', 'Full Name', 'trim|required');
        $this->form_validation->set_rules('phone', 'Phone', 'trim|required');
        $this->form_validation->set_rules('email', 'Email', 'trim|required');
        $this->form_validation->set_rules('socity_id', 'Socity Id', 'trim|required');
         $this->form_validation->set_rules('tax', 'Tax', 'trim|required');


        if ($this->form_validation->run() == FALSE) {
            $data["response"] = false;
            $data["error"] = strip_tags($this->form_validation->error_string());
        } else {

            $user_id = $this->input->post("user_id");
            $cart = $this->input->post("cart_id");
            $total_amount = $this->input->post("total_amount");
            $date = $this->input->post("date");
            $status = $this->input->post("status");
            $promocode = $this->input->post("promocode");
            $total_items = $this->input->post("total_items");
            $pincode = $this->input->post("pincode");
            $delivery_address = $this->input->post("delivery_address");
            $country = $this->input->post("country");
            $payment_method = $this->input->post("payment_method");
            $name = $this->input->post("name");
            $full_name = $this->input->post("full_name");
            $phone = $this->input->post("phone");
            $email = $this->input->post("email");
            $socity_id = $this->input->post("socity_id");
            $tax = $this->input->post("tax");
            $promocode_amount = $this->input->post("promocode_amount");
            $cart_id = explode(",", $cart);


            if ($user_id) {

                foreach ($cart_id as $id) {
                    $cardat = "SELECT * from cart where cart_id='$id'";
                    $result = $this->db->query($cardat);
                    $get_total_rows = $result->num_rows();
                    if ($get_total_rows > 0) {
                        $cart_check = true;
                    } else {
                        $cart_check = false;
                    }
                }

                if ($cart_check) {
                    $order_create = $this->db->query("INSERT INTO sale ( user_id,cart_id,total_amount,date,promocode,total_items,pincode,delivery_address,country,payment_method,name,full_name,phone,email,socity_id,promocode_amount,tax) VALUES ('$user_id','$cart','$total_amount','$date','$promocode','$total_items','$pincode','$delivery_address','$country','$payment_method','$name','$full_name','$phone','$email','$socity_id','$promocode_amount','$tax')");
                    $order_id = $this->db->insert_id();
                    if ($order_create) {

                        foreach ($cart_id as $id) {

                            // $cardat = "SELECT * from cart where cart_id='$id'";
                            // $result = $this->db->query($cardat);

                            $cart_create = $this->db->query("INSERT INTO  order_cart( order_id,cart_id) VALUES('$order_id','$id')");
                            $this->db->insert_id();
                        }

                        $data['response'] = true;
                        $data['order_id'] = $order_id;
                        $data['msg'] = "Add order Successfull";
                    } else {
                        $data['response'] = false;
                        $data['msg'] = "Add order Not Successfull";
                    }
                } else {
                    $data['response'] = false;
                    $data['msg'] = "Incorrect Cart ID";
                }
            }
        }

        echo json_encode($data);
    }

    public function repeatOrder() {

        $data = array();
        $_POST = $_REQUEST;
        $this->load->library('form_validation');
        $this->form_validation->set_rules('order_id', 'Order Id', 'trim|required');
        if ($this->form_validation->run() == FALSE) {
            $data["response"] = false;
            $data["error"] = strip_tags($this->form_validation->error_string());
        } else {
            $order_id = $this->input->post("order_id");

            $order_details = "SELECT * from sale where sale_id='$order_id'";
            $query = $this->db->query($order_details);
            $result = $query->result_array();
            $get_total_rows = $query->num_rows();

            if ($get_total_rows > 0) {
                $result = $result[0];
                $order_user_id = $result["user_id"];
                $total_amount = $result["total_amount"];
                $date = $result["date"];
                $status = $result["status"];
                $promocode = $result["promocode"];
                $total_items = $result["total_items"];
                $pincode = $result["pincode"];
                $delivery_address = $result["delivery_address"];
                $country = $result["country"];
                $payment_method = $result["payment_method"];
                $name = $result["name"];
                $full_name = $result["full_name"];
                $phone = $result["phone"];
                $email = $result["email"];
                $socity_id = $result["socity_id"];
                $promocode_amount = $result["promocode_amount"];
                $cart_old = $result["cart_id"];
                $cart_id = explode(",", $cart_old);

                if ($cart_old) {

                    foreach ($cart_id as $id) {
                        $cardat = "SELECT * from cart where cart_id='$id'";
                        $query1 = $this->db->query($cardat);
                        $cart_data = $query1->result_array();
                        $get_total_rows = $query1->num_rows();
                        if ($get_total_rows > 0) {
                            $cart_data = $cart_data[0];
                            $user_id = $cart_data["user_id"];
                            $pro_id = $cart_data["product_id"];
                            $qty = $cart_data["qty"];
                            $cart_create = $this->db->query("INSERT INTO cart (qty, user_id, product_id) VALUES ('$qty', '$user_id', '$pro_id')");
                            $cart[] = $this->db->insert_id();
                            $cart_check = true;
                        } else {
                            $cart_check = false;
                        }
                    }

                    if ($cart_check) {
                        $cart = implode(',', $cart);
                        $order_create = $this->db->query("INSERT INTO sale ( user_id,cart_id,total_amount,date,promocode,total_items,pincode,delivery_address,country,payment_method,name,full_name,phone,email,socity_id,promocode_amount) VALUES ('$order_user_id','$cart','$total_amount','$date','$promocode','$total_items','$pincode','$delivery_address','$country','$payment_method','$name','$full_name','$phone','$email','$socity_id','$promocode_amount')");
                        $order_id = $this->db->insert_id();
                        if ($order_create) {
                            $cart_id_new = explode(",", $cart);
                            foreach ($cart_id_new as $id) {
                                $cart_create = $this->db->query("INSERT INTO  order_cart( order_id,cart_id) VALUES('$order_id','$id')");
                                $this->db->insert_id();
                            }

                            $data['response'] = true;
                            $data['order_id'] = $order_id;
                            $data['msg'] = "Add order Successfull";
                        } else {
                            $data['response'] = false;
                            $data['msg'] = "Add order Not Successfull";
                        }
                    } else {
                        $data['response'] = false;
                        $data['msg'] = "Incorrect Cart ID";
                    }
                }
            } else {
                $data['response'] = false;
                $data['msg'] = "Incorrect Order ID";
            }
        }
        echo json_encode($data);
    }

    /*     * ************************************************* */

    public function get_Product() {
        $restaurant = $this->input->post('restaurant_id');
        $cardat = "SELECT * from products ";
        $result = $this->db->query($cardat);
        $this->db->from('products');
        $this->db->where('in_stock="1"');
        $this->db->where('restaurant_id=' . $restaurant . '');
        $query = $this->db->get();
        $products = $query->result_array();
        $get_total_rows = $result->num_rows();
//        print_r()
        if ($get_total_rows > 0) {
            foreach ($products as $prow) {

                $response[] = array(
                    'product_id' => $prow['product_id'],
                    'product_name' => $prow['product_name'],
                    'category_id' => $prow['category_id'],
                    'product_description' => $prow['product_description']
                    ,
                    'price' => $prow['price'],
                    'product_image' => base_url('uploads/products/' . $prow['product_image']),
                    //'tax'=>$product->tax,
                    'restaurant_id' => $prow['restaurant_id'],
                    'status' => '0',
                    'in_stock' => $prow['in_stock'],
                );
            }

            echo json_encode(array("success" => true, "data" => $response));
        } else {
            $response['success'] = false;
            $response['message'] = "No Category Found";
            echo json_encode($response);
        }
    }

    /*     * ************************************************ */

    public function restaurant() {
        $cardat = "SELECT * from restaurant_register where status='1'";
        $result = $this->db->query($cardat);
        $this->db->from('restaurant_register');
        $query = $this->db->get();
        $restaurant_register = $query->result_array();
        $get_total_rows = $result->num_rows();
        if ($get_total_rows > 0) {
            foreach ($restaurant_register as $rrow) {
                $response[] = array(
                    'id' => $rrow['id'],
                    'restaurant_name' => $rrow['restaurant_name'],
                    'restaurant_address' => $rrow['restaurant_address'],
                    'restaurant_city' => $rrow['restaurant_city'],
                    'restaurant_state' => $rrow['restaurant_state'],
                    'restaurant_tax' => $rrow['tax'] . '%',
                    "restaurant_image" => $this->config->item('base_url') . 'uploads/restaurant/' . $rrow['image'],
                    'status' => $rrow['status'],
                );
            }

            echo json_encode(array("success" => true, "data" => $response));
        } else {

            $response['success'] = false;
            $response['message'] = "No Category Found";
            echo json_encode($response);
        }
    }

    /*     * *************************************************** */

    public function pincode() {
        $q = $this->db->query("Select * from pincode");
        echo json_encode($q->result());
    }

    function city() {
        $q = $this->db->query("SELECT * FROM `city`");
        $city["city"] = $q->result();
        echo json_encode($city);
    }

    function store() {
        $data = array();
        $_POST = $_REQUEST;
        $getdata = $this->input->post('city_id');
        if ($getdata != '') {
            $q = $this->db->query("Select user_fullname ,user_id FROM `users` where (user_city='" . $this->input->post('city_id') . "')");
            $data["data"] = $q->result();
            echo json_encode($data);
        } else {
            $data["data"] = "Error";
            echo json_encode($data);
        }
    }

    function get_products() {
        $this->load->model("product_model");
        $cat_id = "";
        if ($this->input->post("cat_id")) {
            $cat_id = $this->input->post("cat_id");
        }
        $search = $this->input->post("search");

        $data["response"] = true;
        $datas = $this->product_model->get_products(false, $cat_id, $search, $this->input->post("page"));
        //print_r( $datas);exit();
        foreach ($datas as $product) {
            $present = date('m/d/Y h:i:s a', time());
            $date1 = $product->start_date . " " . $product->start_time;
            $date2 = $product->end_date . " " . $product->end_time;

            if (strtotime($date1) <= strtotime($present) && strtotime($present) <= strtotime($date2)) {

                if (empty($product->deal_price)) {   ///Runing
                    $price = $product->price;
                } else {
                    $price = $product->deal_price;
                }
            } else {
                $price = $product->price; //expired
            }

            $data['data'][] = array(
                'product_id' => $product->product_id,
                'product_name' => $product->product_name,
                'product_name_arb' => $product->product_arb_name,
                'product_description_arb' => $product->product_arb_description,
                'category_id' => $product->category_id,
                'product_description' => $product->product_description,
                'deal_price' => '',
                'start_date' => "",
                'start_time' => "",
                'end_date' => "",
                'end_time' => "",
                'price' => $price,
                'mrp' => $product->mrp,
                'product_image' => $product->product_image,
                //'tax'=>$product->tax,
                'status' => '0',
                'in_stock' => $product->in_stock,
                'unit_value' => $product->unit_value,
                'unit' => $product->unit,
                'increament' => $product->increament,
                'rewards' => $product->rewards,
                'stock' => $product->stock,
                'title' => $product->title
            );
        }




        echo json_encode($data);
    }

    function get_products_suggestion() {
        $this->load->model("product_model");
        $cat_id = "";
        if ($this->input->post("cat_id")) {
            $cat_id = $this->input->post("cat_id");
        }
        $search = $this->input->post("search");

        //$data["response"] = true;  
        $data["data"] = $this->product_model->get_products_suggestion(false, $cat_id, $search, $this->input->post("page"));
        echo json_encode($data);
    }

    function get_time_slot() {

        $this->load->library('form_validation');
        $this->form_validation->set_rules('date', 'date', 'trim|required');
        if ($this->form_validation->run() == FALSE) {
            $data["response"] = false;
            $data["error"] = 'Warning! : ' . strip_tags($this->form_validation->error_string());
        } else {
            $date = date("Y-m-d", strtotime($this->input->post("date")));

            $time = date("H:i:s");



            $this->load->model("time_model");
            $time_slot = $this->time_model->get_time_slot();
            $cloasing_hours = $this->time_model->get_closing_hours($date);


            $begin = new DateTime($time_slot->opening_time);
            $end = new DateTime($time_slot->closing_time);

            $interval = DateInterval::createFromDateString($time_slot->time_slot . ' min');

            $times = new DatePeriod($begin, $interval, $end);
            $time_array = array();
            foreach ($times as $time) {
                if (!empty($cloasing_hours)) {
                    foreach ($cloasing_hours as $c_hr) {
                        if ($date == date("Y-m-d")) {
                            if (strtotime($time->format('h:i A')) > strtotime(date("h:i A")) && strtotime($time->format('h:i A')) > strtotime($c_hr->from_time) && strtotime($time->format('h:i A')) < strtotime($c_hr->to_time)) {
                                
                            } else {
                                $time_array[] = $time->format('h:i A') . ' - ' .
                                        $time->add($interval)->format('h:i A')
                                ;
                            }
                        } else {
                            if (strtotime($time->format('h:i A')) > strtotime($c_hr->from_time) && strtotime($time->format('h:i A')) < strtotime($c_hr->to_time)) {
                                
                            } else {
                                $time_array[] = $time->format('h:i A') . ' - ' .
                                        $time->add($interval)->format('h:i A')
                                ;
                            }
                        }
                    }
                } else {
                    if (strtotime($date) == strtotime(date("Y-m-d"))) {
                        if (strtotime($time->format('h:i A')) > strtotime(date("h:i A"))) {
                            $time_array[] = $time->format('h:i A') . ' - ' .
                                    $time->add($interval)->format('h:i A');
                        }
                    } else {
                        $time_array[] = $time->format('h:i A') . ' - ' .
                                $time->add($interval)->format('h:i A')
                        ;
                    }
                }
            }
            $data["response"] = true;
            $data["times"] = $time_array;
        }
        echo json_encode($data);
    }

    function text_for_send_order() {
        echo json_encode(array("data" => "<p>Our delivery boy will come withing your choosen time and will deliver your order. \n 
            </p>"));
    }

    function send_order() {
        $this->load->library('form_validation');
        $this->form_validation->set_rules('user_id', 'User ID', 'trim|required');
        $this->form_validation->set_rules('date', 'Date', 'trim|required');
        $this->form_validation->set_rules('time', 'Time', 'trim|required');
        $this->form_validation->set_rules('data', 'data', 'trim|required');
        $this->form_validation->set_rules('location', 'Location', 'trim|required');
        if ($this->form_validation->run() == FALSE) {
            $data["response"] = false;
            $data["error"] = 'Warning! : ' . strip_tags($this->form_validation->error_string());
        } else {
            $ld = $this->db->query("select user_location.*, socity.* from user_location
                    inner join socity on socity.socity_id = user_location.socity_id
                     where user_location.location_id = '" . $this->input->post("location") . "' limit 1");
            $location = $ld->row();

            $store_id = $this->input->post("store_id");
            $payment_method = $this->input->post("payment_method");
            $sales_id = $this->input->post("sales_id");
            $date = date("Y-m-d", strtotime($this->input->post("date")));
            //$timeslot = explode("-",$this->input->post("timeslot"));

            $times = explode('-', $this->input->post("time"));
            $fromtime = date("h:i a", strtotime(trim($times[0])));
            $totime = date("h:i a", strtotime(trim($times[1])));


            $user_id = $this->input->post("user_id");
            $insert_array = array("user_id" => $user_id,
                "on_date" => $date,
                "delivery_time_from" => $fromtime,
                "delivery_time_to" => $totime,
                "delivery_address" => $location->house_no . "\n, " . $location->house_no,
                "socity_id" => $location->socity_id,
                "delivery_charge" => $location->delivery_charge,
                "location_id" => $location->location_id,
                "payment_method" => $payment_method,
                "new_store_id" => $store_id
            );
            $this->load->model("common_model");
            $id = $this->common_model->data_insert("sale", $insert_array);

            $data_post = $this->input->post("data");
            $data_array = json_decode($data_post);
            $total_rewards = 0;
            $total_price = 0;
            $total_kg = 0;
            $total_items = array();
            foreach ($data_array as $dt) {
                $qty_in_kg = $dt->qty;
                if ($dt->unit == "gram") {
                    $qty_in_kg = ($dt->qty * $dt->unit_value) / 1000;
                }
                $total_rewards = $total_rewards + ($dt->qty * $dt->rewards);
                $total_price = $total_price + ($dt->qty * $dt->price);
                $total_kg = $total_kg + $qty_in_kg;
                $total_items[$dt->product_id] = $dt->product_id;

                $array = array("product_id" => $dt->product_id,
                    "qty" => $dt->qty,
                    "unit" => $dt->unit,
                    "unit_value" => $dt->unit_value,
                    "sale_id" => $id,
                    "price" => $dt->price,
                    "qty_in_kg" => $qty_in_kg,
                    "rewards" => $dt->rewards
                );
                $this->common_model->data_insert("sale_items", $array);
            }

            if ($this->input->post("total_ammount") != "" || $this->input->post("total_ammount") != 0) {
                $total_price = $this->input->post("total_ammount");
            }
            //$total_price = $total_price + $location->delivery_charge;

            $this->db->query("Update sale set total_amount = '" . $total_price . "', total_kg = '" . $total_kg . "', total_items = '" . count($total_items) . "', total_rewards = '" . $total_rewards . "' where sale_id = '" . $id . "'");

            $data["response"] = true;
            $data["data"] = addslashes("<p>Your order No #" . $id . " is send success fully \n Our delivery person will delivered order \n                         between " . $fromtime . " to " . $totime . " on " . $date . " \n Please keep <strong>" . $total_price . "</strong> on delivery Thanks for being with Us.</p>");

            $data["data_arb"] = addslashes("<p> تم إرسال طلبك رقم  #" . $id . " بنجاح . سوف يقوم موظف التسليم الخاص بنا بتسليم الطلب بين الساعة  " . $fromtime . "ص و  " . $totime . " ص في  " . $date . " \n . الرجاء الاحتفاظ بالرقم  <strong>" . $total_price . "</strong> عند التسليم . شكراً لكونك معنا..</p>");
        }
        echo json_encode($data);
    }

    function my_orders() {
        $this->load->library('form_validation');
        $this->form_validation->set_rules('user_id', 'User ID', 'trim|required');
        if ($this->form_validation->run() == FALSE) {
            $data["response"] = false;
            $data["error"] = 'Warning! : ' . strip_tags($this->form_validation->error_string());
        } else {
            //$this->load->model("product_model");
            $user_id = $this->input->post("user_id");
            $q = $this->db->query("Select sale_id,user_id,cart_id,total_items,total_amount,promocode,promocode_amount,socity_id,delivery_address,pincode,payment_method,name,full_name,email,phone,date FROM `sale` where user_id = '" . $user_id . "' and status != 3 ORDER BY sale_id DESC");
            $data_da = $q->result();
            $data["response"] = true;
            $data["data"] = $data_da;
        }
        echo json_encode($data);
    }

    function delivered_complete() {
        $this->load->library('form_validation');
        $this->form_validation->set_rules('user_id', 'User ID', 'trim|required');
        if ($this->form_validation->run() == FALSE) {
            $data["response"] = false;
            $data["error"] = 'Warning! : ' . strip_tags($this->form_validation->error_string());
        } else {
            $this->load->model("product_model");
            $data = $this->product_model->get_sale_by_user2($this->input->post("user_id"));
        }
        echo json_encode($data);
    }

    function order_details() {
        $this->load->library('form_validation');
        $this->form_validation->set_rules('sale_id', 'Sale ID', 'trim|required');
        $sale_id = $this->input->post("sale_id");
        if ($this->form_validation->run() == FALSE) {
            $data["response"] = false;
            $data["error"] = 'Warning! : ' . strip_tags($this->form_validation->error_string());
        } else {
            $q = $this->db->query("Select sale_id,user_id,cart_id,total_items,total_amount,promocode,promocode_amount,socity_id,delivery_address,pincode,payment_method,name,full_name,email,phone,date FROM `sale` where sale_id = '" . $sale_id . "' and status != 3 ORDER BY sale_id DESC");
            $data_da['order']['details'] = $q->result();
            $ca = $this->db->query("SELECT order_cart.order_id,order_cart.cart_id,products.product_id as product_id,products.product_name,cart.qty,restaurant_register.restaurant_name,restaurant_register.image as rest_image,products.price FROM `order_cart`
                                    LEFT JOIN cart on cart.cart_id=order_cart.cart_id 
                                    LEFT JOIN products on cart.product_id=products.product_id
                                    LEFT JOIN restaurant_register on restaurant_register.id= products.restaurant_id
                                    WHERE order_cart.order_id='$sale_id'");
            $data_da['order']['cart'] = $ca->result();

            $data["response"] = true;
            $data["data"] = $data_da;
        }
        echo json_encode($data);
    }

    function cancel_order() {
        $this->load->library('form_validation');
        $this->form_validation->set_rules('sale_id', 'Sale ID', 'trim|required');
        $this->form_validation->set_rules('user_id', 'User ID', 'trim|required');
        if ($this->form_validation->run() == FALSE) {
            $data["response"] = false;
            $data["error"] = 'Warning! : ' . strip_tags($this->form_validation->error_string());
        } else {
            $this->db->query("Update sale set status = 3 where user_id = '" . $this->input->post("user_id") . "' and  sale_id = '" . $this->input->post("sale_id") . "' ");
            $this->db->delete('sale_items', array('sale_id' => $this->input->post("sale_id")));
            $data["response"] = true;
            $data["message"] = "Your order cancel successfully";
        }
        echo json_encode($data);
    }

    function get_society() {

        $this->load->model("product_model");
        $data = $this->product_model->get_socities();

        echo json_encode($data);
    }

    function get_varients_by_id() {
        $this->load->library('form_validation');
        $this->form_validation->set_rules('ComaSepratedIdsString', 'IDS', 'trim|required');
        if ($this->form_validation->run() == FALSE) {
            $data["response"] = false;
            $data["error"] = 'Warning! : ' . strip_tags($this->form_validation->error_string());
        } else {
            $this->load->model("product_model");
            $data = $this->product_model->get_prices_by_ids($this->input->post("ComaSepratedIdsString"));
        }
        echo json_encode($data);
    }

    function get_sliders() {
        $q = $this->db->query("Select * from slider");
        echo json_encode($q->result());
    }

    function get_banner() {
        $q = $this->db->query("Select * from banner");
        echo json_encode($q->result());
    }

    function get_feature_banner() {
        $q = $this->db->query("Select * from feature_slider");
        echo json_encode($q->result());
    }

    function get_limit_settings() {
        $q = $this->db->query("Select * from settings");
        echo json_encode($q->result());
    }

    function add_address() {
        $this->load->library('form_validation');
        $this->form_validation->set_rules('user_id', 'Pincode', 'trim|required');
        $this->form_validation->set_rules('pincode', 'Pincode ID', 'trim|required');
        $this->form_validation->set_rules('socity_id', 'Socity', 'trim|required');
        $this->form_validation->set_rules('house_no', 'House', 'trim|required');
        if ($this->form_validation->run() == FALSE) {
            $data["response"] = false;
            $data["error"] = strip_tags($this->form_validation->error_string());
        } else {
            $user_id = $this->input->post("user_id");
            $pincode = $this->input->post("pincode");
            $socity_id = $this->input->post("socity_id");
            $house_no = $this->input->post("house_no");
            $receiver_name = $this->input->post("receiver_name");
            $receiver_mobile = $this->input->post("receiver_mobile");

            $array = array(
                "user_id" => $user_id,
                "pincode" => $pincode,
                "socity_id" => $socity_id,
                "house_no" => $this->input->post("house_no"),
                "receiver_name" => $receiver_name,
                "receiver_mobile" => $receiver_mobile
            );

            $this->db->insert("user_location", $array);
            $insert_id = $this->db->insert_id();
            $q = $this->db->query("Select user_location.*,
                    socity.* from user_location 
                    inner join socity on socity.socity_id = user_location.socity_id
                    where location_id = '" . $insert_id . "'");
            $data["response"] = true;
            $data["data"] = $q->row();
        }
        echo json_encode($data);
    }

    public function edit_address() {
        $data = array();
        $this->load->library('form_validation');
        /* add users table validation */
        $this->form_validation->set_rules('pincode', 'Pincode', 'trim|required');
        $this->form_validation->set_rules('socity_id', 'Socity ID', 'trim|required');
        $this->form_validation->set_rules('house_no', 'House Number', 'trim|required');
        $this->form_validation->set_rules('receiver_name', 'Receiver Name', 'trim|required');
        $this->form_validation->set_rules('receiver_mobile', 'Receiver Mobile', 'trim|required');
        $this->form_validation->set_rules('location_id', 'Location ID', 'trim|required');

        if ($this->form_validation->run() == FALSE) {
            $data["response"] = false;
            $data["error"] = 'Warning! : ' . strip_tags($this->form_validation->error_string());
        } else {
            $insert_array = array(
                "pincode" => $this->input->post("pincode"),
                "socity_id" => $this->input->post("socity_id"),
                "house_no" => $this->input->post("house_no"),
                "receiver_name" => $this->input->post("receiver_name"),
                "receiver_mobile" => $this->input->post("receiver_mobile")
            );

            $this->load->model("common_model");


            $this->common_model->data_update("user_location", $insert_array, array("location_id" => $this->input->post("location_id")));


            $data["response"] = true;
            $data["data"] = "Your Address Update successfully";
        }

        echo json_encode($data);
    }

    /* Delete Address */

    public function delete_address() {
        $this->load->library('form_validation');
        $this->form_validation->set_rules('location_id', 'Location ID', 'trim|required');

        if ($this->form_validation->run() == FALSE) {
            $data["response"] = false;
            $data["error"] = 'field is required';
        } else {

            $this->db->delete("user_location", array("location_id" => $this->input->post("location_id")));

            $data["response"] = true;
            $data["message"] = 'Your Address deleted successfully...';
        }
        echo json_encode($data);
    }

    /* End Delete  Address */

    function get_address() {
        $this->load->library('form_validation');
        $this->form_validation->set_rules('user_id', 'User', 'trim|required');

        if ($this->form_validation->run() == FALSE) {
            $data["response"] = false;
            $data["error"] = strip_tags($this->form_validation->error_string());
        } else {
            $user_id = $this->input->post("user_id");

            $q = $this->db->query("Select user_location.*,
                    socity.* from user_location 
                    inner join socity on socity.socity_id = user_location.socity_id
                    where user_id = '" . $user_id . "'");
            $data["response"] = true;
            $data["data"] = $q->result();
        }
        echo json_encode($data);
    }

    /* contact us */

    public function support() {

        $q = $this->db->query("select * from `pageapp` WHERE id =1");


        $data["response"] = true;
        $data['data'] = $q->result();


        echo json_encode($data);
    }

    /* end contact us */

    /* about us */

    public function aboutus() {

        $q = $this->db->query("select * from `pageapp` where id=2");


        $data["response"] = true;
        $data['data'] = $q->result();


        echo json_encode($data);
    }

    /* end about us */
    /* about us */

    public function terms() {

        $q = $this->db->query("select * from `pageapp` where id=3");


        $data["response"] = true;
        $data['data'] = $q->result();


        echo json_encode($data);
    }

    /* end about us */

    public function register_fcm() {
        $data = array();
        $this->load->library('form_validation');
        $this->form_validation->set_rules('user_id', 'User ID', 'trim|required');
        $this->form_validation->set_rules('token', 'Token', 'trim|required');
        $this->form_validation->set_rules('device', 'Device', 'trim|required');
        if ($this->form_validation->run() == FALSE) {
            $data["response"] = false;
            $data["error"] = $this->form_validation->error_string();
        } else {
            $device = $this->input->post("device");
            $token = $this->input->post("token");
            $user_id = $this->input->post("user_id");

            $field = "";
            if ($device == "android") {
                $field = "user_ios_token";
            } else if ($device == "ios") {
                $field = "user_ios_token";
            }
            if ($field != "") {
                $this->db->query("update registers set " . $field . " = '" . $token . "' where user_id = '" . $user_id . "'");
                $data["response"] = true;
            } else {
                $data["response"] = false;
                $data["error"] = "Device type is not set";
            }
        }
        echo json_encode($data);
    }

    public function test_fcm() {
        $message["title"] = "test";
        $message["message"] = "grocery test";
        $message["image"] = "";
        $message["created_at"] = date("Y-m-d");

        $this->load->helper('gcm_helper');
        $gcm = new GCM();
        // $result = $gcm->send_notification(array("AIzaSyCeC9WQR38Sbg7EAM40YVxZGgVSOOAxwjE"),$message ,"android");
        // $result= $gcm->send_topics("/topics/grocery",$message ,"android");
        // $result = $gcm->send_notification(array("AIzaSyCeC9WQR38Sbg7EAM40YVxZGgVSOOAxwjE"),$message ,"android");
        $result = $gcm->send_topics("gorocer", $message, "android");
        //print_r($result);
        echo $result;
    }

    /* Forgot Password */

    public function send_email_verified_mail($email, $token, $name) {
        $message = $this->load->view('users/modify_password', array("name" => $name, "active_link" => site_url("users/verify_email?email=" . $email . "&token=" . $token)), TRUE);

        $config['mailtype'] = 'html';
        $this->email->initialize($config);
        $this->email->to($email);
        $this->email->from('saurabh.rawat@tecmanic.com', 'Saurabh Rawat');
        $this->email->subject('Forgot password request');
        $this->email->message('Hi ' . $name . ' \n Your password forgot request is accepted plase visit following link to change your password. \n
                                ' . base_url() . 'users/modify_password/' . $token);

        return $this->email->send();
    }

    /* End Forgot Password */

    public function wallet() {
        $data = array();
        $_POST = $_REQUEST;
        if ($this->input->get('user_id') == "") {
            
        } else {
            $q = $this->db->query("Select * from registers where(user_id='" . $this->input->get('user_id') . "' ) Limit 1");
            error_reporting(0);
            if ($q->num_rows() > 0) {

                $row = $q->row();

                $data["response"] = true;
                $data = array("success" => success, "wallet" => $row->wallet);
            } else {
                $data = array("success" => unsucess, "wallet" => 0);
            }
        }
        echo json_encode($data);
    }

    public function rewards() {
        $data = array();
        $_GET = $_REQUEST;
        if ($this->input->get('user_id') == "") {
            $data = array("success" => unsucess, "total_rewards" => 0);
        } else {
            // $q = $this->db->query("Select sum(total_rewards) AS total_rewards from `delivered_order` where(user_id='".$this->input->get('user_id')."' )");
            $q = $this->db->query("Select rewards from `registers` where(user_id='" . $this->input->get('user_id') . "' )");
            error_reporting(0);
            if ($q->num_rows() > 0) {

                $row = $q->row();

                $data["response"] = true;
                $data = array("success" => success, "total_rewards" => $row->rewards);
            } else {
                $data = array("success" => hastalavista, "total_rewards" => 0);
            }
        }
        echo json_encode($data);
    }

    public function shift() {
        $data = array();
        $_POST = $_REQUEST;
        if ($this->input->post('user_id') == "") {
            $data = array("success" => unsucess, "total_rewards" => 0);
        } else {
            error_reporting(0);
            $amount = $this->input->post('amount');
            $rewards = $this->input->post('rewards');
            //$user_id=$this->input->post('user_id');
            //$final_amount=$amount+$rewards;
            //$reward_value = $rewards*.50; 
            $final_rewards = 0;


            $select = $this->db->query("SELECT * from rewards where id=1");
            if ($select->num_rows() > 0) {
                $row = $select->row_array();
                $point = $row['point'];
            }

            $reward_value = $point * $rewards;
            $final_amount = $amount + $reward_value;
            $data["wallet_amount"] = [array("final_amount" => $final_amount, "final_rewards" => 0, "amount" => $amount, "rewards" => $rewards, "pont" => $point)];
            $this->db->query("delete from delivered_order where user_id = '" . $this->input->post('user_id') . "'");
            $this->db->query("UPDATE `registers` SET wallet='" . $final_amount . "', rewards='0' where(user_id='" . $this->input->post('user_id') . "' )");
        }
        echo json_encode($data);
    }

    public function wallet_on_checkout() {
        $data = array();
        $_POST = $_REQUEST;
        if ($this->input->post('wallet_amount') >= $this->input->post('total_amount')) {
            $wallet_amount = $this->input->post('wallet_amount');
            $amount = $this->input->post('total_amount');

            $final_amount = $wallet_amount - $amount;
            $balance = 0;

            $data["final_amount"] = [array("wallet" => $final_amount, "total" => $balance)];
        }
        if ($this->input->post('wallet_amount') <= $this->input->post('total_amount')) {
            $wallet_amount = $this->input->post('wallet_amount');
            $amount = $this->input->post('total_amount');

            $final_amount = 0;
            $balance = $amount - $wallet_amount;

            $data["final_amount"] = [array("wallet" => $final_amount, "total" => $balance, "used_wallet" => $wallet_amount)];
        } else {
            
        }
        echo json_encode($data);
    }

    public function recharge_wallet() {
        $data = array();
        $_POST = $_REQUEST;

        $q = $this->db->query("Select wallet from `registers` where(user_id='" . $this->input->post('user_id') . "' )");
        error_reporting(0);
        if ($q->num_rows() > 0) {

            $row = $q->row();

            $current_amount = $q->row()->wallet;
            $request_amount = $this->input->post('wallet_amount');

            $new_amount = $current_amount + $request_amount;
            $this->db->query("UPDATE `registers` SET wallet='" . $new_amount . "' where(user_id='" . $this->input->post('user_id') . "' )");

            $data = array("success" => success, "wallet_amount" => "$new_amount");
        }
        echo json_encode($data);
    }

    public function deelOfDay() {
        $data = array();
        $_POST = $_REQUEST;
        error_reporting(0);
        $q = $this->db->get('deelofday');
        $data[response] = "true";
        $data[Deal_of_the_day] = $q->result();
        echo json_encode($data);
    }

    public function top_selling_product() {
        $data = array();
        $_POST = $_REQUEST;
        error_reporting(0);
        $q = $this->db->query("select p.*,dp.start_date,dp.start_time,dp.end_time,dp.deal_price,c.title,count(si.product_id) as top,si.product_id from products p INNER join sale_items si on p.product_id=si.product_id INNER join categories c ON c.id=p.category_id left join deal_product dp on dp.product_id=si.product_id GROUP BY si.product_id order by top DESC LIMIT 4");
        $data[response] = "true";
        //print_r($q->result());exit();
        //$data[top_selling_product] = $q->result();
        foreach ($q->result() as $product) {
            $present = date('m/d/Y h:i:s a', time());
            $date1 = $product->start_date . " " . $product->start_time;
            $date2 = $product->start_date . " " . $product->end_time;

            if (strtotime($date1) <= strtotime($present) && strtotime($present) <= strtotime($date2)) {

                if (empty($product->deal_price)) {   ///Runing
                    $price = $product->price;
                } else {
                    $price = $product->deal_price;
                }
            } else {
                $price = $product->price; //expired
            }

            $data[top_selling_product][] = array(
                'product_id' => $product->product_id,
                'product_name' => $product->product_name,
                'product_name_arb' => $product->product_arb_name,
                'product_description_arb' => $product->product_arb_description,
                'category_id' => $product->category_id,
                'product_description' => $product->product_description,
                'deal_price' => '',
                'start_date' => '',
                'start_time' => '',
                'end_date' => '',
                'end_time' => '',
                'price' => $price,
                'mrp' => $product->mrp,
                'product_image' => $product->product_image,
                'status' => '',
                'in_stock' => $product->in_stock,
                'unit_value' => $product->unit_value,
                'unit' => $product->unit,
                'increament' => $product->increament,
                'rewards' => $product->rewards,
                'stock' => '',
                'title' => $product->title
            );
        }



        echo json_encode($data);
    }

    public function get_all_top_selling_product() {
        $data = array();
        $_POST = $_REQUEST;
        error_reporting(0);
        if ($this->input->post('top_selling_product')) {
            //$q = $this->db->query("select p.*,dp.start_date,dp.start_time,dp.end_time,dp.deal_price,c.title,count(si.product_id) as top,si.product_id from products p INNER join //sale_items si on p.product_id=si.product_id INNER join categories c ON c.id=p.category_id left join deal_product dp on dp.product_id=si.product_id GROUP BY si.product_id //order by top DESC LIMIT 8");


            $q = $this->db->query("Select dp.*,products.*, ( ifnull (producation.p_qty,0) - ifnull(consuption.c_qty,0)) as stock ,categories.title from products 
            inner join categories on categories.id = products.category_id
            left outer join(select SUM(qty) as c_qty,product_id from sale_items group by product_id) as consuption on consuption.product_id = products.product_id 
            left outer join(select SUM(qty) as p_qty,product_id from purchase group by product_id) as producation on producation.product_id = products.product_id
           left join deal_product dp on dp.product_id=products.product_id where 1 " . $filter . " " . $limit);
            //$products =$q->result();  

            $data[response] = "true";
            foreach ($q->result() as $product) {
                $present = date('m/d/Y h:i:s a', time());
                $date1 = $product->start_date . " " . $product->start_time;
                $date2 = $product->end_date . " " . $product->end_time;

                if (strtotime($date1) <= strtotime($present) && strtotime($present) <= strtotime($date2)) {

                    if (empty($product->deal_price)) {   ///Runing
                        $price = $product->price;
                    } else {
                        $price = $product->deal_price;
                    }
                } else {
                    $price = $product->price; //expired
                }

                $data[top_selling_product][] = array(
                    'product_id' => $product->product_id,
                    'product_name' => $product->product_name,
                    'product_name_arb' => $product->product_arb_name,
                    'product_description_arb' => $product->product_arb_description,
                    'category_id' => $product->category_id,
                    'product_description' => $product->product_description,
                    'deal_price' => '',
                    'start_date' => '',
                    'start_time' => '',
                    'end_date' => '',
                    'end_time' => '',
                    'price' => $price,
                    'mrp' => $product->mrp,
                    'product_image' => $product->product_image,
                    'status' => '',
                    'in_stock' => $product->in_stock,
                    'unit_value' => $product->unit_value,
                    'unit' => $product->unit,
                    'increament' => $product->increament,
                    'rewards' => $product->rewards,
                    'stock' => $product->stock,
                    'title' => $product->title
                );
            }
        }
        echo json_encode($data);
    }

    public function deal_product() {

        $data = array();
        $_POST = $_REQUEST;
        error_reporting(0);

        $q = $this->db->query("SELECT deal_product.*,products.*,categories.title from deal_product 
inner JOIN products on deal_product.product_name = products.product_name 
INNER JOIN categories on categories.id=products.category_id limit 4");

        // $this->db->query("SELECT dp.*,p.*,c.title from deal_product dp inner JOIN products p on dp.product_name = p.product_name INNER JOIN categories c on c.id=p.category_id limit 4");

        $data['response'] = "true";
        // $data['Deal_of_the_day']=array();
        foreach ($q->result() as $product) {

            $present = date('d/m/Y H:i ', time());
            $date1 = $product->start_date . " " . $product->start_time;
            $date2 = $product->end_date . " " . $product->end_time;

            if ($date1 <= $present && $present <= $date2) {
                $status = 1; //running 
            } else if ($date1 > $present) {
                $status = 2; //is going to 
            } else {
                $status = 0; //expired
            }

            // if(strtotime($date1) <= strtotime($present) && strtotime($present) <=strtotime($date2))
            // {
            //   $status = 1;//running 
            // }else if(strtotime($date1) > strtotime($present)){
            //  $status = 2;//is going to
            // }else{
            //  $status = 0;//expired
            // } 

            $data['Deal_of_the_day'][] = array(
                'product_id' => $product->product_id,
                'product_name' => $product->product_name,
                'product_name_arb' => $product->product_arb_name,
                'product_description_arb' => $product->product_arb_description,
                'product_description' => $product->product_description,
                'deal_price' => $product->deal_price,
                'start_date' => $product->start_date,
                'start_time' => $product->start_time,
                'end_date' => $product->end_date,
                'end_time' => $product->end_time,
                'price' => $product->price,
                'mrp' => $product->mrp,
                'product_image' => $product->product_image,
                'status' => $status,
                'in_stock' => $product->in_stock,
                'unit_value' => $product->unit_value,
                'unit' => $product->unit,
                'increament' => $product->increament,
                'rewards' => $product->rewards,
                'title' => $product->title
            );
        }
        echo json_encode($data);
    }

    public function get_all_deal_product() {

        $data = array();
        $_POST = $_REQUEST;
        error_reporting(0);

        if ($this->input->post('dealproduct')) {
            $q = $this->db->query("Select dp.*,products.*, ( ifnull (producation.p_qty,0) - ifnull(consuption.c_qty,0)) as stock ,categories.title from deal_product dp
			left join  products on products.product_name=dp.product_name
            inner join categories on categories.id = products.category_id
            left outer join (select SUM(qty) as c_qty,product_id from sale_items group by product_id) as consuption on consuption.product_id = products.product_id 
            left outer join(select SUM(qty) as p_qty,product_id from purchase group by product_id) as producation on producation.product_id = products.product_id
            where 1 " . $filter . " " . $limit);


            //   $this->db->query("SELECT dp.*,p.*,c.title from deal_product dp 
            //   inner JOIN products p on dp.product_name = p.product_name 
            //   INNER JOIN categories c on c.id=p.category_id");
        }
        $data['response'] = "true";
        //$data['Deal_of_the_day'][]=array();
        foreach ($q->result() as $product) {
            $present = date('d/m/Y H:i:s ', time());
            $date1 = $product->start_date . " " . $product->start_time;
            $date2 = $product->end_date . " " . $product->end_time;

            if ($date1 <= $present && $present <= $date2) {

                if (empty($product->deal_price)) {   ///Runing
                    $price = $product->price;
                } else {
                    $price = $product->deal_price;
                }
            } else {
                $price = $product->price; //expired
            }


            $data['Deal_of_the_day'][] = array(
                'product_id' => $product->product_id,
                'product_name' => $product->product_name,
                'product_name_arb' => $product->product_arb_name,
                'product_description_arb' => $product->product_arb_description,
                'category_id' => $product->category_id,
                'product_description' => $product->product_description,
                'deal_price' => $product->deal_price,
                'start_date' => $product->start_date,
                'start_time' => $product->start_time,
                'end_date' => $product->end_date,
                'end_time' => $product->end_time,
                'mrp' => $product->mrp,
                'price' => $price,
                'product_image' => $product->product_image,
                'status' => $product->in_stock,
                'in_stock' => $product->in_stock,
                'unit_value' => $product->unit_value,
                'unit' => $product->unit,
                'increament' => $product->increament,
                'rewards' => $product->rewards,
                'stock' => $product->stock,
                'title' => $product->title
            );
        }
        echo json_encode($data);
    }

    public function icon() {
        $parent = 0;
        if ($this->input->post("parent")) {
            $parent = $this->input->post("parent");
        }
        $categories = $this->get_header_categories_short($parent, 0, $this);
        $data["response"] = true;
        $data["data"] = $categories;
        echo json_encode($data);
    }

    public function get_header_categories_short($parent, $level, $th) {
        $q = $th->db->query("Select a.*, ifnull(Deriv1.Count , 0) as Count, ifnull(Total1.PCount, 0) as PCount FROM `header_categories` a  LEFT OUTER JOIN (SELECT `parent`, COUNT(*) AS Count FROM `header_categories` GROUP BY `parent`) Deriv1 ON a.`id` = Deriv1.`parent` 
                         LEFT OUTER JOIN (SELECT `category_id`,COUNT(*) AS PCount FROM `header_products` GROUP BY `category_id`) Total1 ON a.`id` = Total1.`category_id` 
                         WHERE a.`parent`=" . $parent);

        $return_array = array();

        foreach ($q->result() as $row) {
            if ($row->Count > 0) {
                $sub_cat = $this->get_header_categories_short($row->id, $level + 1, $th);
                $row->sub_cat = $sub_cat;
            } elseif ($row->Count == 0) {
                
            }
            $return_array[] = $row;
        }
        return $return_array;
    }

    function get_header_products() {
        $this->load->model("product_model");
        $cat_id = "";
        if ($this->input->post("cat_id")) {
            $cat_id = $this->input->post("cat_id");
        }
        $search = $this->input->post("search");

        $data["response"] = true;
        $datas = $this->product_model->get_header_products(false, $cat_id, $search, $this->input->post("page"));

        foreach ($datas as $product) {
            $data['data'][] = array(
                'product_id' => $product->product_id,
                'product_name' => $product->product_name,
                'product_name_arb' => $product->product_arb_name,
                'product_description_arb' => $product->product_arb_description,
                'category_id' => $product->category_id,
                'product_description' => $product->product_description,
                'deal_price' => "",
                'start_date' => "",
                'start_time' => "",
                'end_date' => "",
                'end_time' => "",
                'price' => $product->price,
                'product_image' => $product->product_image,
                'status' => '0',
                'in_stock' => $product->in_stock,
                'unit_value' => $product->unit_value,
                'unit' => $product->unit,
                'increament' => $product->increament,
                'rewards' => $product->rewards,
                'stock' => $product->stock,
                'title' => $product->title
            );
        }
        echo json_encode($data);
    }

    public function get_coupons() {
        $q = $this->db->query("SELECT * FROM `coupons` where coupon_code='" . $this->input->post("coupon_code") . "'");

        if ($q->result() > 0) {
            foreach ($q->result() as $row) {
                if ($row->valid_from <= date('d/m/Y') && $row->valid_to >= date('d/m/Y')) {
                    if ($row->cart_value <= $this->input->post("payable_amount")) {
                        $payable_amount = $this->input->post("payable_amount");
                        $coupon_amount = $row->discount_value;
                        $new_amount = $payable_amount - $coupon_amount;
                        $data["response"] = true;
                        $data["msg"] = "Coupon code apply successfully ";
                        $data["Total_amount"] = $new_amount;
                        $data["coupon_value"] = $coupon_amount;
                    } else {
                        $data["response"] = false;
                        $data["msg"] = "Your Cart Amount is not Enough For This Coupon ";
                        $data["Total_amount"] = $this->input->post("payable_amount");
                        $data["coupon_value"] = 0;
                    }
                } else {
                    $data["response"] = false;
                    $data["msg"] = "This coupon is Expired";
                    $data["Total_amount"] = $this->input->post("payable_amount");
                    $data["coupon_value"] = 0;
                }
            }
        } else {
            $data["response"] = false;
            $data["msg"] = "Invalid Coupon";
            $data["Total_amount"] = $this->input->post("payable_amount");
            $data["coupon_value"] = 0;
        }

        echo json_encode($data);
    }

    public function get_sub_cat() {
        $parent = 0;
        if ($this->input->post("sub_cat") != 0) {
            $q = $this->db->query("SELECT * FROM `categories` where id='" . $this->input->post("sub_cat") . "'");
            $data["response"] = true;
            $data["subcat"] = $q->result();
            echo json_encode($data);
        } else {
            $data["response"] = false;
            $data["subcat"] = "";
            echo json_encode($data);
        }
    }

    public function delivery_boy() {

        $q = $this->db->query("select id,user_name from `delivery_boy` where user_status=1");
        $data['delivery_boy'] = $q->result();

        echo json_encode($data);
    }

    public function delivery_boy_login() {
        error_reporting(0);
        $data = array();

        $this->load->library('form_validation');
        $this->form_validation->set_rules('password', 'Password', 'trim|required');

        if (!$this->input->post('user_password')) {
            $data["response"] = false;
            $data["error"] = strip_tags($this->form_validation->error_string());
        } else {
            //users.user_email='".$this->input->post('user_email')."' or
            $q = $this->db->query("Select * from delivery_boy where user_password='" . $this->input->post('user_password') . "'");

            if ($q->result() > 0) {
                $row = $q->result();
                $access = $row->user_status;
                if ($access == '0') {
                    $data["response"] = false;
                    $data["data"] = 'Your account currently inactive.Please Contact Admin';
                } else {
                    //$error_reporting(0);
                    $data["response"] = true;
                    $q = $this->db->query("Select id,user_name from delivery_boy where user_password='" . $this->input->post('user_password') . "'");
                    $product = $q->result();
                    $data['product'] = $product;
                }
            } else {
                $data["response"] = false;
                $data["data"] = 'Invalide Username or Passwords';
            }
        }
        echo json_encode($data);
    }

    public function add_purchase() {
        if (_is_user_login($this)) {

            if (isset($_POST)) {
                $this->load->library('form_validation');
                $this->form_validation->set_rules('product_id', 'product_id', 'trim|required');
                $this->form_validation->set_rules('qty', 'Qty', 'trim|required');
                $this->form_validation->set_rules('unit', 'Unit', 'trim|required');
                if ($this->form_validation->run() == FALSE) {
                    if ($this->form_validation->error_string() != "")
                        $this->session->set_flashdata("message", '<div class="alert alert-warning alert-dismissible" role="alert">
                                        <i class="fa fa-warning"></i>
                                      <button type="button" class="close" data-dismiss="alert"><span aria-hidden="true">&times;</span><span class="sr-only">Close</span></button>
                                      <strong>Warning!</strong> ' . $this->form_validation->error_string() . '
                                    </div>');
                }
                else {

                    $this->load->model("common_model");
                    $array = array(
                        "product_id" => $this->input->post("product_id"),
                        "qty" => $this->input->post("qty"),
                        "price" => $this->input->post("price"),
                        "unit" => $this->input->post("unit"),
                        "store_id_login" => $this->input->post("store_id_login")
                    );
                    $this->common_model->data_insert("purchase", $array);

                    $this->session->set_flashdata("message", '<div class="alert alert-success alert-dismissible" role="alert">
                                        <i class="fa fa-check"></i>
                                      <button type="button" class="close" data-dismiss="alert"><span aria-hidden="true">&times;</span><span class="sr-only">Close</span></button>
                                      <strong>Success!</strong> Your request added successfully...
                                    </div>');
                    redirect("admin/add_purchase");
                }

                $this->load->model("product_model");
                $data["purchases"] = $this->product_model->get_purchase_list();
                $data["products"] = $this->product_model->get_products();
                $this->load->view("admin/product/purchase", $data);
            }
        }
    }

    public function stock() {
        $this->load->model("product_model");
        $cat_id = "";
        if ($this->input->post("cat_id")) {
            $cat_id = $this->input->post("cat_id");
        }
        $search = $this->input->post("search");

        $datas = $this->product_model->get_products(false, $cat_id, $search, $this->input->post("page"));
        //print_r( $datas);exit();
        foreach ($datas as $product) {
            $present = date('m/d/Y h:i:s a', time());
            $date1 = $product->start_date . " " . $product->start_time;
            $date2 = $product->end_date . " " . $product->end_time;

            if (strtotime($date1) <= strtotime($present) && strtotime($present) <= strtotime($date2)) {

                if (empty($product->deal_price)) {   ///Runing
                    $price = $product->price;
                } else {
                    $price = $product->deal_price;
                }
            } else {
                $price = $product->price; //expired
            }

            $data['products'][] = array(
                'product_id' => $product->product_id,
                'product_name' => $product->product_name
            );
        }

        echo json_encode($data);
    }

    public function stock_insert() {
        $this->load->library('form_validation');

        $this->input->post('product_id');
        $this->input->post('qty');
        $this->input->post('unit');
        if (!$this->input->post('product_id')) {
            $data["data"] = 'Please select the product';
        } else {

            $this->load->model("common_model");
            $array = array(
                "product_id" => $this->input->post("product_id"),
                "qty" => $this->input->post("qty"),
                "price" => $this->input->post("price"),
                "unit" => $this->input->post("unit"),
                "store_id_login" => $this->input->post("store_id_login")
            );
            $this->common_model->data_insert("purchase", $array);

            $data['product'][] = array("msg" => 'Your Stock is Updated');
        }
        echo json_encode($data);
        $this->load->model("product_model");
        $data["purchases"] = $this->product_model->get_purchase_list();
        $data["products"] = $this->product_model->get_products();
    }

    public function assign() {
        $order = $this->input->post("order_id");
        $order = $this->input->post("d_boy");
        $this->load->model("common_model");
        $this->common_model->data_update("sale", $update_array, array("sale_id" => $order));
    }

    public function delivery_boy_order() {
        $delivery_boy_id = $this->input->post("d_id");
        $date = date("d-m-Y", strtotime('-3 day'));
        $this->load->model("product_model");
        $data = $this->product_model->delivery_boy_order($delivery_boy_id);

        $this->db->query("DELETE FROM signature WHERE `date` < '.$date.'");
        //$data['assign_orders'] = $q->result();
        echo json_encode($data);
    }

    public function assign_order() {
        $order_id = $this->input->post("order_id");
        $boy_name = $this->input->post("boy_name");

        $update_array = array("assign_to" => $boy_name);

        $this->load->model("common_model");
        //$q= $this->common_model->data_update("sale",$update_array,array("sale_id"=>$order_id));
        $hit = $this->db->query("UPDATE sale SET `assign_to`='" . $boy_name . "' where `sale_id`='" . $order_id . "'");
        if ($hit) {
            $data['assign'][] = array("msg" => "Assign Successfully");
        } else {
            $data['assign'][] = array("msg" => "Assign Not Successfully");
        }
        echo json_encode($data);
    }

    public function mark_delivered() {

        $this->load->library('form_validation');
        $signature = $this->input->post("signature");

        if (empty($_FILES['signature']['name'])) {
            $this->form_validation->set_rules('signature', 'signature', 'required');
        }

        if ($this->form_validation->run() == FALSE) {
            $data["error"] = $data["error"] = array("error" => "not found");
        } else {
            $add = array(
                "order_id" => $this->input->post("id")
            );

            if ($_FILES["signature"]["size"] > 0) {
                $config['upload_path'] = './uploads/signature/';
                $config['allowed_types'] = 'gif|jpg|png|jpeg';
                $this->load->library('upload', $config);

                if (!$this->upload->do_upload('signature')) {
                    $error = array('error' => $this->upload->display_errors());
                } else {
                    $img_data = $this->upload->data();
                    $add["signature"] = $img_data['file_name'];
                }
            }

            $q = $this->db->insert("signature", $add);
            if ($q) {
                $data = array("msg" => "Upload Successfull");
            } else {
                $data = array("msg" => "Upload Not Successfull");
            }
        }

        echo json_encode($data);
    }

    public function mark_delivered2() {


        if ((($_FILES["signature"]["type"] == "image/gif") || ($_FILES["signature"]["type"] == "image/jpeg") || ($_FILES["signature"]["type"] == "image/jpg") || ($_FILES["signature"]["type"] == "image/jpeg") || ($_FILES["signature"]["type"] == "image/png") || ($_FILES["signature"]["type"] == "image/png"))) {


            //Move the file to the uploads folder
            move_uploaded_file($_FILES["signature"]["tmp_name"], "./uploads/signature/" . $_FILES["signature"]["name"]);

            //Get the File Location
            $filelocation = './uploads/signature/' . $_FILES["signature"]["name"];

            //Get the File Size
            $order_id = $this->input->post("id");

            $q = $this->db->query("INSERT INTO signature (order_id, signature) VALUES ('$order_id', '$filelocation')");

            //$this->db->insert("signature",$add);
            if ($q) {

                $data = array("success" => "1", "msg" => "Upload Successfull");
                $this->db->query("UPDATE `sale` SET `status`=4 WHERE `sale_id`='" . $order_id . "'");
                $this->db->query("INSERT INTO delivered_order (sale_id, user_id, on_date, delivery_time_from, delivery_time_to, status, note, is_paid, total_amount, total_rewards, total_kg, total_items, socity_id, delivery_address, location_id, delivery_charge, new_store_id, assign_to, payment_method)
                                SELECT sale_id, user_id, on_date, delivery_time_from, delivery_time_to, status, note, is_paid, total_amount, total_rewards, total_kg, total_items, socity_id, delivery_address, location_id, delivery_charge, new_store_id, assign_to, payment_method FROM sale where sale_id = '" . $order_id . "'");


                $q2 = $this->db->query("Select total_rewards, user_id from sale where sale_id = '" . $order_id . "'");
                $user2 = $q2->row();

                $q = $this->db->query("Select * from registers where user_id = '" . $user2->user_id . "'");
                $user = $q->row();

                $rewrd_by_profile = $user->rewards;
                $rewrd_by_order = $user2->total_rewards;

                $new_rewards = $rewrd_by_profile + $rewrd_by_order;
                $this->db->query("update registers set rewards = '" . $new_rewards . "' where user_id = '" . $user2->user_id . "'");
            } else {
                $data = array("success" => "0", "msg" => "Upload Not Successfull");
            }
        } else {
            $data = array("success" => "0", "msg" => "Image Type Not Right");
        }
        echo json_encode($data);
    }

    public function ads() {
        $qry = $this->db->query("SELECT * FROM `ads`");
        $data = $qry->result();
        echo json_encode($data);
    }

    public function paypal() {
        $qry = $this->db->query("SELECT * FROM `paypal`");
        $data['paypal'] = $qry->result();
        echo json_encode($data);
    }

    public function razorpay() {
        $qry = $this->db->query("SELECT * FROM `razorpay`");
        $data = $qry->result();
        echo json_encode($data);
    }

    public function get_categories12() {
        $parent = 0;
        if ($this->input->post("parent")) {
            $parent = $this->input->post("parent");
        }
        $categories = $this->get_categories_short2($parent, 0, $this);
        $data["response"] = true;
        $data["data"] = $categories;
        echo json_encode($data);
    }

    public function get_categories_short2($parent, $level, $th) {
        $q = $th->db->query("Select a.*, ifnull(Deriv1.Count , 0) as Count, ifnull(Total1.PCount, 0) as PCount FROM `categories` a  LEFT OUTER JOIN (SELECT `parent`, COUNT(*) AS Count FROM `categories` GROUP BY `parent`) Deriv1 ON a.`id` = Deriv1.`parent` 
                                 LEFT OUTER JOIN (SELECT `category_id`,COUNT(*) AS PCount FROM `products` GROUP BY `category_id`) Total1 ON a.`id` = Total1.`category_id` 
                                 WHERE a.`parent`=" . $parent . " LIMIT 12");

        $return_array = array();

        foreach ($q->result() as $row) {
            if ($row->Count > 0) {
                $sub_cat = $this->get_categories_short2($row->id, $level + 1, $th);
                $row->sub_cat = $sub_cat;
            } elseif ($row->Count == 0) {
                
            }
            $return_array[] = $row;
        }
        return $return_array;
    }

    public function cart() {

        $user_id = $this->input->post("user_id");
        $pro_id = $this->input->post("product_id");
        $qty = $this->input->post("qty");

        if ($user_id) {
            $cart_create = $this->db->query("INSERT INTO cart (qty, user_id, product_id) VALUES ('$qty', '$user_id', '$pro_id')");
 $cart_id = $this->db->insert_id();

            if ($cart_create) {
                $data['response'] = true;
                $data['cart_id']=$cart_id;
                $data['msg'] = "Add Cart Successfull";
            } else {
                $data['response'] = false;
                $data['msg'] = "Add Cart Not Successfull";
            }
        } else {
            $data['response'] = false;
            $data['msg'] = "Add Cart Not Successfull";
        }

        echo json_encode($data);
    }

    public function view_cart() {


        $user_id = $this->input->post("user_id");

        if ($user_id) {


            $cart_productr = $this->db->query("select * from cart where user_id = '" . $this->input->post("user_id") . "'");
            $user = $cart_productr->result();
            $cart_quantity = $cart_productr->num_rows();
            if ($cart_quantity > 0) {
                $i = 1;
                foreach ($user as $user) {

                    $id = $user->product_id;
                    $qty = $user->qty;
                    $cart_id = $user->cart_id;
                    $q = $this->db->query("Select dp.*,products.*, ( ifnull (producation.p_qty,0) - ifnull(consuption.c_qty,0)) as stock ,categories.title from products 
                          inner join categories on categories.id = products.category_id
                          left outer join(select SUM(qty) as c_qty,product_id from sale_items group by product_id) as consuption on consuption.product_id = products.product_id 
                          left outer join(select SUM(qty) as p_qty,product_id from purchase group by product_id) as producation on producation.product_id = products.product_id
                          left join deal_product dp on dp.product_id=products.product_id where products.product_id =  '" . $id . "'");
                    $products = $q->result();


                    foreach ($products as $product) {
                        $present = date('m/d/Y h:i:s a', time());
                        $date1 = $product->start_date . " " . $product->start_time;
                        $date2 = $product->end_date . " " . $product->end_time;

                        if (strtotime($date1) <= strtotime($present) && strtotime($present) <= strtotime($date2)) {

                            if (empty($product->deal_price)) {   ///Runing
                                $price = $product->price;
                            } else {
                                $price = $product->deal_price;
                            }
                        } else {
                            $price = $product->price; //expired
                        }
                        $data['response'] = true;
                        $data['total_item'] = $i;
                        $sum['total'] = $price * $qty;
                        //array_push($data['total_amount'], $sum);
                        //$data['total_amount']=$sum;
                        $data['data'][] = array(
                            'product_id' => $product->product_id,
                            'product_name' => $product->product_name,
                            'category_id' => $product->category_id,
                            'product_description' => $product->product_description,
                            'price' => $price,
                            //'mrp' => $product->mrp,
                            'product_image' => base_url('uploads/products' . '/' . $product->product_image),
                            //'tax'=>$product->tax,
                            //'status' => $status,
                            'in_stock' => $product->in_stock,
                            'title' => $product->title,
                            'qty' => $qty,
                            'cart_id' => $cart_id,
                            'total_product_amount' => $qty * $price
                        );
                    } $i++;
                }
            } else if ($cart_quantity < 1) {
                $data['total_item'] = 0;
                $data['response'] = false;
                $data['msg'] = "Your Cart is Empty ";
            }
        } else {
            $data['response'] = false;
            $data['msg'] = "Cart Not Available ";
        }

        echo json_encode($data);
    }

    public function delete_from_cart() {
        $user_id = $this->input->post("user_id");
        $cart_id = $this->input->post("cart_id");

        $done = $this->db->query("delete from cart where cart_id = '" . $cart_id . "'");
        if ($done) {
            $data['response'] = true;
            $data['msg'] = "Product Delete From Cart Successfully";
        }

        echo json_encode($data);
    }

    public function payment_success() {
        $order_id = $this->input->post("order_id");
        $amount = $this->input->post("amount");

        $this->db->query("UPDATE `sale` SET `is_paid`='" . $amount . "' WHERE `sale_id`='" . $order_id . "'");
    }

    public function update_cart() {

        $cart_id = $this->input->post("cart_id");
        $qty = $this->input->post("qty");

        $this->load->library('form_validation');
        $this->form_validation->set_rules('cart_id', 'Cart ID', 'trim|required');
        $this->form_validation->set_rules('qty', 'Quantity', 'trim|required');
        if ($this->form_validation->run() == FALSE) {
            $data["response"] = false;
            $data["error"] = 'Warning! : ' . strip_tags($this->form_validation->error_string());
        } else {
            $cart_update = $this->db->query("UPDATE `cart` SET `qty`='" . $qty . "' WHERE `cart_id`='" . $cart_id . "'");

            if ($cart_update) {
                $data['response'] = true;
                $data['msg'] = "Add update Successfull";
            } else {
                $data['response'] = false;
                $data['msg'] = "Add Cart Not Successfull";
            }
        }



        echo json_encode($data);
    }

    public function get_categories22() {
        $parent = 0;
        if ($this->input->post("parent")) {
            $parent = $this->input->post("parent");
        }
        $categories = $this->get_categories_short22($parent, 0, $this);
        $data["response"] = true;
        $data["data"] = $categories;
        echo json_encode($data);
    }

    public function get_categories_short22($parent, $level, $th) {
        $q = $th->db->query("Select a.*, ifnull(Deriv1.Count , 0) as Count, ifnull(Total1.PCount, 0) as PCount FROM `categories` a  LEFT OUTER JOIN (SELECT `parent`, COUNT(*) AS Count FROM `categories` GROUP BY `parent`) Deriv1 ON a.`id` = Deriv1.`parent` 
                                 LEFT OUTER JOIN (SELECT `category_id`,COUNT(*) AS PCount FROM `products` GROUP BY `category_id`) Total1 ON a.`id` = Total1.`category_id` 
                                 WHERE a.`parent`=" . $parent . " LIMIT 9");

        $return_array = array();

        foreach ($q->result() as $row) {
            if ($row->Count > 0) {
                $sub_cat = $this->get_categories_short($row->id, $level + 1, $th);
                $row->sub_cat = $sub_cat;
            } elseif ($row->Count == 0) {
                
            }
            $return_array[] = $row;
        }
        return $return_array;
    }

    public function get_categoriesz() {
        $parent = 0;
        if ($this->input->post("parent")) {
            $parent = $this->input->post("parent");
        }
        $categories = $this->get_categories_shortz($parent, 0, $this);
        $data["response"] = true;
        $data["data"] = $categories;
        echo json_encode($data);
    }

    public function get_categories_shortz($parent, $level, $th) {
        $q = $th->db->query("Select a.*, ifnull(Deriv1.Count , 0) as Count, ifnull(Total1.PCount, 0) as PCount FROM `categories` a  LEFT OUTER JOIN (SELECT `parent`, COUNT(*) AS Count FROM `categories` GROUP BY `parent`) Deriv1 ON a.`id` = Deriv1.`parent` 
                                 LEFT OUTER JOIN (SELECT `category_id`,COUNT(*) AS PCount FROM `products` GROUP BY `category_id`) Total1 ON a.`id` = Total1.`category_id` 
                                 WHERE a.`parent`=" . $parent . "");

        $return_array = array();

        foreach ($q->result() as $row) {
            if ($row->Count > 0) {
                $sub_cat = $this->get_categories_shortz($row->id, $level + 1, $th);
                $row->sub_cat = $sub_cat;
            } elseif ($row->Count == 0) {
                
            }
            $return_array[] = $row;
        }
        return $return_array;
    }

    public function ios_send_order() {
        $total_rewards = "";
        $total_price = "";
        $total_kg = "";
        $this->load->library('form_validation');
        $this->form_validation->set_rules('user_id', 'User ID', 'trim|required');
        $this->form_validation->set_rules('date', 'Date', 'trim|required');
        $this->form_validation->set_rules('time', 'Time', 'trim|required');
        $this->form_validation->set_rules('location', 'Location', 'trim|required');
        if ($this->form_validation->run() == FALSE) {
            $data["response"] = false;
            $data["error"] = 'Warning! : ' . strip_tags($this->form_validation->error_string());
        } else {
            $ld = $this->db->query("select user_location.*, socity.* from user_location
                    inner join socity on socity.socity_id = user_location.socity_id
                     where user_location.location_id = '" . $this->input->post("location") . "' limit 1");
            $location = $ld->row();

            $store_id = $this->input->post("store_id");
            $payment_method = $this->input->post("payment_method");
            $date = date("Y-m-d", strtotime($this->input->post("date")));
            //$timeslot = explode("-",$this->input->post("timeslot"));

            $times = explode('-', $this->input->post("time"));
            $fromtime = date("h:i a", strtotime(trim($times[0])));
            $totime = date("h:i a", strtotime(trim($times[1])));


            $user_id = $this->input->post("user_id");
            $insert_array = array("user_id" => $user_id,
                "on_date" => $date,
                "delivery_time_from" => $fromtime,
                "delivery_time_to" => $totime,
                "delivery_address" => $location->house_no . "\n, " . $location->house_no,
                "socity_id" => $location->socity_id,
                "delivery_charge" => $location->delivery_charge,
                "location_id" => $location->location_id,
                "payment_method" => $payment_method,
                "new_store_id" => $store_id
            );
            $this->load->model("common_model");
            $id = $this->common_model->data_insert("sale", $insert_array);

            $cart = $this->db->query("select * from cart WHERE user_id='" . $user_id . "'");
            $cart_value = $cart->result();
            foreach ($cart_value as $cart_value) {

                $q = $this->db->query("Select dp.*,products.*, ( ifnull (producation.p_qty,0) - ifnull(consuption.c_qty,0)) as stock ,categories.title from products 
                          inner join categories on categories.id = products.category_id
                          left outer join(select SUM(qty) as c_qty,product_id from sale_items group by product_id) as consuption on consuption.product_id = products.product_id 
                          left outer join(select SUM(qty) as p_qty,product_id from purchase group by product_id) as producation on producation.product_id = products.product_id
                          left join deal_product dp on dp.product_id=products.product_id where products.product_id =  '" . $cart_value->product_id . "'");
                $products = $q->result();
                foreach ($products as $product) {
                    $present = date('m/d/Y h:i:s a', time());
                    $date1 = $product->start_date . " " . $product->start_time;
                    $date2 = $product->end_date . " " . $product->end_time;

                    if (strtotime($date1) <= strtotime($present) && strtotime($present) <= strtotime($date2)) {

                        if (empty($product->deal_price)) {   ///Runing
                            $price = $product->price;
                        } else {
                            $price = $product->deal_price;
                        }
                    } else {
                        $price = $product->price; //expired
                    }


                    $qty_in_kg = $cart_value->qty;
                    if ($product->unit == "gram") {
                        $qty_in_kg = ($cart_value->qty * $product->unit_value) / 1000;
                    }
                    $total_rewards = $total_rewards + ($cart_value->qty * $product->rewards);
                    $total_price = $total_price + ($cart_value->qty * $product->price);
                    $total_kg = $total_kg + $qty_in_kg;
                    $total_items[$product->product_id] = $product->product_id;


                    $array = array("product_id" => $product->product_id,
                        "qty" => $cart_value->qty,
                        "unit" => $product->unit,
                        "unit_value" => $product->unit_value,
                        "sale_id" => $id,
                        "price" => $product->price,
                        "qty_in_kg" => $qty_in_kg,
                        "rewards" => $product->rewards
                    );
                    $this->common_model->data_insert("sale_items", $array);
                }
            }



            // $data_post = $this->input->post("data");
            // $data_array = json_decode($data_post);
            // $total_rewards = 0;
            // $total_price = 0;
            // $total_kg = 0;
            // $total_items = array();
            // foreach($data_array as $dt){
            //     $qty_in_kg = $dt->qty; 
            //     if($dt->unit=="gram"){
            //         $qty_in_kg =  ($dt->qty * $dt->unit_value) / 1000;     
            //     }
            //     $total_rewards = $total_rewards + ($dt->qty * $dt->rewards);
            //     $total_price = $total_price + ($dt->qty * $dt->price);
            //     $total_kg = $total_kg + $qty_in_kg;
            //     $total_items[$dt->product_id] = $dt->product_id;    
            //     $array = array("product_id"=>$dt->product_id,
            //     "qty"=>$dt->qty,
            //     "unit"=>$dt->unit,
            //     "unit_value"=>$dt->unit_value,
            //     "sale_id"=>$id,
            //     "price"=>$dt->price,
            //     "qty_in_kg"=>$qty_in_kg,
            //     "rewards" =>$dt->rewards
            //     );
            //     $this->common_model->data_insert("sale_items",$array);
            // }







            $total_price = $total_price + $location->delivery_charge;
            $this->db->query("Update sale set total_amount = '" . $total_price . "', total_kg = '" . $total_kg . "', total_items = '" . count($total_items) . "', total_rewards = '" . $total_rewards . "' where sale_id = '" . $id . "'");

            $data["response"] = true;
            $data["data"] = addslashes("<p>Your order No #" . $id . " is send success fully \n Our delivery person will delivered order \n 
                    between " . $fromtime . " to " . $totime . " on " . $date . " \n
                    Please keep <strong>" . $total_price . "</strong> on delivery
                    Thanks for being with Us.</p>");
        }
        echo json_encode($data);
    }

    function add_coupons() {

        $this->load->helper('form');
        $this->load->model('product_model');
        $this->load->library('session');

        $this->load->library('form_validation');
        $this->form_validation->set_rules('coupon_title', 'Coupon name', 'trim|required|max_length[6]|alpha_numeric');
        $this->form_validation->set_rules('coupon_code', 'Coupon Code', 'trim|required|max_length[6]|alpha_numeric');
        $this->form_validation->set_rules('from', 'From', 'required'); //|callback_date_valid
        $this->form_validation->set_rules('to', 'To', 'required'); //|callback_date_valid

        $this->form_validation->set_rules('value', 'Value', 'required|numeric');
        $this->form_validation->set_rules('cart_value', 'Cart Value', 'required|numeric');
        $this->form_validation->set_rules('restriction', 'Uses restriction', 'required|numeric');

        $data = array();
        if ($this->form_validation->run() == FALSE) {
            $data["response"] = false;
            $data["error"] = 'Warning! : ' . strip_tags($this->form_validation->error_string());
        } else {
            $data = array(
                'coupon_name' => $this->input->post('coupon_title'),
                'coupon_code' => $this->input->post('coupon_code'),
                'valid_from' => $this->input->post('from'),
                'valid_to' => $this->input->post('to'),
                'validity_type' => "",
                'product_name' => "",
                'discount_type' => "",
                'discount_value' => $this->input->post('value'),
                'cart_value' => $this->input->post('cart_value'),
                'uses_restriction' => $this->input->post('restriction')
            );
            //print_r($data);
            if ($this->product_model->coupon($data)) {
                $data["response"] = true;
                $data["msg"] = 'Coupon Create Successfull';
            }
        }
        //$data['coupons'] = $this->product_model->coupon_list();
        echo json_encode($data);
    }

    public function assign_client_count() {
        $d = date('d/m/y');
        $q = $this->db->query("Select * from assign_client where sale_user_id = '" . $this->input->post('sales_id') . "'");
        $data['count'] = $q->num_rows();
        echo json_encode($data);
    }

    public function sales_report() {
        $d = date('d/m/y');
        $create_by = $this->input->post("sales_id");
        $sql = "Select * FROM sale 
     inner join socity on socity.socity_id = sale.socity_id 
     inner join registers on registers.user_id = sale.user_id
     WHERE sale.created_by='" . $create_by . "' AND sale.created_on ='" . $d . "' ORDER BY sale.sale_id DESC ";
        $q = $this->db->query($sql);
        $data["response"] = true;
        $data['run'] = $q->result();

        echo json_encode($data);
    }

    public function user_create_by() {
        $create_by = $this->input->post("sales_id");
        $q = $this->db->query("Select * from registers where created_by = '" . $create_by . "'");
        $data['create_by'] = $q->num_rows();
        echo json_encode($data);
    }

    public function sale_by_salesman() {
        $create_by = $this->input->post("sales_id");
        $q = $this->db->query("Select * from sale where created_by = '" . $create_by . "'");
        $data['create_by'] = $q->num_rows();
        echo json_encode($data);
    }

    public function created_by_salesman() {
        $create_by = $this->input->post("sales_id");
        $q = $this->db->query("Select * from registers where created_by = '" . $create_by . "'");
        $data['create_by'] = $q->result();
        echo json_encode($data);
    }

    public function today_user_create_by() {
        $create_by = $this->input->post("sales_id");
        $today = date('d/m/y');
        $q = $this->db->query("Select * from registers where created_by = '" . $create_by . "' AND created_on = '" . $today . "' ");
        $data['create_by'] = $q->num_rows();
        echo json_encode($data);
    }

    public function today_sale_by_salesman() {
        $create_by = $this->input->post("sales_id");
        $today = date('d/m/y');
        $q = $this->db->query("Select * from sale where created_by = '" . $create_by . "' AND created_on = '" . $today . "'");
        $data['create_by'] = $q->num_rows();
        echo json_encode($data);
    }

    public function today_created_by_salesman() {
        $create_by = $this->input->post("sales_id");
        $today = date('d/m/y');
        $q = $this->db->query("Select * from registers where created_by = '" . $create_by . "' AND created_on = '" . $today . "'");
        $data['create_by'] = $q->result();
        echo json_encode($data);
    }

    public function today_assign_client_count() {
        $date = date('d/m/y');
        $q = $this->db->query("Select * from assign_client where sale_user_id = '" . $this->input->post('sales_id') . "' AND on_date = '" . $date . "'");
        $data['count'] = $q->num_rows();
        echo json_encode($data);
    }

    public function user_profile_detail() {
        error_reporting(0);
        $q = $this->db->query("Select * from registers where user_id = '" . $this->input->post('detail_id') . "'");
        $data['detail'] = $q->result();
        //$q =$this->db->query("Select registers.*, user_location.* from registers left join user_location on user_location.user_id=registers.user_id where registers.user_id = '".$this->input->post('user_id')."'");
        $que = $this->db->query("Select user_location.* , socity.socity_name from user_location left join socity on socity.socity_id=user_location.socity_id where user_location.user_id = '" . $this->input->post('detail_id') . "'");
        foreach ($que->result() as $addresses) {
            $get[] = $addresses->receiver_name . " , " . $addresses->house_no . " " . $addresses->socity_name . " " . $addresses->pincode . "";
        }
        $data['address'] = $get;
        echo json_encode($data);
    }

    public function purchase_history() {
        //error_reporting(0);
        $q = $this->db->query("Select * from sale where user_id = '" . $this->input->post('user_id') . "'");
        $data['purchase_history'] = $q->result();
        echo json_encode($data);
    }

    public function order_by_salesman() {
        $create_by = $this->input->post("sales_id");
        $q = $this->db->query("Select * from sale where created_by = '" . $create_by . "'");
        $data['order'] = $q->result();
        echo json_encode($data);
    }

    public function user_detail() {

        $this->load->model("product_model");
        $qry = $this->db->query("SELECT * FROM `registers` where user_id = '" . $this->input->post('user_id') . "'");
        $data["user"] = $qry->result();
        //$data["order"] = $this->product_model->get_sale_orders(" and sale.user_id = '".$user_id."' AND sale.status=4 ");
        echo json_encode($data);
    }

    public function wallet_at_checkout() {

        $id = $this->input->post('user_id');
        $q = $this->db->query("SELECT * FROM `registers` where user_id = '" . $id . "'");
        $row = $q->row();
        $profile_amount = $row->wallet;
        $wallet_amount = $this->input->post('wallet_amount');
        $new_wallet_amount = $profile_amount - $wallet_amount;

        $this->db->query("UPDATE registers set wallet = '" . $new_wallet_amount . "' WHERE user_id = '" . $this->input->post('user_id') . "'");
    }

}

