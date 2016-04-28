# psychobabbletools
I am focusing here on modifing Rod Roark's patient portal to work with CFDB.  CFDB can run a variety of Forms applications including CF7 (free), Gravity Forms, Ninja Forms and many others.  I have also made the OpenEMR CMS Portal list_requests.php window automatically switch to the patient associated with the form being input. As Rod's plug in currently is working you must be sure to switch to the patient you are importing a form to.  This is not an issue when adding a new patient in Rod's current system but I do a lot of assessment forms as I work with multiple clients that need to be imported into their respective files so I need to be sure I do not mess up on this.  Also, Ninja Forms basic is free but it is very limited.  To get all the features I needed I would need to spend a ridiculous amount of money to get the full functionality required for my practice.  I am a psychotherapist and operate on a very slim budget and I have no staff so costs matter.  You get a lot more bang for your buck from Gravity forms.  And, if you are ok with a little coding CF7 is free and does it all.  So, I am doing this to have more flexibility for less money.

In order for this modification to work, the CFDB plugin needs to be added to WordPress.  The CFDB table needs to be modified to have an ID field. In phpMyAdmin SQL tab:

    ALTER TABLE wp_cf7dbplugin_submits ADD ID INT NOT NULL AUTO_INCREMENT FIRST, ADD PRIMARY KEY (ID);
    
    
You will also need to make the following modifications to the forms at /openemr/interface/cmsportal/:

    upload_form.php
    Line 46 change intval to floatval
    
    patient_select.php
    line 29 change intval to floatval
    
    patient_form.php
    line 31 change intval to floatval
    
    lbf_form.php
    line 29 change intval to floatval
    
    issue_form.php
    line 110 change intval to floatval
    
    insurance_form.php
    line 163 change intval to floatval
    
    history_form.php
    line 31 change intval to floatval

I have included the following modifications of Rod Roark's files:

    /openemr/interface/cmsportal/list_requests.php

    /openemr/interface/cmsportal/portal.inc.php

    /openemr/interface/forms/LBF/new.php

    /wordpress/wp-content/plugins/sunset-patient-portal/webserve.php

I have also included an example of a Demographics form done in CF7.  All of Rod's Ninja Form templates also work.

You will also need to be running a forms application like CF7 or Ninja Forms etc.

All appears to be working well. There is likely a better method than for handling the SQL statements than adding the ID field to the DFDB table. This does not cause any problems with DFDB in its current iteration.  The author tells me that may change in the future.  He says he has thought of adding the same but is not sure how he will implement it and says that it may be safer to rename the field something other than ID.  However, changing ID would mean that I would have to change other parts of Rod’s code I do not want to do.  So a better solution would be helpful.  For now this is working for me.  One possible way I have thought of but do not know how I would implement is to  make the combination of the submit_time field and the field_order field be the uid AS ID in the SQL statement.  That would might work but I do not have time now to rewrite that and test it.  It is a project for another day.  
