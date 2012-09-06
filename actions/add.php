<?php

// save form values in case of failure
elgg_make_sticky_form('rssimport');

// this action saves a new import feed

$feedtitle = get_input('feedtitle');
$feedurl = get_input('feedurl');
$copyright = get_input('copyright');
$cron = get_input('cron');
$defaultaccess = get_input('defaultaccess');
$defaulttags = get_input('defaulttags');
$import_into = get_input('import_into');
$containerid = get_input('containerid');
$guid = get_input('guid', false);


//sanity checking
if (empty($feedtitle) || empty($feedurl)) {
	register_error(elgg_echo('rssimport:empty:field'));
	forward(REFERRER);
}

if (!$copyright) {
	register_error(elgg_echo('rssimport:empty:copyright'));
	forward(REFERRER);
}



//create/update our object
$action_type = 'updated';
if (!($guid && $rssimport = get_entity($guid))) {
  $action_type = 'created';
  $rssimport = new ElggObject();
  $rssimport->subtype = 'rssimport';
}

$rssimport->title = $feedtitle;
$rssimport->owner_guid = elgg_get_logged_in_user_guid();
$rssimport->description = $feedurl;
$rssimport->access_id = ACCESS_LOGGED_IN;

if (!$rssimport->save()) {
  register_error('rssimport:save:error');
  forward(REFERRER);
}

// saved ok, we don't need to keep form values
elgg_clear_sticky_form('rssimport');

//add our metadata
$rssimport->copyright = true;
$rssimport->cron = $cron;
$rssimport->defaultaccess = $defaultaccess;
$rssimport->defaulttags = $defaulttags;
$rssimport->import_into = $import_into;
// not a typo - this stores the guid of the container - either the user or a group
$rssimport->rssimport_containerid = $containerid;

unset($_SESSION['rssimport']);

//set message and send back
system_message(elgg_echo('rssimport:import:' . $action_type));
forward($rssimport->getURL());