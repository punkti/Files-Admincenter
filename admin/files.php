<?php
/*
##########################################################################
#                                                                        #
#           Version 4       /                        /   /               #
#          -----------__---/__---__------__----__---/---/-               #
#           | /| /  /___) /   ) (_ `   /   ) /___) /   /                 #
#          _|/_|/__(___ _(___/_(__)___/___/_(___ _/___/___               #
#                       Free Content / Management System                 #
#                                   /                                    #
#                                                                        #
#                                                                        #
#   Copyright 2005-2010 by webspell.org                                  #
#                                                                        #
#   visit webSPELL.org, webspell.info to get webSPELL for free           #
#   - Script runs under the GNU GENERAL PUBLIC LICENSE                   #
#   - It's NOT allowed to remove this copyright-tag                      #
#   -- http://www.fsf.org/licensing/licenses/gpl.html                    #
#                                                                        #
#   Code based on WebSPELL Clanpackage (Michael Gruber - webspell.at),   #
#   Far Development by Development Team - webspell.org                   #
#                                                                        #
#   visit webspell.org                                                   #
#                                                                        #
##########################################################################
*/

$_language->read_module('files');

if(!isfileadmin($userID) OR mb_substr(basename($_SERVER['REQUEST_URI']),0,15) != "admincenter.php") die($_language->module['access_denied']);

$enableBbCode = false;
$filepath = "../downloads/";

function generate_options($filecats = '', $offset = '', $subcatID = 0) {
	$rubrics = safe_query("SELECT * FROM ".PREFIX."files_categorys WHERE subcatID = '".$subcatID."' ORDER BY name");
	while($dr = mysql_fetch_array($rubrics)) {
		$filecats .= '<option value="'.$dr['filecatID'].'">'.$offset.htmlspecialchars($dr['name']).'</option>';
		if(mysql_num_rows(safe_query("SELECT * FROM ".PREFIX."files_categorys WHERE subcatID = '".$dr['filecatID']."'"))) {
			$filecats .= generate_options("", $offset."- ", $dr['filecatID']);
		}
	}
	return $filecats;
}

function unit_to_size($num, $unit) {
	switch($unit) {
		case 'b': $size=$num; break;
		case 'kb': $size=$num*1024; break;
		case 'mb': $size=$num*1024*1024; break;
		case 'gb': $size=$num*1024*1024*1024; break;
	}
	return $size;
}

$action = isset($_GET['action']) ? $_GET['action'] : '';

if($action == 'add') {
	echo '<h1>&curren; <a href="admincenter.php?site=files" class="white">'.$_language->module['files'].'</a> &raquo; '.$_language->module['new_file'].'</h1>';
	$id = $name = $info = $currentFileUrl = $size = $addonKey = '';

	$extern = 'http://';
	$mirrors = array('', '');
	
	$filecatsOptions = generate_options();
	$accessOptions = '<option value="0">'.$_language->module['all'].'</option><option value="1">'.$_language->module['registered'].'</option><option value="2">'.$_language->module['clanmember'].'</option>';
	$unit = '<option value="b">Byte</option><option value="kb">KByte</option><option value="mb">MByte</option><option value="gb">GByte</option>';
	
	$CAPCLASS = new Captcha;
	$CAPCLASS->create_transaction();
	$hash = $CAPCLASS->get_hash();

	$addbbcode = '';
	$addflags = '';
	if($enableBbCode) {
		$_language->read_module('bbcode', true);
		eval ("\$addbbcode = \"".gettemplate("addbbcode", "html", "admin")."\";");
		eval ("\$addflags = \"".gettemplate("flags_admin", "html", "admin")."\";");
	}

	$type = 'save';
	$type_value = $_language->module['edit'];
	eval ("\$html = \"".gettemplate("admincenter_file_edit", "html", "admin")."\";");
	echo $html;
}

