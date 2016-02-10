<?php
ini_set('display_errors', 'On');
error_reporting(E_ALL | E_STRICT);
// Copyright 2014-2015 Rod Roark
//
// For using the WordPress API from an external program such as this, see:
// http://www.webopius.com/content/139/using-the-wordpress-api-from-pages-outside-of-wordpress
// ... including the reader comments.
// Rod's original work is found at:
// http://www.open-emr.org/wiki/index.php/Patient_Portal
//
// 2/10/2016 This is a modification of Rod's work by Craig Tucker for use with CF7 and CFDB in 
// WordPress. All references to NinjaForms have been deleted.
// 
// In order for this to work you will need to make the following modification:
// In /openemr/interface/cmsportal/upload_form.php line 46 change intval to floatval --
// e.g  $postid = empty($_REQUEST['postid' ]) ? 0 : floatval($_REQUEST['postid' ])
//
// You will also need to create forms with lables in CF7 with fields that match openemr fields. 
// I just copied field names used from Rod's Jinja Forms.

define('WP_USE_THEMES', false);
require('../../../wp-load.php');

// For use of the $wpdb object to access the WordPress database, see:
// http://codex.wordpress.org/Class_Reference/wpdb

$out = array('errmsg' => '');
$action = $_REQUEST['action'];

// These are the administrative settings for the Cartpauj PM plugin.
// We need to know who its messaging administrator is. This is likely to
// be the same as $_REQUEST['login'] but we cannot assume that.
$adminOps = get_option('cartpaujPM_options');
$admin_user_login = $adminOps['admin_user_login'];

// While the password is sent to us as plain text, this transport interface
// should always be encrypted via SSL (HTTPS). See also:
// http://codex.wordpress.org/Function_Reference/wp_authenticate
// http://codex.wordpress.org/Class_Reference/WP_User
$user = wp_authenticate($_REQUEST['login'], $_REQUEST['password']);

if (is_wp_error($user)) {
  $out['errmsg'] = "Portal authentication failed.";
}
// Portal administrator must have one of these capabilities.
// Note manage_portal is a custom capability added via User Role Editor.
else if (!$user->has_cap('create_users') && !$user->has_cap('manage_portal')) {
  $out['errmsg'] = "This login does not have permission to administer the portal.";
}
else {
  if ('list'        == $action) action_list       ($_REQUEST['date_from'], $_REQUEST['date_to']); else
  if ('getpost'     == $action) action_getpost    ($_REQUEST['postid']                         ); else
  if ('getupload'   == $action) action_getupload  ($_REQUEST['uploadid']                       ); else
  if ('delpost'     == $action) action_delpost    ($_REQUEST['postid']                         ); else
  if ('checkptform' == $action) action_checkptform($_REQUEST['patient'], $_REQUEST['form']     ); else
  if ('getmessage'  == $action) action_getmessage ($_REQUEST['messageid']                      ); else
  if ('getmsgup'    == $action) action_getmsgup   ($_REQUEST['uploadid']                       ); else
  if ('delmessage'  == $action) action_delmessage ($_REQUEST['messageid']                      ); else
  if ('adduser'     == $action) action_adduser($_REQUEST['newlogin'], $_REQUEST['newpass'], $_REQUEST['newemail']); else
  if ('putmessage'  == $action) action_putmessage ($_REQUEST                                   ); else
  // More TBD.
  $out['errmsg'] = 'Action not recognized!';
}

// For JSON-over-HTTP we would echo json_encode($out) instead of the following.
// However serialize() works better because it supports arbitrary binary data,
// thus attachments do not have to be base64-encoded.

$tmp = serialize($out);
header('Content-Description: File Transfer');
header('Content-Type: application/octet-stream');
header('Content-Disposition: attachment; filename=cmsreply.bin');
header('Content-Transfer-Encoding: binary');
header('Expires: 0');
header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
header('Pragma: public');
header('Content-Length: ' . strlen($tmp));
ob_clean();
flush();
echo $tmp;

