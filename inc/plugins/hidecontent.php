<?php

// Plugin : Hide content from guest 4.0
// Author : Harshit Shrivastava
// 2016-2017

// Disallow direct access to this file for security reasons

if(!defined("IN_MYBB"))
{
	die("Direct initialization of this file is not allowed.<br /><br />Please make sure IN_MYBB is defined.");
}
$plugins->add_hook("postbit", "hidecontent_postbit");
$plugins->add_hook("printthread_post", "hidecontent_print");
$plugins->add_hook("archive_thread_post", "hidecontent_archive");
$plugins->add_hook("syndication_get_posts", "hidecontent_syndicate");

function hidecontent_info()
{
	return array(
		"name"			=> "Hide content from guest",
		"description"	=> "Hide your thread content from guests & users",
		"website"		=> "http://mybb.com",
		"author"		=> "Harshit Shrivastava",
		"authorsite"	=> "mailto:harshit_s21@rediffmail.com",
		"version"		=> "4.0",
		"guid" 			=> "dcda923d29ec5dfb852a993160ca8356",
		"compatibility" => "18*"
	);
}

function hidecontent_validate($fid)
{
	global $mybb;
	if($mybb->settings['hidecontent_exclude'])
	{
		$fids = explode(",", $mybb->settings['hidecontent_exclude']);
		if(in_array($fid, $fids))
		{
			return False;
		}
	}
	return True;
}
function hidecontent_userboardvalidate($fid)
{
	global $mybb;
	if($mybb->settings['hidecontent_userexclude'])
	{
		$fids = explode(",", $mybb->settings['hidecontent_userexclude']);
		if(in_array($fid, $fids))
		{
			return False;
		}
	}
	return True;
}
function hidecontent_usergroupvalidate($gid){
	global $mybb;	
	if($mybb->settings['hidecontent_user_groupexclude'])
	{
		$gids = explode(",", $mybb->settings['hidecontent_user_groupexclude']);
		if(in_array($gid, $gids) || $gid == 4 || $gid == 3)
		{
			return False;
		}
	}
	else
	{
		if($gid == 4 || $gid == 3)
			return False;
	}
	return True;
}
function hidecontent_checkUserAgent(){
	$userAgents = array("Googlebot", "Slurp", "MSNBot", "ia_archiver", "Yandex", "Rambler","bingbot","GurujiBot","Baiduspider","facebook");
	return preg_match('/' . strtolower(implode('|', $userAgents)) . '/i', strtolower($_SERVER['HTTP_USER_AGENT']));
}
function hidecontent_checkUser(&$post, $type){
	global $mybb, $lang, $postCount, $thread, $postCount;
	$lang->load("hidecontent");
	$postCount++;
	if ($mybb->settings['hidecontent_show'] == 1 && $mybb->user['uid'] == 0 && hidecontent_validate($post['fid']) && !hidecontent_checkUserAgent() && (($mybb->settings['hidecontent_hidemode'] == "post" && $postCount == 1) || ($mybb->settings['hidecontent_hidemode'] == "replies" && $postCount > 1) || ($mybb->settings['hidecontent_hidemode'] == "both")))
		if($type == "syn")
			return empty(trim($mybb->settings['hidecontent_code']))?true:false;
		else
			return empty(trim($mybb->settings['hidecontent_code']))?$lang->hide_guest_msg:$mybb->settings['hidecontent_code'];
	if ($mybb->user['uid'] != 0 && $mybb->settings['hidecontent_usershow'] == 1 && $mybb->settings['hidecontent_userpost'] > $mybb->user['postnum'] && $thread['uid'] != $mybb->user['uid'] && $post['uid'] != $mybb->user['uid'] && hidecontent_usergroupvalidate($mybb->user['usergroup']) && hidecontent_userboardvalidate($post['fid']) && !hidecontent_checkUserAgent() && (($mybb->settings['hidecontent_userhidemode'] == "post" && $postCount == 1) || ($mybb->settings['hidecontent_userhidemode'] == "replies" && $postCount > 1) || ($mybb->settings['hidecontent_userhidemode'] == "both")))
		if($type == "syn")
			return empty(trim($mybb->settings['hidecontent_code']))?true:false;
		else
			return empty(trim($mybb->settings['hidecontent_usercode']))?$lang->sprintf($lang->hide_user_msg, $mybb->user['username'], $mybb->settings['hidecontent_userpost']):$mybb->settings['hidecontent_usercode'];
}
$postCount=0;
function hidecontent_postbit(&$post)
{
	$temp = hidecontent_checkUser($post,"");
	if(!empty($temp)) {
		$post['attachments'] = "";
		$post['message'] = $temp;
	}
}
function hidecontent_print()
{
	global $postrow;
	$temp = hidecontent_checkUser($postrow,"");
	if(!empty($temp)) $postrow['message'] = $temp;
	
}
function hidecontent_archive()
{
	global $post;
	$temp = hidecontent_checkUser($post,"");
	if(!empty($temp)) $post['message'] = $temp;
}
function hidecontent_syndicate()
{
	global $firstposts, $post;
	if(hidecontent_checkUser($post, "syn") == true)	$firstposts = null;
}
function hidecontent_activate()
{
global $db;
$hidecontent_group = array(
        'gid'    => 'NULL',
        'name'  => 'hidecontent',
        'title'      => 'Hide content from guests',
        'description'    => 'Hide your thread content from guests',
        'disporder'    => "1",
        'isdefault'  => "0",
    ); 
$db->insert_query('settinggroups', $hidecontent_group);
$gid = $db->insert_id(); 
// Enable / Disable
$hidecontent_setting1 = array(
        'sid'            => 'NULL',
        'name'        => 'hidecontent_show',
        'title'            => 'Enable on board',
        'description'    => 'If you set this option to yes, this plugin will hide content from the posts.',
        'optionscode'    => 'yesno',
        'value'        => '1',
        'disporder'        => 1,
        'gid'            => intval($gid),
    );
$hidecontent_setting2 = array(
        'sid'            => 'NULL',
        'name'        => 'hidecontent_code',
        'title'            => 'Enter Message',
        'description'    => 'You can enter HTML code',
        'optionscode'    => 'textarea',
        'value'        => '',
        'disporder'        => 2,
        'gid'            => intval($gid),
    );
$hidecontent_setting3 = array(
        'sid'            => 'NULL',
        'name'        => 'hidecontent_exclude',
        'title'            => 'Forum ID without this mod',
        'description'    => 'If you do not want to use this mod on a forum or forums put ID separated by comma. Ex. 2,5,7',
        'optionscode'    => 'text',
        'value'        => '0',
        'disporder'        => 3,
        'gid'            => intval($gid),
    );
$hidecontent_setting4 = array(
        'sid'            => 'NULL',
        'name'        => 'hidecontent_hidemode',
        'title'            => 'Post Hide Mode',
        'description'    => 'Select the mode to hide the post.',
        'optionscode'    => 'select
both=Hide both post & replies
post=Hide only post
replies=Hide only replies
',
        'value'        => '1',
        'disporder'        => 4,
        'gid'            => intval($gid),
    );
	
$hidecontent_setting5 = array(
        'sid'            => 'NULL',
        'name'        => 'hidecontent_usershow',
        'title'            => 'Enable for users',
        'description'    => 'If you set this option to yes, this plugin will hide content from the posts for the registered users.',
        'optionscode'    => 'yesno',
        'value'        => '1',
        'disporder'        => 5,
        'gid'            => intval($gid),
    );

$hidecontent_setting6 = array(
        'sid'            => 'NULL',
        'name'        => 'hidecontent_userpost',
        'title'            => 'Minimum number of post to show message',
        'description'    => 'Enter number of posts',
        'optionscode'    => 'text',
        'value'        => '2',
        'disporder'        => 6,
        'gid'            => intval($gid),
    );
	
$hidecontent_setting7 = array(
        'sid'            => 'NULL',
        'name'        => 'hidecontent_usercode',
        'title'            => 'Enter Message for users',
        'description'    => 'You can enter HTML code',
        'optionscode'    => 'textarea',
        'value'        => '',
        'disporder'        => 7,
        'gid'            => intval($gid),
    );
	
$hidecontent_setting8 = array(
        'sid'            => 'NULL',
        'name'        => 'hidecontent_userexclude',
        'title'            => 'Forum ID without this mod',
        'description'    => 'If you do not want to use this mod on a forum or forums put ID separated by comma. Ex. 2,5,7',
        'optionscode'    => 'text',
        'value'        => '0',
        'disporder'        => 8,
        'gid'            => intval($gid),
    );
$hidecontent_setting9 = array(
        'sid'            => 'NULL',
        'name'        => 'hidecontent_userhidemode',
        'title'            => 'Post Hide Mode',
        'description'    => 'Select the mode to hide the post.',
        'optionscode'    => 'select
both=Hide both post & replies
post=Hide only post
replies=Hide only replies
',
        'value'        => '1',
        'disporder'        => 9,
        'gid'            => intval($gid),
    );

$hidecontent_setting10 = array(
        'sid'            => 'NULL',
        'name'        => 'hidecontent_user_groupexclude',
        'title'            => 'Group ID without this mod',
        'description'    => 'If you do not want to use this mod on a group or groups put ID separated by comma. Ex. 2,5,7',
        'optionscode'    => 'text',
        'value'        => '0',
        'disporder'        => 10,
        'gid'            => intval($gid),
    );
$db->insert_query('settings', $hidecontent_setting1);
$db->insert_query('settings', $hidecontent_setting2);
$db->insert_query('settings', $hidecontent_setting3);
$db->insert_query('settings', $hidecontent_setting4);
$db->insert_query('settings', $hidecontent_setting5);
$db->insert_query('settings', $hidecontent_setting6);
$db->insert_query('settings', $hidecontent_setting7);
$db->insert_query('settings', $hidecontent_setting8);
$db->insert_query('settings', $hidecontent_setting9);
$db->insert_query('settings', $hidecontent_setting10);
  rebuild_settings();
}
function hidecontent_deactivate()
{
  global $db;
  $db->query("DELETE FROM ".TABLE_PREFIX."settings WHERE name = 'hidecontent_show'");
  $db->query("DELETE FROM ".TABLE_PREFIX."settings WHERE name = 'hidecontent_code'");
  $db->query("DELETE FROM ".TABLE_PREFIX."settings WHERE name = 'hidecontent_exclude'");
  $db->query("DELETE FROM ".TABLE_PREFIX."settings WHERE name = 'hidecontent_hidemode'");
  $db->query("DELETE FROM ".TABLE_PREFIX."settings WHERE name = 'hidecontent_usershow'");
  $db->query("DELETE FROM ".TABLE_PREFIX."settings WHERE name = 'hidecontent_usercode'");
  $db->query("DELETE FROM ".TABLE_PREFIX."settings WHERE name = 'hidecontent_userpost'");
  $db->query("DELETE FROM ".TABLE_PREFIX."settings WHERE name = 'hidecontent_userexclude'");
  $db->query("DELETE FROM ".TABLE_PREFIX."settings WHERE name = 'hidecontent_user_groupexclude'");
  $db->query("DELETE FROM ".TABLE_PREFIX."settings WHERE name = 'hidecontent_userhidemode'");
  $db->query("DELETE FROM ".TABLE_PREFIX."settinggroups WHERE name='hidecontent'");
  rebuild_settings();
}
?>