elseif($action=="edit") {
	echo '<h1>&curren; <a href="admincenter.php?site=files" class="white">'.$_language->module['files'].'</a> &raquo; '.$_language->module['edit_file'].'</h1>';
	if(isset($_GET['fileID']) && ($id = $_GET['fileID']) && is_numeric($id)) {
		$ergebnis = safe_query("SELECT * FROM `".PREFIX."files` WHERE fileID = {$id}");
		if(mysql_num_rows($ergebnis)) {
			$ds = mysql_fetch_assoc($ergebnis);
			$id = $ds['fileID'];
			$name = getinput($ds['filename']);
			$info = getinput($ds['info']);
			$addonKey = '';
			
			$filecats = generate_options();
			$filecatsOptions = str_replace('value="'.$ds['filecatID'].'"', 'value="'.$ds['filecatID'].'" selected="selected"', $filecats);
			$accessmenu = '<option value="0">'.$_language->module['all'].'</option><option value="1">'.$_language->module['registered'].'</option><option value="2">'.$_language->module['clanmember'].'</option>';
			$accessOptions = str_replace('value="'.$ds['accesslevel'].'"', 'value="'.$ds['accesslevel'].'" selected="selected"', $accessmenu);
						
			$extern = 'http://';
			if(stristr($ds['file'],"http://") || stristr($ds['file'],"ftp://")) $extern = $ds['file'];
			
			if(validate_url($ds['file'])) {
				$currentFileUrl = $ds['file'];
			} elseif(!empty($ds['file'])) {
				$currentFileUrl = 'http://'.$hp_url.'/downloads/'.$ds['file'];
			}
			
			$mirrors = array('', '');
			if(!empty($ds['mirrors'])) {
				foreach(explode('||', $ds['mirrors']) as $index => $mirror) {
					$mirrors[$index] = $mirror;
				}
			}
					
			$sizeinfo = strtolower(detectfilesize($ds['filesize']));
			$sizeinfo = explode(" ",$sizeinfo);

			$size = $sizeinfo[0];
			$unit = '<option value="b">Byte</option><option value="kb">KByte</option><option value="mb">MByte</option><option value="gb">GByte</option>';
			switch($sizeinfo[1]) {
				case 'byte': $unit = str_replace('value="b"','value="b" selected="selected"', $unit); break;
				case 'kb': $unit = str_replace('value="kb"','value="kb" selected="selected"', $unit); break;
				case 'mb': $unit = str_replace('value="mb"','value="mb" selected="selected"', $unit); break;
				case 'gb': $unit = str_replace('value="gb"','value="gb" selected="selected"', $unit); break;
			}
			
			$CAPCLASS = new Captcha;
			$CAPCLASS->create_transaction();
			$hash = $CAPCLASS->get_hash();
			
			$addbbcode = '';
			$addflags = '';
			if($enableBbCode) {
				$_language->read_module('bbcode', true);
				eval ("\$addbbcode = \"".gettemplate("addbbcode", "html", "admin")."\";");
				eval ("\$addflags = \"".gettemplate("flags_admin", "html", "admin")."\";");
			}
			
			$type = 'saveedit';
			$type_value = $_language->module['edit'];
			eval ("\$html = \"".gettemplate("admincenter_file_edit", "html", "admin")."\";");
			echo $html;
		} else {
			redirect('admincenter.php?site=files', 'file does not exist.', 3);
		}
	} else {
		redirect('admincenter.php?site=files', '', 0);
	}
}

elseif(isset($_POST['save'])) {
	echo '<h1>&curren; <a href="admincenter.php?site=files" class="white">'.$_language->module['files'].'</a> &raquo; '.$_language->module['new_file'].'</h1>';
	$CAPCLASS = new Captcha;
	if($CAPCLASS->check_captcha(0, $_POST['captcha_hash'])) {
		$upload			= $_FILES['upfile'];
		$fileID			= $_POST['id'];
		$poster			= $_POST['poster'];
		$file			= $_POST['file'];
		$countEmpty		= countempty($file);
		$filecat		= $file['filecatID'];
		$filename		= $file['filename'];
		$fileurl		= $file['url'];
		$filesize		= unit_to_size($file['size'], $file['sizeunit']);
		$info			= $file['info'];
		$accesslevel	= $file['accesslevel'];
		$mirrors		= $file['mirrors'];
		$addonKey		= $file['addon_key'];
		if(empty($addonKey)) {
			$addonKey = createkey(9);
		}
		if($mirrors) {
			$saveMirrors = array();
			foreach($mirrors as $mirror) {
				if(!validate_url($mirror)) {
					continue;
				}
				$saveMirrors[] = $mirror;
			}
			$mirrors = '';
			if(!empty($saveMirrors)) {
				$mirrors = implode('||', $saveMirrors);
			}
		}
		unset($file);

		if($upload || $fileurl) {
			if($upload['name'] != '') {
				$des_file = $filepath . $upload['name'];
				if(!file_exists($des_file)) {
					if(move_uploaded_file($upload['tmp_name'], $des_file)) {
						$fileSource = $upload['name'];
						$filesize = $upload['size'];
						@chmod($des_file, $new_chmod);
					}
				}
				else {
					$des_file = $filepath . $time."_".$upload['name'];
					if(!file_exists($des_file)){
						if(move_uploaded_file($upload['tmp_name'], $des_file)) {
							$fileSource = $time."_".$upload['name'];
							$filesize = $upload['size'];
							@chmod($des_file, $new_chmod);
						}
					}
					else{
						$errorBefore = $_language->module['file_already_exists'];
					}
				}
			}
			elseif(validate_url($fileurl)) {
				$fileSource = $fileurl;
			}
			
			if(isset($errorBefore)) {
				echo $errorBefore;
			} else {
				$qry = "INSERT INTO `".PREFIX."files`
						(filecatID, poster, date, filename, filesize, info, file, mirrors, downloads, accesslevel )
					VALUES
						({$filecat}, {$poster}, {$time}, '{$filename}', '{$filesize}', '{$info}', '{$fileSource}', '{$mirrors}', 0, {$accesslevel})";
				if(safe_query($qry)) {
					$id = mysql_insert_id();
					$redirectUrl = 'admincenter.php?site=files&amp;action=edit&amp;fileID='.$id;
					$redirectText = $_language->module['file_created'];
				} else {
					$redirectUrl = 'admincenter.php?site=files';
					$redirectText = $_language->module['file_not_created'];
				}
				redirect($redirectUrl, $redirectText, 3);
			}
		} else {
			echo $_language->module['no_valid_file'];
		}
	} else echo $_language->module['transaction_invalid'];
}

