# psychobabbletools
First, thank you to Rod Roark for offering us his code.  It has been so useful.  And, he has been very gracious in giving me pointers regarding the code to make my modifications.  As much as possible I have tried to use his style in making my modifications and I have tried to carefully comment out where I am adding my parts.

I am focusing here on modifing Rod Roark's patient portal to work with CFDB. The CFDB Plugin is found at https://cfdbplugin.com/. CFDB can run a variety of Forms applications including CF7 (free), Gravity Forms, Ninja Forms and many others.  

I have also modified the webserve interface to sync with Frontend PM Pro.  Frontend PM Pro is found at https://www.shamimsplugins.com/products/front-end-pm-pro/. Several addjustments are needed to set up Frontend PM Pro for HIPAA compliance.  I have explained how to set it up here: https://github.com/CraigT543/Sunset-Patient-Portal-with-FrontendPM.

I have also made the OpenEMR CMS Portal list_requests.php window automatically switch to the patient associated with the form being input. As Rod's plug in currently is working you must be sure to switch to the patient you are importing a form to.  This is not an issue when adding a new patient in Rod's current system but I do a lot of assessment forms over time as I work with multiple clients that need to be imported into their respective files so I need to be sure I do not mess up on this.  Also, Ninja Forms basic is free but it is very limited.  To get all the features I needed I would need to spend a ridiculous amount of money to get the full functionality required for my practice.  I am a psychotherapist and operate on a very slim budget and I have no staff so costs matter.  You get a lot more bang for your buck from Gravity forms.  And, if you are ok with a little coding CF7 is free and does it all.  So, I am doing this to have more flexibility for less money.

In order for this modification to work, the CFDB plugin needs to be added to WordPress.  The CFDB table needs to be modified to have an ID field. In phpMyAdmin SQL tab:

    ALTER TABLE wp_cf7dbplugin_submits ADD ID INT NOT NULL AUTO_INCREMENT FIRST, ADD PRIMARY KEY (ID);
    
    
You will also need to make the following modifications to the forms at /openemr/interface/cmsportal/:

 Replace all postid 

	intval($_REQUEST['postid'])

 with 

	floatval($_REQUEST['postid'])

history_form.php line 20

insurance_form.php line 154

issue_form.php line 99

lbf_form.php line 20

patient_form.php line 20

patient_select.php line 30

upload_form.php line 50

You will also need to modify /openemr/interface/forms/LBF/new.php and change lines 82 and 83 to from intval to floatval as such:

    $formid = isset($_GET['id']) ? floatval($_GET['id']) : 0;
    $portalid = isset($_GET['portalid']) ? floatval($_GET['portalid']) : 0;


I have included the following modifications of Rod Roark's files:

    /openemr/interface/cmsportal/list_requests.php

    /openemr/interface/cmsportal/portal.inc.php

    /wordpress/wp-content/plugins/sunset-patient-portal/webserve.php

I have also included an example of a Demographics form done in CF7.  

CF7 has made some chages in how it works and it breaks some functioning with CFCB unless the folloing is added to every form:

    [hidden userlogin default:user_login]
	
And the following function needs to be added to functions.php in WordPress:

	//Add Submitted Login to CF7 forms where [hidden userlogin default:user_login]
	function filter_add_cf7_submitted_login($formData) {
			$submission = WPCF7_Submission::get_instance();
			if ( $submission ) {
				$posted_data = $submission->get_posted_data();
				$formData->user = $posted_data['userlogin']; // string user name if submitter was logged in. May be null
			}
		return $formData;
	}
	add_filter('cfdb_form_data', 'filter_add_cf7_submitted_login');		


All of Rod's Ninja Form templates also work.

You will also need to be running a forms application like CF7, Gravity, or Ninja Forms etc.

All appears to be working well. There is likely a better method than for handling the SQL statements than adding the ID field to the DFDB table. This does not cause any problems with DFDB in its current iteration and the past few upgrades.  The author tells me that may change in the future.  He says he has thought of adding the same but is not sure how he will implement it and says that it may be safer to rename the field something other than ID.  However, changing ID would mean that I would have to change other parts of Rod’s code I do not want to do.  So a better solution would be helpful.  For now this is working for me.  One possible way I have thought of but do not know how I would implement is to  make the combination of the `submit_time` field and the `field_order` field be the uid AS ID in the SQL statement.  That would might work but I do not have time now to figure out how to do it, rewrite that, and test it.  It is a project for another day.  
