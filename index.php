<?php
/**
* Plugin Name: Cleverwise Cloaked Files
* Description: Easily cloak (hide) unlimited file downloads on your site so that your visitors must know secret codes to view them.  This allows you to provide selective people, such as ecommerce purchasers, with the necessary codes to download the protected files.
* Version: 1.0
* Author: Jeremy O'Connell
* Author URI: http://www.cyberws.com/cleverwise-plugins/
* License: GPL2 .:. http://opensource.org/licenses/GPL-2.0
*/

////////////////////////////////////////////////////////////////////////////
//	Load Cleverwise Framework Library
////////////////////////////////////////////////////////////////////////////
include_once('cwfa.php');
$cwfa_cf=new cwfa_cf;

////////////////////////////////////////////////////////////////////////////
//	Wordpress database option
////////////////////////////////////////////////////////////////////////////
Global $wpdb,$cf_wp_option_version_txt,$cf_wp_option,$cf_wp_option_version_num;

$cf_wp_option_version_num='1.0';
$cf_wp_option='cloaked_files';
$cf_wp_option_version_txt=$cf_wp_option.'_version';

////////////////////////////////////////////////////////////////////////////
//	Get db prefix and set correct table names
////////////////////////////////////////////////////////////////////////////
Global $cw_cloaked_files_tbl;

$wp_db_prefix=$wpdb->prefix;
$cw_cloaked_files_tbl=$wp_db_prefix.'cloaked_files';
$cw_cloaked_files_logs_tbl=$wp_db_prefix.'cloaked_files_logs';

////////////////////////////////////////////////////////////////////////////
//	If admin panel is showing and user can manage options load menu option
////////////////////////////////////////////////////////////////////////////
if (is_admin()) {
	//	Hook admin code
	include_once("cfa.php");

	//	Activation code
	register_activation_hook( __FILE__, 'cw_cloaked_files_activate');

	//	Check installed version and if mismatch upgrade
	Global $wpdb;
	$cf_wp_option_db_version=get_option($cf_wp_option_version_txt);
	if ($cf_wp_option_db_version < $cf_wp_option_version_num) {
		update_option($cf_wp_option_version_txt,$cf_wp_option_version_num);
	}
}

//	Handle file download when fid and fgo are set
if (isset($_REQUEST['fid'])) {
	$fid=$cwfa_cf->cwf_san_an($_REQUEST['fid']);
	if (isset($_REQUEST['fgo'])) {
		$file_url=base64_decode(urldecode($_REQUEST['fgo']));
		list($file_url,$file_name)=explode('|',$file_url,2);
		
		$cf_wp_option_array=get_option($cf_wp_option);
		$cf_wp_option_array=unserialize($cf_wp_option_array);
		$cloaked_files_base_url=$cf_wp_option_array['cloaked_files_base_url'];
		$cloaked_files_base_url .=$file_url;
		
		$dl_log_ts=time();
		$cloaked_files_download_ip=$cf_wp_option_array['cloaked_files_download_ip'];
		if ($cloaked_files_download_ip == 'n') {
			$dl_log_ip='';
		} else {
			$dl_log_ip=cf_get_client_ip_server();
		}		
		$data['dl_log_ts']=$dl_log_ts;
		$data['dl_log_ip']=$dl_log_ip;
		$data['dl_log_data']=$fid.'|'.$file_name;
		$wpdb->insert($cw_cloaked_files_logs_tbl,$data);
		
		header("Location: $cloaked_files_base_url");
		die();
	}
}

////////////////////////////////////////////////////////////////////////////
//	Register shortcut to display visitor side
////////////////////////////////////////////////////////////////////////////
add_shortcode('cw_cloaked_files', 'cw_cloaked_files_vside');

////////////////////////////////////////////////////////////////////////////
//	Visitor Display
////////////////////////////////////////////////////////////////////////////
function cw_cloaked_files_vside() {
Global $wpdb,$cf_wp_option,$cw_cloaked_files_tbl,$cwfa_cf;
	$fid=$cwfa_cf->cwf_san_an($_REQUEST['fid']);

	$cf_wp_option_array=get_option($cf_wp_option);
	$cf_wp_option_array=unserialize($cf_wp_option_array);
	$cloaked_files_build=$cf_wp_option_array['cloaked_files_error_msg'];
	$cloaked_files_download_txt=$cf_wp_option_array['cloaked_files_download_txt'];
	
	$cloaked_files_build=stripslashes($cloaked_files_build);
	
	if (isset($fid)) {
		$myrows=$wpdb->get_results("SELECT file_url,file_name FROM $cw_cloaked_files_tbl where file_id='$fid'");
		if ($myrows) {
			foreach ($myrows as $myrow) {
				$file_url=$myrow->file_url;
				$file_name=stripslashes($myrow->file_name);
				$file_url .='|'.$file_name;
				$file_url=urlencode(base64_encode($file_url));
				$cloaked_files_build='<a href="?fid='.$fid.'&fgo='.$file_url.'">'.$cloaked_files_download_txt.' '.$file_name.'</a>';
			}
		}
	}
	
	//	Display to browser/site
	//$cloaked_files_build='<p>'.$cloaked_files_build.'</p>';
	return $cloaked_files_build;
}

////////////////////////////////////////////////////////////////////////////
//	Get Client IP
////////////////////////////////////////////////////////////////////////////
function cf_get_client_ip_server() {
    $ipaddress = '';
    if ($_SERVER['HTTP_CLIENT_IP'])
        $ipaddress = $_SERVER['HTTP_CLIENT_IP'];
    else if($_SERVER['HTTP_X_FORWARDED_FOR'])
        $ipaddress = $_SERVER['HTTP_X_FORWARDED_FOR'];
    else if($_SERVER['HTTP_X_FORWARDED'])
        $ipaddress = $_SERVER['HTTP_X_FORWARDED'];
    else if($_SERVER['HTTP_FORWARDED_FOR'])
        $ipaddress = $_SERVER['HTTP_FORWARDED_FOR'];
    else if($_SERVER['HTTP_FORWARDED'])
        $ipaddress = $_SERVER['HTTP_FORWARDED'];
    else if($_SERVER['REMOTE_ADDR'])
        $ipaddress = $_SERVER['REMOTE_ADDR'];
    else
        $ipaddress = 'UNKNOWN';
 
    return $ipaddress;
}