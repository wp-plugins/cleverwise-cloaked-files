<?php
/*
* Copyright 2014 Jeremy O'Connell  (email : cwplugins@cyberws.com)
* License: GPL2 .:. http://opensource.org/licenses/GPL-2.0
*/

////////////////////////////////////////////////////////////////////////////
//	Verify admin panel is loaded, if not fail
////////////////////////////////////////////////////////////////////////////
if (!is_admin()) {
	die();
}

////////////////////////////////////////////////////////////////////////////
//	Menu call
////////////////////////////////////////////////////////////////////////////
add_action('admin_menu', 'cw_cloaked_files_aside_mn');

////////////////////////////////////////////////////////////////////////////
//	Load admin menu option
////////////////////////////////////////////////////////////////////////////
function cw_cloaked_files_aside_mn() {

	//	If user is logged in and has admin permissions show menu
	if (is_user_logged_in()) {
		add_menu_page('Cloaked Files','Cloaked Files','manage_options','cw-cloaked-files','cw_cloaked_files_aside','','33');
	}
}

////////////////////////////////////////////////////////////////////////////
//	Load admin functions
////////////////////////////////////////////////////////////////////////////
function cw_cloaked_files_aside() {
Global $wpdb,$cf_wp_option,$cw_cloaked_files_tbl,$cw_cloaked_files_logs_tbl,$cwfa_cf;

	////////////////////////////////////////////////////////////////////////////
	//	Load options for plugin
	////////////////////////////////////////////////////////////////////////////
	$cf_wp_option_array=get_option($cf_wp_option);
	$cf_wp_option_array=unserialize($cf_wp_option_array);
	$cloaked_files_base_url=$cf_wp_option_array['cloaked_files_base_url'];
	$cloaked_files_records_ppg=$cf_wp_option_array['cloaked_files_records_ppg'];

	////////////////////////////////////////////////////////////////////////////
	//	Set action value
	////////////////////////////////////////////////////////////////////////////
	if (isset($_REQUEST['cw_action'])) {
		$cw_action=$_REQUEST['cw_action'];
	} else {
		$cw_action='main';
	}

	////////////////////////////////////////////////////////////////////////////
	//	Previous page link
	////////////////////////////////////////////////////////////////////////////
	$pplink='<a href="javascript:history.go(-1);">Return to previous page...</a>';
	
	////////////////////////////////////////////////////////////////////////////
	//	Define Variables
	////////////////////////////////////////////////////////////////////////////
	$cw_cloaked_files_action='';
	$cw_cloaked_files_html='';
	$cloaked_files_stypes['files']='Cloaked Files';
	$cloaked_files_stypes['logs']='Download Logs';
	
	////////////////////////////////////////////////////////////////////////////
	//	Add/Edit Cloaked Files
	////////////////////////////////////////////////////////////////////////////
	if ($cw_action == 'cloakedadd' or $cw_action == 'cloakededit') {

		$file_url='';
		$file_name='';
		
		if ($cw_action == 'cloakededit') {
			$cw_cloaked_files_action_btn='Edit';
			$cw_cloaked_files_action='Editting';
			
			$fid=$cwfa_cf->cwf_san_an($_REQUEST['fid']);
			
			$myrows=$wpdb->get_results("SELECT file_url,file_name FROM $cw_cloaked_files_tbl where file_id='$fid'");
			if ($myrows) {
				foreach ($myrows as $myrow) {
					$file_url=$myrow->file_url;
					$file_name=stripslashes($myrow->file_name);
				}
			}
		}
		
		if (!$file_url) {
			$cw_action='cloakedadd';
			$cw_cloaked_files_action_btn='Add';
			$cw_cloaked_files_action='Adding';
			$fid='0';
		}
		$cw_action .='sv';
		
		if ($cw_action == 'cloakededitsv') {
			$cw_cloaked_files_html='<p>To display file add the bold text to the end of the download page URL (starting with ? mark):</p><p><b>?fid='.$fid.'</b></p>';
		}
		
$cw_cloaked_files_html .=<<<EOM
<form method="post">
<input type="hidden" name="cw_action" value="$cw_action">
<input type="hidden" name="fid" value="$fid">
<p>Note: The file name can include directories.</p>
<p>File Name: <input type="text" name="file_url" value="$file_url" style="width: 360px;"></p>
<p>Link Name: <input type="text" name="file_name" value="$file_name" style="width: 340px;"></p>
<p><input type="submit" value="$cw_cloaked_files_action_btn" class="button"></p>
</form>
EOM;

		if ($cw_action == 'cloakededitsv') {
$cw_cloaked_files_html .=<<<EOM
<div id="del_link" name="del_link" style="border-top: 1px solid #d6d6cf; margin-top: 20px; padding: 5px; width: 390px;"><a href="javascript:void(0);" onclick="document.getElementById('del_controls').style.display='';document.getElementById('del_link').style.display='none';">Show deletion controls</a></div>
<div name="del_controls" id="del_controls" style="display: none; width: 390px; margin-top: 20px; border: 1px solid #d6d6cf; padding: 5px;">
<a href="javascript:void(0);" onclick="document.getElementById('del_controls').style.display='none';document.getElementById('del_link').style.display='';">Hide deletion controls</a>
<form method="post">
<input type="hidden" name="cw_action" value="cloakeddel"><input type="hidden" name="fid" value="$fid">
<p><input type="checkbox" name="cf_confirm_1" value="1"> Check to delete $file_name</p>
<p><input type="checkbox" name="cf_confirm_2" value="1"> Check to confirm deletion of $file_name</p>
<p><span style="color: #ff0000; font-weight: bold;">Deletion is final! There is no undoing this action!</span></p>
<p style="text-align: right;"><input type="submit" value="Delete" class="button"></p>
</div>
EOM;
		}

	////////////////////////////////////////////////////////////////////////////
	//	Add/Edit Cloaked Files Save
	////////////////////////////////////////////////////////////////////////////
	} elseif ($cw_action == 'cloakedaddsv' or $cw_action == 'cloakededitsv') {
		$fid=$cwfa_cf->cwf_san_an($_REQUEST['fid']);
		$file_url=$cwfa_cf->cwf_san_url($_REQUEST['file_url']);
		$file_name=$cwfa_cf->cwf_san_all($_REQUEST['file_name']);
		
		$error='';

		if (!$file_url) {
			$error .='<li>No File Name</li>';
		}
		if (!$file_name) {
			$error .='<li>No Link Name</li>';
		}
		
		if ($error) {
			$cw_cloaked_files_action='Error';
			$cw_cloaked_files_html='Please fix the following in order to save cloaked file:<br><ul style="list-style: disc; margin-left: 25px;">'. $error .'</ul>'.$pplink;
		} else {
			$cw_cloaked_files_action='Success';
			
			$data['file_url']=$file_url;
			$data['file_name']=$file_name;
			
			if ($cw_action == 'cloakededitsv') {
				$where=array();
				$where['file_id']=$fid;
				$wpdb->update($cw_cloaked_files_tbl,$data,$where);
			} else {
				$fid_status='f';
				while ($fid_status == 'f') {
					$fid='48';
					$fid=$cwfa_cf->cwf_gen_randstr($fid);	//cf_generate_fid();
					$data['file_id']=$fid;
					$myrows=$wpdb->get_results("SELECT file_name FROM $cw_cloaked_files_tbl where file_id='$fid'");
					if (!$myrows) {
						$fid_status='p';
					}
				}
				
				$wpdb->insert($cw_cloaked_files_tbl,$data);
				$fid=$wpdb->insert_id;
			}
			
			$file_name=stripslashes($file_name);
			$cw_cloaked_files_html='<p>'.$file_name.' has been successfully saved!</p><p><a href="?page=cw-cloaked-files&cw_action=mainpanel">Continue</a></p>';
		}
		
	////////////////////////////////////////////////////////////////////////////
	//	Delete Cloaked File
	////////////////////////////////////////////////////////////////////////////
	} elseif ($cw_action == 'cloakeddel') {
		$fid=$cwfa_cf->cwf_san_an($_REQUEST['fid']);

		if (isset($_REQUEST['cf_confirm_1'])) {
			$cf_confirm_1=$cwfa_cf->cwf_san_int($_REQUEST['cf_confirm_1']);
		} else {
			$cf_confirm_1='0';
		}
		if (isset($_REQUEST['cf_confirm_2'])) {
			$cf_confirm_2=$cwfa_cf->cwf_san_int($_REQUEST['cf_confirm_2']);
		} else {
			$cf_confirm_2='0';
		}

		$cw_cloaked_files_action='Delete Cloaked File';

		if ($cf_confirm_1 == '1' and $cf_confirm_2 == '1') {
			$where=array();
			$where['file_id']=$fid;
			$wpdb->delete($cw_cloaked_files_tbl,$where);

			$cw_cloaked_files_html='Cloaked file has been removed! <a href="?page=cw-cloaked-files">Continue...</a>';
		} else {
			$cw_cloaked_files_html='<span style="color: #ff0000;">Error! You must check both confirmation boxes!</span><br><br>'.$pplink;
		}
		
	////////////////////////////////////////////////////////////////////////////
	//	Search
	////////////////////////////////////////////////////////////////////////////
	} elseif ($cw_action == 'cloakedsearch') {
		$search_results='';
		$pgprevnxt='';
		$pgnavlist='';
		$statusword='';
		$settings_ppg=$cloaked_files_records_ppg;
		$stype=$_REQUEST['stype'];

		//	Load search box
		$sbox=trim($_REQUEST['sbox']);
		$sbox=stripslashes($sbox);
		if (!$sbox) {
			$sbox='%';
		}
		$sboxlink=urlencode($sbox);
		$sbox=addslashes($sbox);

		if ($stype == 'logs') {
			$search_cnt_row='dl_log_id';
			$table=$cw_cloaked_files_logs_tbl;
			$cf_wheresql='(dl_log_ip like "%'.$sbox.'%" or dl_log_data like "%'.$sbox.'%")';
		} else {
			$stype='files';
			$search_cnt_row='file_id';
			$table=$cw_cloaked_files_tbl;
			$cf_wheresql='(file_url like "%'.$sbox.'%" or file_name like "%'.$sbox.'%")';
		}
		$cf_wherelink="sbox=$sboxlink&stype=$stype";
		$cf_form='<input type="hidden" name="sbox" value="'.$sbox.'"><input type="hidden" name="stype" value="'.$stype.'">';

		//	Matching record count
		$search_cnt='0';
		$myrows=$wpdb->get_results("SELECT count($search_cnt_row) as search_cnt FROM $table where $cf_wheresql");
		if ($myrows) {
			foreach ($myrows as $myrow) {
				$search_cnt=$myrow->search_cnt;
			}
		}

		//	Max page count
		$tpgs=$search_cnt/$settings_ppg;
		if (substr_count($tpgs,'.') > '0') {
			list($tpgs,$tpgsdiscard)=explode('.',$tpgs);
			$tpgs++;
		}

		//	Load page count
		if (isset($_REQUEST['spg'])) {
			$spg=$cwfa_cf->cwf_san_int($_REQUEST['spg']);
			if (!$spg) {
				$spg='1';
			}
		} else {
			$spg='1';
		}

		//	Page count can't exceed max pages
		if ($spg > $tpgs) {
			$spg=$tpgs;
		}
		$cw_page_txt='Page: '.$spg.' of '.$tpgs;

		//	Get records
		$snum=($spg-1)*$settings_ppg;
		if ($snum < '0') {
			$snum='0';
		}
		$enum=$snum;
		if ($stype == 'logs') {
			$order_by='dl_log_ts DESC';
			$select_columns='dl_log_ts,dl_log_ip,dl_log_data';
			$table=$cw_cloaked_files_logs_tbl;
		} else {
			$order_by='file_name';
			$select_columns='file_id,file_name';
			$table=$cw_cloaked_files_tbl;
		}
		$myrows=$wpdb->get_results("SELECT $select_columns FROM $table where $cf_wheresql order by $order_by limit $snum,$settings_ppg");
		if ($myrows) {
			foreach ($myrows as $myrow) {
				if ($stype == 'logs') {
					$dl_log_ts=$myrow->dl_log_ts;
					$dl_log_ip=$myrow->dl_log_ip;
					$dl_log_data=$myrow->dl_log_data;
				} else {
					$cf_file_id=$myrow->file_id;
					$cf_file_name=$myrow->file_name;
				}
				$enum++;
				$enum=$cwfa_cf->cwf_fmt_tho($enum);
				$search_results .='<li>'.$enum.') ';
				if ($stype == 'logs') {
					$dl_log_ts=cf_unixtime_to_human($dl_log_ts);
					list($cf_file_id,$cf_file_name)=explode('|',$dl_log_data,2);
					$dl_log_data='<a href="?page=cw-cloaked-files&cw_action=cloakededit&fid='.$cf_file_id.'">'.$cf_file_name.'</a>';
					$search_results .=$dl_log_ts.' '.$dl_log_data;
					if ($dl_log_ip) {
						$search_results .=' by '.$dl_log_ip;
					}
				} else {
					$search_results .='<a href="?page=cw-cloaked-files&cw_action=cloakededit&fid='.$cf_file_id.'">'.$cf_file_name.'</a>';
				}
				$search_results .='</li>';
				$enum=$cwfa_cf->cwf_san_int($enum);
			}
		}

		//	Show search text
		if ($search_results) {
			$snum++;
			$snum=$cwfa_cf->cwf_fmt_tho($snum);
			$enum=$cwfa_cf->cwf_fmt_tho($enum);
			$search_cnt=$cwfa_cf->cwf_fmt_tho($search_cnt);

			$search_results="<p>Displaying $snum to $enum out of $search_cnt</p><ul>$search_results</ul>";

			$snum=$cwfa_cf->cwf_san_int($snum);
			$enum=$cwfa_cf->cwf_san_int($enum);
			$search_cnt=$cwfa_cf->cwf_san_int($search_cnt);
		} else {
			$search_results='<li>Sorry, no matching records...  <a href="javascript:history.go(-1);">Continue</a></li>';
		}

		//	Build Page List
		if ($search_results) {
			$tpgsloop=$spg-4;
			$tpgsmax=$spg+3;

			if ($tpgsloop < '1') {
				$tpgsloop='0';
				$tpgsmax='7';
			}
			if ($spg > ($tpgs-6)) {
				$tpgsloop=$tpgs-7;
				$tpgsmax=$tpgs;
			}
			if ($tpgs < '9') {
				$tpgsloop='0';
				$tpgsmax=$tpgs;
			}
		
			while ($tpgsloop < $tpgsmax) {
				$tpgsloop++;
				if ($pgnavlist) {
					$pgnavlist .=' | ';
				}
				if ($tpgsloop == $spg) {
					$pgnavlist .=$tpgsloop;
				} else {
					$pgnavlist .='<a href="?page=cw-cloaked-files&cw_action=cloakedsearch&'.$cf_wherelink.'&spg='.$tpgsloop.'">'.$tpgsloop.'</a>';
				}
			}
			if ($pgnavlist) {
				if ($spg != '1') {
					$spgpx=$spg-1;
					$pgprevnxt='<a href="?page=cw-cloaked-files&cw_action=cloakedsearch&'.$cf_wherelink.'&spg='.$spgpx.'">Previous Page</a>';
				}
				if ($spg != $tpgs) {
					$spgpx=$spg+1;
					if ($pgprevnxt) {
						$pgprevnxt .=' | ';
					}
					$pgprevnxt .='<a href="?page=cw-cloaked-files&cw_action=cloakedsearch&'.$cf_wherelink.'&spg='.$spgpx.'">Next Page</a>';
				}
				if ($pgprevnxt) {
					$pgprevnxt=' .:. '.$pgprevnxt;
				}

				//	Show page list if more than one page
				if ($tpgs > '1') {
					$pgnavlist="<p>$cw_page_txt$pgprevnxt</p><p>Pages: $pgnavlist</p>";
				} else {
					$pgnavlist="<p>$cw_page_txt$pgprevnxt</p>";
				}

				if ($tpgs > '8') {
					$pgnavlist .='<p><form method="post" style="margin: 0px; 0px;"><input type="hidden" name="cw_action" value="cloakedsearch">'.$fs_form.'Jump To Page: <input type="text" name="spg" style="width: 40px;"> of '.$tpgs.' <input type="submit" value="Go" class="button"></form></p>';
				}
			} else {
				$pgnavlist='&nbsp;';
			}
		}

		$cw_cloaked_files_action='Searching '.$cloaked_files_stypes[$stype];
$cw_cloaked_files_html .=<<<EOM
<p>Results for: <b>$sbox</b></p>
$search_results
$pgnavlist
EOM;

	////////////////////////////////////////////////////////////////////////////
	//	Settings
	////////////////////////////////////////////////////////////////////////////
	} elseif ($cw_action == 'settings' or $cw_action == 'settingsv') {
		$cw_cloaked_files_action='View';

		if ($cw_action == 'settingsv') {
			$cw_cloaked_files_action='Sav';
			$error='';

			$cf_wp_option_array=array();

			$cloaked_files_base_url=$cwfa_cf->cwf_san_url($_REQUEST['cloaked_files_base_url']);
			if (!$cloaked_files_base_url) {
				$error .='<li>No Base Cloaked File URL</li>';
			} else {
				$cloaked_files_base_url=$cwfa_cf->cwf_trailing_slash_on($cloaked_files_base_url);
				$cf_wp_option_array['cloaked_files_base_url']=$cloaked_files_base_url;
			}

			$cloaked_files_records_ppg=$cwfa_cf->cwf_san_int($_REQUEST['cloaked_files_records_ppg']);
			if (!$cloaked_files_records_ppg or $cloaked_files_records_ppg > '300' or $cloaked_files_records_ppg < '10') {
				$cloaked_files_records_ppg='50';
			}
			$cf_wp_option_array['cloaked_files_records_ppg']=$cloaked_files_records_ppg;

			$cloaked_files_error_msg=trim($_REQUEST['cloaked_files_error_msg']);
			if (!$cloaked_files_error_msg) {
				$error .='<li>No File Not Located Message</li>';
			} else {
				$cf_wp_option_array['cloaked_files_error_msg']=$cloaked_files_error_msg;
			}

			$cloaked_files_download_ip='y';
			if (isset($_REQUEST['cloaked_files_download_ip'])) {
				$cloaked_files_download_ip=$cwfa_cf->cwf_san_an($_REQUEST['cloaked_files_download_ip']);
			}
			$cf_wp_option_array['cloaked_files_download_ip']=$cloaked_files_download_ip;
			
			$cloaked_files_download_txt=$cwfa_cf->cwf_san_title($_REQUEST['cloaked_files_download_txt']);
			if (!$cloaked_files_error_msg) {
				$error .='<li>No Download Prepend Message</li>';
			} else {
				$cf_wp_option_array['cloaked_files_download_txt']=$cloaked_files_download_txt;
			}

			if ($error) {
				$cw_cloaked_files_html='Please fix the following in order to save settings:<br><ul style="list-style: disc; margin-left: 25px;">'. $error .'</ul>'.$pplink;
			} else {
				$cf_wp_option_array=serialize($cf_wp_option_array);
				$cf_wp_option_chk=get_option($cf_wp_option);

				if (!$cf_wp_option_chk) {
					add_option($cf_wp_option,$cf_wp_option_array);
				} else {
					update_option($cf_wp_option,$cf_wp_option_array);
				}

				$cw_cloaked_files_html .='Settings have been saved! <a href="?page=cw-cloaked-files">Continue to Main Menu</a>';
			}

		} else {
			$cw_cloaked_files_action='Edit';

			if (!$cloaked_files_base_url) {
				$cloaked_files_base_url=site_url();
			}

			if (!$cloaked_files_records_ppg) {
				$cloaked_files_records_ppg='50';
			}
			
			$cloaked_files_error_msg=$cf_wp_option_array['cloaked_files_error_msg'];
			$cloaked_files_error_msg=stripslashes($cloaked_files_error_msg);
			if (!$cloaked_files_error_msg) {
				$cloaked_files_error_msg='Unfortunately that file doesn\'t exist!';
			}

			$cloaked_files_download_txt=$cf_wp_option_array['cloaked_files_download_txt'];
			if (!$cloaked_files_download_txt) {
				$cloaked_files_download_txt='Click to download';
			}
			
			$cloaked_files_download_ip=$cf_wp_option_array['cloaked_files_download_ip'];
			$cfdipstats='';
			if ($cloaked_files_download_ip == 'n') {
				$cfdipstats='checked';
			}

$cw_cloaked_files_html .=<<<EOM
<form method="post">
<input type="hidden" name="cw_action" value="settingsv">
<p>Base Cloaked File URL:<div style="margin-left: 20px;">What is the full URL to the cloaked files folder? (Include trailing slash)</div></p>
<p><input type="text" name="cloaked_files_base_url" value="$cloaked_files_base_url" style="width: 400px;"></p>
<p>Cloaked Files Per Page:<div style="margin-left: 20px;">When running searches how many cloaked file records should be displayed per page? 10-300</div></p>
<p><input type="text" name="cloaked_files_records_ppg" value="$cloaked_files_records_ppg" style="width: 50px;"></p>
<p>File Not Located Message:<div style="margin-left: 20px;">Message to display to visitor when no download is found.</div></p>
<p><input type="text" name="cloaked_files_error_msg" value="$cloaked_files_error_msg" style="width: 400px;"></p>
<p>Download Prepend Message:<div style="margin-left: 20px;">This text will appear at the beginning of the download link.</div></p>
<p><input type="text" name="cloaked_files_download_txt" value="$cloaked_files_download_txt" style="width: 400px;"></p>
<p>Don't Log IPs:</p>
<p><input type="checkbox" name="cloaked_files_download_ip" value="n"$cfdipstats> If checked system won't save downloader's IP</p>
<p><input type="submit" value="Save" class="button">
</form>
EOM;
		}
		$cw_cloaked_files_action .='ing Settings';
		
	////////////////////////////////////////////////////////////////////////////
	//	What Is New?
	////////////////////////////////////////////////////////////////////////////
	} elseif ($cw_action == 'settingsnew') {
		$cw_cloaked_files_action='What Is New?';

$cw_cloaked_files_html .=<<<EOM
<p>The following lists the new changes from version-to-version.</p>
<p>Version: <b>1.0</b></p>
<ul style="list-style: disc; margin-left: 25px;">
<li>Initial release of plugin</li>
</ul>
EOM;

	////////////////////////////////////////////////////////////////////////////
	//	Help Guide
	////////////////////////////////////////////////////////////////////////////
	} elseif ($cw_action == 'settingshelp') {
		$cw_cloaked_files_action='Help Guide';

$cw_cloaked_files_html .=<<<EOM
<div style="margin: 10px 0px 5px 0px; width: 400px; border-bottom: 1px solid #c16a2b; padding-bottom: 5px; font-weight: bold;">Introduction:</div>
<p>This system allows you to cloak (hide) file downloads so only visitors with special URLs can retrieve those files.  You may also have multiple secret download pages with different layouts to allow for more targeted content.</p>
<p>Steps:</p>
<ol>
<li><p>In <b>Settings</b> edit the information to properly setup important details.</p></li>
<li><p>Now add cloaked file records.  There is no uploader so you use the built in Wordpress media manager, FTP program, webhosting control panel, etc.</p></li>
<li><p>Create at least one download page.  This is done by simply using Wordpress' built in page (or post) system to create the layout to meet your design concept.  Wherever you want the actual download link to appear on that page simply insert the code <b>[cw_cloaked_files]</b> and Wordpress will insert the necessary link.  You should make this page private so it won't appear in the RSS feed, be sent to the search engines, or be linked anywhere on your site.</p>
<li>Now you are ready to get your special download links to your cloaked files.  You simply edit the cloaked file record for the desired file and you will see <b>?fid=[some code here]</b>.  All you do is add that code to the end of your secret download page.<br><br>
For example your site is http://www.somedomain.tld and your secret download page is called bonusdownloads.  This would make the download link be http://www.somedomain.tld/bonusdownloads?fid=[some code] and that is what you would pass out to your visitors in emails or wherever desired.</li>
<li>Finally add additional cloaked file records into the system along with other download pages, as needed.</li>
<li>You may search your cloaked file records and download logs on the main panel.</li>
</ol>
EOM;
	
	////////////////////////////////////////////////////////////////////////////
	//	Main panel
	////////////////////////////////////////////////////////////////////////////
	} else {
	
			//	Count Redirects
		$cw_cloaked_files_count='';
		$myrows=$wpdb->get_results("SELECT count(file_id) as file_id_cnt FROM $cw_cloaked_files_tbl");
		if ($myrows) {
			foreach ($myrows as $myrow) {
				$cw_cloaked_files_count=$cwfa_cf->cwf_fmt_tho($myrow->file_id_cnt);
			}
		}

		$cloaked_files_type_list='';
		foreach ($cloaked_files_stypes as $search_type => $search_type_words) {
			$cloaked_files_type_list .='<option value="'.$search_type.'">'.$search_type_words.'</option>';
		}
		
$cw_cloaked_files_action='Main Panel';
$cw_cloaked_files_html .=<<<EOM
<div style="width: 400px; text-align: center;">
<p>Cloaked Files: $cw_cloaked_files_count&nbsp;&nbsp;&nbsp;(<a href="?page=cw-cloaked-files&cw_action=cloakedadd">Add Cloaked File</a>)</p>
<form method="post" style="margin: 0px; padding: 0px;">
<input type="hidden" name="cw_action" value="cloakedsearch">
<p>Search for: <input type="text" name="sbox" style="width: 300px;"></p>
<p>In: <select name="stype">$cloaked_files_type_list</select>&nbsp;&nbsp;&nbsp;<input type="submit" value="Go" class="button"></p>
<div style="margin-top: 5px; width: 350px; font-size: 10px; font-style: italic;">Just hit "Go" to bring up all records</div>
</form>
</div>
EOM;
	}
	
	////////////////////////////////////////////////////////////////////////////
	//	Send to print out
	////////////////////////////////////////////////////////////////////////////
	cw_cloaked_files_admin_browser($cw_cloaked_files_html,$cw_cloaked_files_action);
}

