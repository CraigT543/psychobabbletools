# psychobabbletools
I am focusing here on modifing Rod Roark's patient portal to work with CFDB.  CFDB can run a variety of Forms applications including CF7 (free), Gravity Forms, Ninja Forms and others.  

In order for this modification to work, the CFDB plugin needs to be added to Wordpress.  The CFDB table needs to be modified to have an ID field.  In phpMyAdmin SQL tab:

ALTER TABLE wp_cf7dbplugin_submits ADD ID INT NOT NULL AUTO_INCREMENT FIRST, ADD PRIMARY KEY (ID);

You will also need to be running a forms application like CF7 or Ninja Forms etc.

I have modified the folloing of Rod Roark's files:

/openemr/interface/cmsportal/list_requests.php

/openemr/interface/cmsportal/portal.inc.php

/openemr/interface/forms/LBF/new.php


