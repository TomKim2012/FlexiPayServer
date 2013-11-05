<?php
class Members_model extends CI_Model {
	var $gallery_path;
	var $gallery_path_url;

	function __construct()
	{
		parent::__construct();
		$this->gallery_path = realpath(APPPATH . '../images/members/');
		$this->gallery_path_url = base_url().'images/members/';
	}

	function index()
	{
	$data['main_content']='users/register';
	$this->load->view('common/template',$data);	
	}
	function validate(){
        // grab user input
        $username = $this->security->xss_clean($this->input->post('username'));
        $password = $this->security->xss_clean(md5($this->input->post('password')));
 
        // Prep the query
        $this->db->where('username', $username);
        $this->db->where('password', $password);
 
        // Run the query
        $query = $this->db->get('users');
        // Let's check if there are any results
        if($query->num_rows == 1)
        {
            // If there is a user, then create session data
            $row = $query->row();
            $data = array(
                    'user_id' => $row->user_id,
                    'first_name' => $row->first_name,
                    'last_name' => $row->last_name,
                    'username' => $row->username,
                    'validated' => true
                    );
            $this->session->set_userdata($data);
            return true;
        }
        // If the previous process did not validate
        // then return false.
        return false;
    }

	function create_member($new_member){
		$insert =$this->db->insert('users',$new_member);
		return $insert;
	}
	function check_username($username)
	{
	    $this->db->select('username');
	    $this->db->where('username',$username);
	    $query = $this->db->get('users');
	    if ($query->num_rows() > 0){
	        return true;
	    }
	    else{
	        return false;
	    }
	}

	function check_email($email)
	{
		$this->db->select('email');
	    $this->db->where('email',$email);
	    $query = $this->db->get('users');
	    if ($query->num_rows() > 0){
	        return true;
	    }
	    else{
	        return false;
	    }
	}

}