////////////////////////////////////////////////////////////////////////////
//	Print out to browser (wp)
////////////////////////////////////////////////////////////////////////////
function cw_cloaked_files_admin_browser($cw_cloaked_files_html,$cw_cloaked_files_action) {
$cw_plugin_name='cleverwise-cloaked-files';
print <<<EOM
<style type="text/css">
#cws-wrap {margin: 20px 20px 20px 0px;}
#cws-wrap a {text-decoration: none; color: #3991bb;}
#cws-wrap a:hover {text-decoration: underline; color: #ce570f;}
#cws-nav {width: 400px; padding: 0px; margin-top: 10px; background-color: #deeaef; -moz-border-radius: 5px; border-radius: 5px;}
#cws-resources {width: 400px; padding: 0px; margin: 40px 0px 20px 0px; background-color: #c6d6ad; -moz-border-radius: 5px; border-radius: 5px; font-size: 12px; color: #000000;}
#cws-resources a {text-decoration: none; color: #28394d;}
#cws-resources a:hover {text-decoration: none; background-color: #28394d; color: #ffffff;}
#cws-inner {padding: 5px;}
</style>
<div id="cws-wrap" name="cws-wrap">
<h2 style="padding: 0px; margin: 0px;">Cleverwise Cloaked Files Management</h2>
<div style="margin-top: 7px; width: 90%; font-size: 10px; line-height: 1;">Easily cloak (hide) unlimited file downloads on your site so that your visitors must know secret codes to view them. This allows you to provide selective people, such as ecommerce purchasers, with the necessary codes to download the protected files.</div>
<div id="cws-nav" name="cws-nav"><div id="cws-inner" name="cws-inner"><a href="?page=cw-cloaked-files">Main Panel</a> | <a href="?page=cw-cloaked-files&cw_action=settings">Settings</a> | <a href="?page=cw-cloaked-files&cw_action=settingshelp">Help Guide</a> | <a href="?page=cw-cloaked-files&cw_action=settingsnew">What Is New?</a></div></div>
<p style="font-size: 13px; font-weight: bold;">Current: <span style="color: #ab5c23;">$cw_cloaked_files_action</span></p>
<p>$cw_cloaked_files_html</p>
<div id="cws-resources" name="cws-resources"><div id="cws-inner" name="cws-inner">Resources (open in new windows):<br>
<a href="https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=7VJ774KB9L9Z4" target="_blank">Donate - Thank You!</a> | <a href="http://wordpress.org/support/plugin/$cw_plugin_name" target="_blank">Get Support</a> | <a href="http://wordpress.org/support/view/plugin-reviews/$cw_plugin_name" target="_blank">Review Plugin</a> | <a href="http://www.cyberws.com/cleverwise-plugins/plugin-suggestion/" target="_blank">Suggest Plugin</a><br>
<a href="http://www.cyberws.com/cleverwise-plugins" target="_blank">Cleverwise Plugins</a> | <a href="http://www.cyberws.com/professional-technical-consulting/" target="_blank">Wordpress +PHP,Server Consulting</a></div></div>
</div>
EOM;
}