elseif(isset($_POST['saveedit'])) {
//	$icon=$_FILES["icon"];
//	$country=$_POST["country"];
//	$short=$_POST["shorthandle"];
	echo '<h1>&curren; <a href="admincenter.php?site=files" class="white">'.$_language->module['files'].'</a> &raquo; '.$_language->module['edit_file'].'</h1>';
	$CAPCLASS = new Captcha;
	if($CAPCLASS->check_captcha(0, $_POST['captcha_hash'])) {
		$upload			= $_FILES['upfile'];
		$fileID			= $_POST['id'];
		$file			= $_POST['file'];
		$filecat		= $file['filecatID'];
		$filename		= $file['filename'];
		$fileurl		= $file['url'];
		$filesize		= unit_to_size($file['size'], $file['sizeunit']);
		$info			= $file['info'];
		$accesslevel	= $file['accesslevel'];
		$mirrors		= $file['mirrors'];
		$isAddon		= isset($file['is_addon']);
		$addonKey		= $file['addon_key'];
		
		if($isAddon) {
			foreach($file['version'] as $type => $version) {
				if(empty($version)) {
					continue;
				}
				$version = explode(',', trim($version));
				updateVersions($fileID, $version, $type);
			}
		}
		
		if($mirrors) {
			$saveMirrors = array();
			foreach($mirrors as $mirror) {
				if(!validate_url($mirror)) {
					continue;
				}
				$saveMirrors[] = $mirror;
			}
			$mirrors = '';
			if(!empty($saveMirrors)) {
				$mirrors = implode('||', $saveMirrors);
			}
		}
		unset($file);

		if($upload['name'] != '') {
			$des_file = $filepath.$upload['name'];
			if(file_exists($des_file)) {
				unlink($des_file);
			}
			if(move_uploaded_file($upload['tmp_name'], $des_file)) {
				$fileSource = $upload['name'];
				$filesize = $upload['size'];
				chmod($des_file, $new_chmod);
			}
		}	
		elseif($fileurl != 'http://' && validate_url($fileurl)) {
			$fileSource = $fileurl;
		}
		
		if(!safe_query("UPDATE `".PREFIX."files` SET 
			filecatID = {$filecat},
			mirrors = '{$mirrors}',
			filename = '{$filename}',
			filesize = '{$filesize}',
			info='{$info}',
			accesslevel='{$accesslevel}'
			WHERE fileID = {$fileID}")) {
			die(redirect('admincenter.php?site=files', $_language->module['failed_save_file-info'], 3));
		}
		if(isset($fileSource)) {
			if(!safe_query("UPDATE `".PREFIX."files` SET file= '{$fileSource}' WHERE fileID = {$fileID}")) {
				die(redirect('admincenter.php?site=files', $_language->module['failed_edit_file'], 3));
			}
		}
	redirect('admincenter.php?site=files', $_language->module['successful']);
	} else echo $_language->module['transaction_invalid'];
}

elseif(isset($_GET['delete'])) {
	echo '<h1>&curren; <a href="admincenter.php?site=files" class="white">'.$_language->module['files'].'</a> &raquo; '.$_language->module['delete_file'].'</h1>';
 	$CAPCLASS = new Captcha;
	if($CAPCLASS->check_captcha(0, $_GET['captcha_hash'])) {
		$id = $_GET['fileID'];
		safe_query("DELETE FROM `".PREFIX."files` WHERE fileID = {$id}");
		redirect('admincenter.php?site=files', '', 0);
	} else echo $_language->module['transaction_invalid'];
}

else {

  echo'<h1>&curren; '.$_language->module['files'].'</h1>';

  echo'<table width="100%" border="0" cellspacing="1" cellpadding="3">
      <tr>
        <td><input type="button" onclick="MM_goToURL(\'parent\',\'admincenter.php?site=files&amp;action=add\');return document.MM_returnValue" value="'.$_language->module['new_file'].'" /></td>
        <td align="right"><b>'.$_language->module['file_search'].':</b> &nbsp; <input id="exact" type="checkbox" /> '.$_language->module['exact'].' &nbsp; <input type="text" onkeyup=\'overlay(this, "searchresult");search("files","filename","fileID",encodeURIComponent(this.value),"search_admincenter_file","searchresult","replace", document.getElementById("exact").checked, "ac_usersearch")\' size="25" /><br />
        <div id="searchresult" style="position:absolute;display:none;border:1px solid black;background-color:#DDDDDD; padding:2px;"></div></td>
      </tr>
    </table>';

//  echo'<form method="post" action="admincenter.php?site=countries">
//  <table width="100%" border="0" cellspacing="1" cellpadding="3" bgcolor="#DDDDDD">
//    <tr>
//      <td width="15%" class="title"><b>'.$_language->module['icon'].'</b></td>
//      <td width="45%" class="title"><b>'.$_language->module['country'].'</b></td>
//      <td width="15%" class="title"><b>'.$_language->module['shorthandle'].'</b></td>
//      <td width="25%" class="title"><b>'.$_language->module['actions'].'</b></td>
//    </tr>';
    echo'<form method="post" action="admincenter.php?site=files">
  <table width="100%" border="0" cellspacing="1" cellpadding="3" bgcolor="#DDDDDD">
	<col />
	<col width="1" />
    <tr>
      <td class="title"><b>'.$_language->module['name'].'</b></td>
      <td class="title"><b>'.$_language->module['actions'].'</b></td>
    </tr>';

	if(!empty($_GET['search'])) {
		$search = $_GET['search'];
	}

	if(isset($search)) {
		$ds=safe_query("SELECT * FROM ".PREFIX."files WHERE fileID = {$search}");
	} else {
		$ds=safe_query("SELECT * FROM ".PREFIX."files ORDER BY fileID");
	}
	$anz=mysql_num_rows($ds);
	if($anz) {

		$i=0;
		$CAPCLASS = new Captcha;
		$CAPCLASS->create_transaction();
		$hash = $CAPCLASS->get_hash();

		while($file = mysql_fetch_assoc($ds)) {
			$td = ($i++%2) ? 'td2' : 'td1';

			$name = getinput($file['filename']);
			$id = $file['fileID'];


		  echo'<tr>
			<td class="'.$td.'">'.$name.'</td>
			<td class="'.$td.'" align="center"><span style="white-space:nowrap;"><input type="button" onclick="MM_goToURL(\'parent\',\'admincenter.php?site=files&amp;action=edit&amp;fileID='.$id.'\');return document.MM_returnValue" value="'.$_language->module['edit'].'" />
			<input type="button" onclick="MM_confirm(\''.$_language->module['really_delete_file'].'\', \'admincenter.php?site=files&amp;delete=true&amp;fileID='.$id.'&amp;captcha_hash='.$hash.'\')" value="'.$_language->module['delete'].'" /></span></td>
		  </tr>';
		}
	}
//    while($flags = mysql_fetch_array($ds)) {
//      if($i%2) { $td='td1'; }
//			else { $td='td2'; }
//			$pic='<img src="../images/flags/'.$flags['short'].'.gif" border="0" alt="'.$flags['country'].'" />';
//
//      echo'<tr>
//        <td class="'.$td.'" align="center">'.$pic.'</td>
//        <td class="'.$td.'">'.getinput($flags['country']).'</td>
//        <td class="'.$td.'" align="center">'.getinput($flags['short']).'</td>
//        <td class="'.$td.'" align="center"><input type="button" onclick="MM_goToURL(\'parent\',\'admincenter.php?site=countries&amp;action=edit&amp;countryID='.$flags['countryID'].'\');return document.MM_returnValue" value="'.$_language->module['edit'].'" />
//        <input type="button" onclick="MM_confirm(\''.$_language->module['really_delete'].'\', \'admincenter.php?site=countries&amp;delete=true&amp;countryID='.$flags['countryID'].'&amp;captcha_hash='.$hash.'\')" value="'.$_language->module['delete'].'" /></td>
//      </tr>';
//
//      $i++;
//		}
//	}
//  else echo'<tr><td class="td1" colspan="5">'.$_language->module['no_entries'].'</td></tr>';

  echo '</table>
  </form>
  <style type="text/css">
  <!--
	#searchresult {text-align:left;}
	-->
  </style>';
}
?>