function get_mime_type($filename) {
	$idx = explode( '.', $filename );
	$count_explode = count($idx);
	$idx = strtolower($idx[$count_explode-1]);
 
	$mimet = array(	
        'txt' => 'text/plain',
        'htm' => 'text/html',
        'html' => 'text/html',
        'php' => 'text/html',
        'css' => 'text/css',
        'js' => 'application/javascript',
        'json' => 'application/json',
        'xml' => 'application/xml',
        'swf' => 'application/x-shockwave-flash',
        'flv' => 'video/x-flv',

        // images
        'png' => 'image/png',
        'jpe' => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'jpg' => 'image/jpeg',
        'gif' => 'image/gif',
        'bmp' => 'image/bmp',
        'ico' => 'image/vnd.microsoft.icon',
        'tiff' => 'image/tiff',
        'tif' => 'image/tiff',
        'svg' => 'image/svg+xml',
        'svgz' => 'image/svg+xml',

        // archives
        'zip' => 'application/zip',
        'rar' => 'application/x-rar-compressed',
        'exe' => 'application/x-msdownload',
        'msi' => 'application/x-msdownload',
        'cab' => 'application/vnd.ms-cab-compressed',

        // audio/video
        'mp3' => 'audio/mpeg',
        'qt' => 'video/quicktime',
        'mov' => 'video/quicktime',

        // adobe
        'pdf' => 'application/pdf',
        'psd' => 'image/vnd.adobe.photoshop',
        'ai' => 'application/postscript',
        'eps' => 'application/postscript',
        'ps' => 'application/postscript',

        // ms office
        'doc' => 'application/msword',
        'rtf' => 'application/rtf',
        'xls' => 'application/vnd.ms-excel',
        'ppt' => 'application/vnd.ms-powerpoint',
        'docx' => 'application/msword',
        'xlsx' => 'application/vnd.ms-excel',
        'pptx' => 'application/vnd.ms-powerpoint',


        // open office
        'odt' => 'application/vnd.oasis.opendocument.text',
        'ods' => 'application/vnd.oasis.opendocument.spreadsheet',
	);
 
	if (isset( $mimet[$idx] )) {
	 return $mimet[$idx];
	} else {
	 return 'application/octet-stream';
	}
 }



function convertToID($login) {
  global $wpdb;
  $result = $wpdb->get_var($wpdb->prepare("SELECT ID FROM {$wpdb->users} WHERE user_login = %s", $login));
  if (!empty($result)) return $result;
  return 0;
}

// Logic to process the "list" action.
// For CF7, a row for every form submission.
//
function action_list($date_from='', $date_to='') {
  global $wpdb, $out, $admin_user_login;
  $out['list'] = array();
  $out['messages'] = array();
  
  date_default_timezone_set('UTC');
  $from_date=strtotime($date_from." 00:00:00");
  $to_date=strtotime($date_to." 23:59:59");

	$query =
	"SELECT submit_time, field_value, form_name " .
	"FROM {$wpdb->prefix}cf7dbplugin_submits " .
	"WHERE field_order = 9999";

    $qparms = array();
    if ($date_from) {
      $query .= " AND submit_time >= %d";
      $qparms[] = "$from_date";
    }
    if ($date_to) {
      $query .= " AND submit_time <= %d";
      $qparms[] = "$to_date";
    }
    $query .= " ORDER BY submit_time";

    $query = $wpdb->prepare($query, $qparms);
    if (empty($query)) {
      $out['errmsg'] = "Internal error: wpdb prepare() failed.";
      return;
    }
	
    $rows = $wpdb->get_results($query, ARRAY_A);
    foreach ($rows as $row) {
      $out['list'][] = array(
        'postid'   => $row['submit_time'],
        'user'     => (isset($row['field_value']) ? $row['field_value'] : ''),
        'datetime' => gmdate("Y-m-d H:i:s",$row['submit_time']),
        'type'     => $row['form_name'],
      );
    }
  
  // Get list of messages also.
  $query = "SELECT cm.id, cm.date, cm.message_title, " .
    "uf.user_login AS from_login, ut.user_login AS to_login " .
    "FROM {$wpdb->prefix}cartpauj_pm_messages AS cm " .
    "LEFT JOIN $wpdb->users AS uf ON uf.ID = cm.from_user " .
    "LEFT JOIN $wpdb->users AS ut ON ut.ID = cm.to_user " .
    "WHERE (cm.from_del = 0 AND uf.user_login = %s OR " .
    "cm.to_del = 0 AND ut.user_login = %s)";
  $qparms = array($admin_user_login, $admin_user_login);
  if ($date_from) {
    $query .= " AND cm.date >= %s";
    $qparms[] = "$date_from 00:00:00";
  }
  if ($date_to) {
    $query .= " AND cm.date <= %s";
    $qparms[] = "$date_to 23:59:59";
  }
  $query .= " ORDER BY cm.date";
  $query = $wpdb->prepare($query, $qparms);
  if (empty($query)) {
    $out['errmsg'] = "Internal error: wpdb prepare() failed.";
    return;
  }
  $rows = $wpdb->get_results($query, ARRAY_A);
  foreach ($rows as $row) {
    $out['messages'][] = array(
      'messageid' => $row['id'],
      'user'      => ($row['from_login'] == $admin_user_login ? $row['to_login'] : $row['from_login']),
      'fromuser'  => $row['from_login'],
      'touser'    => $row['to_login'],
      'datetime'  => $row['date'],
      'title'     => $row['message_title'],
    );
  }
}

