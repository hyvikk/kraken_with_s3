<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Signup extends CI_Controller {
	
	public function __construct(){
		parent::__construct();	
		$this->load->library('kraken_lib');
    }

	public function index(){
		// Kraken Optimizer
		$dpfile="<NAME_OF_YOUR_IMAGE>";
		$file_name=$dpfile;
		$file_path=realpath("./uploads/users/".$file_name); 	// Realpath of your Image
		$bucket_folder="users";		// Name of Folder in S3 Bucket, If not specified file will be uploaded in Bucket's root
		$result=$this->kraken_lib->optimize_and_upload($file_path, $file_name, $bucket_folder, 1);
		// Kraken Optimizer
	}
}