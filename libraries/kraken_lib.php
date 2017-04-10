<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/**
 * CodeIgniter
 *
 * An open source application development framework for PHP 5.1.6 or newer
 *
 * @package     CodeIgniter
 * @author      Hiren Vala
 * @copyright   Copyright (c) 2017, Hyvikk Solutions
 * @license    
 * @link        https://www.hyvikk.com
 * @since       Version 1.0
 * @filesource
 */

class Kraken_lib {
    protected $CI;
    public function __construct($params = array()){
        $this->CI =& get_instance();
        $this->CI->load->helper('url');      
    }

    public function optimize_and_upload($file_path, $file_name, $bucket_folder, $resize=0){
		$data = array();
		
		if($resize){
			$file_nm=pathinfo($file_name);
			$file_name1=$file_nm['filename']."-250x250".$file_nm['extension']; // Hard resize to 250x250
			$file_name2=$file_nm['filename']."-100x100".$file_nm['extension']; // Hard resize to 100x100
			$data[]=$this->perform($file_path, $file_name1, $bucket_folder, 250, 250);
			$data[]=$this->perform($file_path, $file_name2, $bucket_folder, 100, 100);
		}else{
			$data[]=$this->perform($file_path, $file_name, $bucket_folder); // No Resize, Upload Original file after Optimizing
		}
		return $data;
    }

    public function perform($file_path, $file_name, $bucket_folder, $width=0, $height=0){
    	require_once APPPATH."third_party/lib/Kraken.php";
    	$kraken_conf=$this->CI->config->item('kraken'); // Assuming you are storing your key,secret of kraken in config
    	$s3_conf=$this->CI->config->item('s3');         // Assuming you are storing your key,secret of s3 in config
		$kraken = new Kraken($kraken_conf['key'], $kraken_conf['secret']);
    	$datetime = new DateTime('01 Mar 2018');
		$expires = $datetime->format('c');
    	$params = array(
			"file" => $file_path,
			"wait" => true,
			"lossy" => true,			
			"s3_store" => array(
				"key" => $s3_conf['key'],
				"secret" => $s3_conf['secret'],
				"bucket" => "<YOUR_S3_BUCKET_NAME>", 
				"path" => $bucket_folder."/".$file_name,
				"headers" => array(
					"Cache-Control" => "max-age=31536000",
					"Expires" => strtotime($expires),
				),
				"region" => $s3_conf['region'],
				"webp" => true,
			),
		);
    	if($width!=0 && $height!=0){
    		$params["resize"]=array(
				"width" => $width,
				"height" => $height,
				"strategy" => "exact",
			);
    	}
		$data = $kraken->upload($params);
		return $data;
    }
}