// Logic to process the "getpost" action.
// The $postid argument identifies the form instance.
// For CF7 the submitted field values and names must be extracted from
// serialized globs, and each field name comes from its description text.
//
function action_getpost($postid) {
  global $wpdb, $out;
  $out['post'] = array();
  $out['uploads'] = array();

    // wp_posts has one row for each submitted form.
    // wp_nf_objectmeta includes a set of rows for each defined form.
    $query =
	"SELECT submit_time, form_name, field_value " .
	"FROM {$wpdb->prefix}cf7dbplugin_submits " .
	"WHERE field_order = 9999 AND submit_time = %f";

    $queryp = $wpdb->prepare($query, $postid);
    if (empty($queryp)) {
      $out['errmsg'] = "Internal error: \"$query\" \"$postid\"";
      return;
    }

    $row = $wpdb->get_row($queryp, ARRAY_A);
    if (empty($row)) {
      $out['errmsg'] = "No rows matching: \"$postid\"";
	  echo $queryp;
      return;
    }
    // $formid = $row['submit_time'];
    $out['post'] = array(
      'postid'   => $row['submit_time'],
      'user'     => (isset($row['field_value']) ? $row['field_value'] : ''),
      'datetime' => gmdate("Y-m-d H:i:s",$row['submit_time']),
      'type'     => $row['form_name'],
    );
    $out['fields'] = array();
    $out['labels'] = array();
    // wp_cf7dbplugin_submits has one row for each defined form field.
    $query2 =	  
		"SELECT ID, submit_time, field_name, field_value, file " .
		"FROM {$wpdb->prefix}cf7dbplugin_submits " .  
		"WHERE field_order < 9999 AND submit_time= %f " . 
		"ORDER BY field_order";
    $query2p = $wpdb->prepare($query2, $postid);
    $rows = $wpdb->get_results($query2p, ARRAY_A);
	
   foreach ($rows as $fldrow) {
     // Report uploads, if any.
      if (!empty($fldrow['file'])) { 
		// Put the info into the uploads array.
		
            $out['uploads'][] = array(
              'filename' =>  $fldrow['field_value'],
              'mimetype' => get_mime_type($fldrow['field_value']),
              'id'       => $fldrow['ID'],
            );
      }
	  
      // Each field that matches with a field name in OpenEMR must have that name in
      // its description text. 
      if (is_string($fldrow['field_value'])) {
      $out['fields'][$fldrow['field_name']] = $fldrow['field_value'];
      }
      $out['labels'][$fldrow['field_name']] = $fldrow['field_name'];
    }
}

// Logic to process the "delpost" action to delete a post.
//
function action_delpost($postid) {
  global $wpdb, $out;
    // If this form instance includes any file uploads, then delete the
    // uploaded files as well as the rows in CF7.
    action_getpost($postid);
    if ($out['errmsg']) return;

    $result = $wpdb->delete("{$wpdb->prefix}cf7dbplugin_submits",
        array('submit_time' => $postid), array('%f'));
	if ($result) {
		$out = array('errmsg' => '');
	} else {
		$out['errmsg'] = "Delete failed for post '$postid'";
	}
}

// Logic to process the "adduser" action to create a user as a patient.
//
function action_adduser($login, $pass, $email) {
  global $wpdb, $out, $user;
  // if (!$user->has_cap('create_users')) {
  //   $out['errmsg'] = "Portal administrator does not have permission to create users.";
  //   return;
  // }
  if (empty($login)) $login = $email;
  $userid = wp_insert_user(array(
    'user_login' => $login,
    'user_pass'  => $pass,
    'user_email' => $email,
    'role'       => 'patient',
  ));
  if (is_wp_error($userid)) {
    $out['errmsg'] = "Failed to add user '$login': " . $userid->get_error_message();
  }
  else {
    $out['userid'] = $userid;
  }
}