////////////////////////////////////////////////////////////////////////////
//	Generate File ID
////////////////////////////////////////////////////////////////////////////
function cf_generate_fid() {
	$fid_field=array('a','b','c','d','e','f','g','h','i','j','k','l','m','n','o','p','q','r','s','t','u','v','w','x','y','z','A','B','C','D','E','F','G','H','I','J','K','L','M','N','O','P','Q','R','S','T','U','V','W','X','Y','Z','1','2','3','4','5','6','7','8','9','0');
	$fid='';
	$fi='0';
	while ($fi < '48') {
		$fidrand=array_rand($fid_field,'1');
		$fid .=$fid_field[$fidrand];
		$fi++;
	}
	return $fid;
}

////////////////////////////////////////////////////////////////////////////
//	Convert Unixtime To Human Time
////////////////////////////////////////////////////////////////////////////
function cf_unixtime_to_human($convertime) {
	$convertime=date('jS-F-Y',$convertime);
	return $convertime;
}

////////////////////////////////////////////////////////////////////////////
//	Activate
////////////////////////////////////////////////////////////////////////////
function cw_cloaked_files_activate() {
	Global $wpdb,$cf_wp_option_version_txt,$cf_wp_option_version_num,$cw_cloaked_files_tbl,$cw_cloaked_files_logs_tbl;
	require_once(ABSPATH.'wp-admin/includes/upgrade.php');

	$cf_wp_option_db_version=get_option($cf_wp_option_version_txt);

//	Create cloaked files table
	$table_name=$cw_cloaked_files_tbl;
$sql .=<<<EOM
CREATE TABLE IF NOT EXISTS `$table_name` (
  `file_id` char(48) NOT NULL,
  `file_url` varchar(100) NOT NULL,
  `file_name` varchar(100) NOT NULL,
  `file_remap` varchar(48) NOT NULL,
  UNIQUE KEY `file_id` (`file_id`)
) DEFAULT CHARSET=utf8;
EOM;
	dbDelta($sql);

//	Create cloaked files log table
	$table_name=$cw_cloaked_files_logs_tbl;
$sql .=<<<EOM
CREATE TABLE IF NOT EXISTS `$table_name` (
  `dl_log_id` int(15) unsigned NOT NULL AUTO_INCREMENT,
  `dl_log_ts` int(15) unsigned NOT NULL,
  `dl_log_ip` varchar(40) NOT NULL,
  `dl_log_data` varchar(150) NOT NULL,
  PRIMARY KEY (`dl_log_id`)
) CHARSET=utf8;
EOM;
	dbDelta($sql);
	
//	Insert version number
	if (!$cf_wp_option_db_version) {
		add_option($cf_wp_option_version_txt,$cf_wp_option_version_num);
	}
}