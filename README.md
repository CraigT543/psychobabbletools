# psychobabbletools
I am focusing here on modifing Rod Roark's patient portal to work with CFDB.  CFDB can run a variety of Forms applications including CF7 (free), Gravity Forms, Ninja Forms and others.  I have also made the OpenEMR CMS Portal list_requests.php window automaticly switch to the patient associated with the form being input.

In order for this modification to work, the CFDB plugin needs to be added to Wordpress.  The CFDB table needs to be modified to have an ID field.  In phpMyAdmin SQL tab:

    ALTER TABLE wp_cf7dbplugin_submits ADD ID INT NOT NULL AUTO_INCREMENT FIRST, ADD PRIMARY KEY (ID);

You will also need to be running a forms application like CF7 or Ninja Forms etc.

I have modified the following of Rod Roark's files:

    /openemr/interface/cmsportal/list_requests.php

    /openemr/interface/cmsportal/portal.inc.php

    /openemr/interface/forms/LBF/new.php

    /wordpress/wp-content/plugins/sunset-patient-portal/webserve.php

I have also included an example of a Demographics form done in CF7.  All of Rod's Ninja Form templates also work.

All appears to be working. There is likely a better method than for handling the SQL statements than adding the ID field to the DFDB table. This does not cause any problems with DFDB in it's current itteration.  The author tells me that may change in the future.  So a better solution would be helpful.  For now this is working for me.