// Logic to process the "checkptform" action to determine if a form is pending for
// the given patient login and form name.  If it is its request ID is returned.
//
function action_checkptform($patient, $form) {
  global $wpdb, $out;
  $out['list'] = array();
    $query =
      "SELECT p.ID FROM " .
      "$wpdb->users AS u, " .
      "$wpdb->posts AS p, " .
      "$wpdb->postmeta AS pm, " .
      "{$wpdb->prefix}nf_objectmeta AS om " .
      "WHERE u.user_login = %s AND " .
      "p.post_author = u.id AND " .
      "p.post_type = 'nf_sub' AND " .
      "pm.post_id = p.ID AND " .
      "pm.meta_key = 'form_id' AND " .
      "om.object_id = pm.meta_value AND " .
      "om.meta_key = 'form_title' AND " .
      "om.meta_value = %s " .
      "ORDER BY p.ID LIMIT 1";
    $queryp = $wpdb->prepare($query, array($patient, $form));
    if (empty($queryp)) {
      $out['errmsg'] = "Internal error: \"$query\" \"$patient\" \"$form\"";
      return;
    }
    $row = $wpdb->get_row($queryp, ARRAY_A);
    $out['postid'] = empty($row['ID']) ? '0' : $row['ID'];
}

// Logic to process the "getupload" action.
// Returns filename, mimetype, datetime and contents for the specified upload ID.
//
function action_getupload($uploadid) {
  global $wpdb, $out;
	$query = "SELECT ID, submit_time, field_name, field_value, file " .
	"FROM {$wpdb->prefix}cf7dbplugin_submits WHERE ID = %f";
	
	$row = $wpdb->get_row($wpdb->prepare($query, array($uploadid)), ARRAY_A);
	$out['filename'] = $row['field_value'];
	$out['mimetype'] = get_mime_type($row['field_value']);
	//$out['contents'] = base64_encode($row['file']);
	$out['contents'] = $row['file'];
}


// Logic to process the "getmessage" action.
// The $messageid argument identifies the message.
//
function action_getmessage($messageid) {
  global $wpdb, $out, $admin_user_login;
  $out['message'] = array();
  $out['uploads'] = array();
  $query = "SELECT cm.id, cm.date, cm.message_title, cm.message_contents, " .
    "uf.user_login AS from_login, ut.user_login AS to_login " .
    "FROM {$wpdb->prefix}cartpauj_pm_messages AS cm " .
    "LEFT JOIN $wpdb->users AS uf ON uf.ID = cm.from_user " .
    "LEFT JOIN $wpdb->users AS ut ON ut.ID = cm.to_user " .
    "WHERE cm.id = %d";
  $queryp = $wpdb->prepare($query, array($messageid));
  if (empty($queryp)) {
    $out['errmsg'] = "Internal error: \"$query\" \"$postid\"";
    return;
  }
  $row = $wpdb->get_row($queryp, ARRAY_A);
  if (empty($row)) {
    $out['errmsg'] = "No messages matching: \"$messageid\"";
    return;
  }
  $out['message'] = array(
    'messageid' => $row['id'],
    'user'      => ($row['from_login'] == $admin_user_login ? $row['to_login'] : $row['from_login']),
    'fromuser'  => $row['from_login'],
    'touser'    => $row['to_login'],
    'datetime'  => $row['date'],
    'title'     => $row['message_title'],
    'contents'  => $row['message_contents'],
  );
  $query2 = "SELECT id, filename, mimetype " .
    "FROM {$wpdb->prefix}cartpauj_pm_attachments " .
    "WHERE message_id = %d ORDER BY filename, id";
  $query2p = $wpdb->prepare($query2, array($messageid));
  if (empty($query2p)) {
    $out['errmsg'] = "Internal error: \"$query2\" \"$messageid\"";
    return;
  }
  $msgrows = $wpdb->get_results($query2p, ARRAY_A);
  foreach ($msgrows as $msgrow) {
    $out['uploads'][] = array(
      'filename' => $msgrow['filename'],
      'mimetype' => $msgrow['mimetype'],
      'id'       => $msgrow['id'],
    );
  }
}

// Logic to process the "getmsgup" action.
// Returns filename, mimetype and contents for the specified upload ID.
//
function action_getmsgup($uploadid) {
  global $wpdb, $out;
  $query = "SELECT id, filename, mimetype, contents " .
    "FROM {$wpdb->prefix}cartpauj_pm_attachments " .
    "WHERE id = %d";
  $row = $wpdb->get_row($wpdb->prepare($query, array($uploadid)), ARRAY_A);
  $out['filename'] = $row['filename'];
  $out['mimetype'] = $row['mimetype'];
  // $out['contents'] = base64_encode($row['contents']);
  $out['contents'] = $row['contents'];
}

// Logic to process the "delmessage" action to delete a message.  It's not
// physically deleted until both sender and recipient delete it.  Note that we
// can delete (actually hide) a child message, but in WordPress that action is
// not supported; there only a parent message can be deleted.  In either case
// a physical delete also deletes all children and associated attachments.
//
function action_delmessage($messageid) {
  global $wpdb, $out, $admin_user_login;
  // Get message attributes so we can figure out what to do.
  $query = "SELECT cm.from_del, cm.to_del, " .
    "uf.user_login AS from_login, ut.user_login AS to_login " .
    "FROM {$wpdb->prefix}cartpauj_pm_messages AS cm " .
    "LEFT JOIN $wpdb->users AS uf ON uf.ID = cm.from_user " .
    "LEFT JOIN $wpdb->users AS ut ON ut.ID = cm.to_user " .
    "WHERE cm.id = %d";
  $row = $wpdb->get_row($wpdb->prepare($query, array($messageid)), ARRAY_A);
  if (empty($row)) {
    $out['errmsg'] = "Cannot delete, there is no message with ID $messageid.";
    return;
  }
  if ($row['from_login'] == $admin_user_login && $row['to_del'] > 0 ||
      $row['to_login'] == $admin_user_login && $row['from_del'] > 0) {
    // Other party has flagged it for deletion so purge the message, its
    // children and all related attachments.
    $wpdb->query($wpdb->prepare("DELETE FROM a " .
      "USING {$wpdb->prefix}cartpauj_pm_messages AS m " .
      "JOIN {$wpdb->prefix}cartpauj_pm_attachments AS a " .
      "WHERE (m.id = %d OR m.parent_id = %d) AND a.message_id = m.id",
      $messageid, $messageid));
    $wpdb->query($wpdb->prepare("DELETE FROM " .
      "{$wpdb->prefix}cartpauj_pm_messages WHERE id = %d OR parent_id = %d",
      $messageid, $messageid));
  }
  else if ($row['from_login'] == $admin_user_login) {
    // We are the sender, recipient has not yet deleted.
    $wpdb->query($wpdb->prepare("UPDATE {$wpdb->prefix}cartpauj_pm_messages " .
      "SET from_del = 1 WHERE id = %d", $messageid));
  }
  else if ($row['to_login'] == $admin_user_login) {
    // We are the recipient, sender has not yet deleted.
    $wpdb->query($wpdb->prepare("UPDATE {$wpdb->prefix}cartpauj_pm_messages " .
      "SET to_del = 1 WHERE id = %d", $messageid));
  }
  else {
    // This should not happen.
    $out['errmsg'] = "Delete refused because '$admin_user_login' is not the " .
      "sender or recipient of message $messageid.";
  }
}

// Logic to process the "putmessage" action.
// Sends a message to the designated user with an optional attachment.
//
function action_putmessage(&$args) {
  global $wpdb, $out, $admin_user_login;
  $sender = convertToID($admin_user_login);
  if (!$sender) {
    $out['errmsg'] = "No such sender '$admin_user_login'";
    return;
  }
  $recipient = convertToID($args['user']);
  if (!$recipient) {
    $out['errmsg'] = "No such recipient '{$args['user']}'";
    return;
  }
  $tmp = $wpdb->insert("{$wpdb->prefix}cartpauj_pm_messages", array(
    'from_user'        => $sender,
    'to_user'          => $recipient,
    'message_title'    => $args['title'],
    'message_contents' => $args['message'],
    'last_sender'      => $sender,
    'date'             => current_time('mysql', 1),
    'last_date'        => current_time('mysql', 1),
  ), array('%d', '%d', '%s', '%s', '%d', '%s', '%s'));
  if ($tmp === false) {
    $out['errmsg'] = "Message insert failed";
    return;
  }
  if (!empty($args['contents'])) {
    $message_id = $wpdb->insert_id;
    $tmp = $wpdb->insert("{$wpdb->prefix}cartpauj_pm_attachments", array(
      'message_id' => $message_id,
      'filename'   => $args['filename'],
      'mimetype'   => $args['mimetype'],
      'contents'   => base64_decode($args['contents']),
    ), array('%d', '%s', '%s', '%s'));
    if ($tmp === false) {
      $out['errmsg'] = "Attachment insert failed";
    }
  }
}
