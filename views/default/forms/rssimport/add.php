<?php

$rssimport = $vars['entity'];

	// 	user defined feed name textbox
	$value = "";
	if ($rssimport instanceof ElggObject) {		// we're updating, populate with saved info
		$value = $rssimport->title;
	}
	if(!empty($_SESSION['rssimport']['feedtitle'])){ $value = $_SESSION['rssimport']['feedtitle']; }
	$createform = elgg_echo('rssimport:name') . "<br>";
	$createform .= elgg_view('input/text', array('name' => 'feedtitle', 'id' => 'feedName', 'value' => $value)) . "<br><br>";

	// feed url textbox
	$value = "";
	if ($rssimport instanceof ElggObject) {		// we're updating, populate with saved info
		$value = $rssimport->description;
	}
	if(!empty($_SESSION['rssimport']['feedurl'])){ $value = $_SESSION['rssimport']['feedurl']; }
	$createform .= elgg_echo('rssimport:url') . "<br>";
	$createform .= elgg_view('input/text', array('name' => 'feedurl', 'id' => 'feedurl', 'value' => $value)) . "<br><br>";


	$createform .= elgg_view('input/hidden', array('name' => 'containerid', 'value' => $defcontainer_id));
	$createform .= elgg_view('input/hidden', array('name' => 'import_into', 'value' => $import_into));

	// cron pulldown
	$value = "never";
	if ($rssimport instanceof ElggObject) {		// we're updating, populate with saved info
		$value = $rssimport->cron;
	}
	if (!empty($_SESSION['rssimport']['cron'])) { $value = $_SESSION['rssimport']['cron']; }
	$selectopts = array();
	$selectopts['name'] = "cron";
	$selectopts['id'] = "feedcron";
	$selectopts['value'] = $value;
	$selectopts['options_values'] = array('never' => elgg_echo('rssimport:cron:never'), 'hourly' => elgg_echo('rssimport:cron:hourly'), 'daily' => elgg_echo('rssimport:cron:daily'), 'weekly' => elgg_echo('rssimport:cron:weekly'));
	$createform .= elgg_echo('rssimport:cron:description') . " ";
	$createform .= elgg_view('input/dropdown', $selectopts) . "<br>";

	// default access
	if (defined('ACCESS_DEFAULT')) {
		$defaultaccess = ACCESS_DEFAULT;
	}
	else{
		$defaultaccess = 0;
	}
  
	if ($rssimport instanceof ElggObject) {		// we're updating, populate with saved info
		$defaultaccess = $rssimport->defaultaccess;
	}
	if (!empty($_SESSION['rssimport']['defaultaccess'])) { $defaultaccess = $_SESSION['rssimport']['defaultaccess']; }
	$createform .= elgg_echo('rssimport:defaultaccess:description') . " ";
	$createform .= elgg_view('input/access', array('name' => 'defaultaccess', 'value' => $defaultaccess)) . "<br><br>";

	// default tags textbox
	$value = "";
	if ($rssimport instanceof ElggObject) {		// we're updating, populate with saved info
		$value = $rssimport->defaulttags;
	}
	if (!empty($_SESSION['rssimport']['defaulttags'])) { $value = $_SESSION['rssimport']['defaulttags']; }
	$createform .= elgg_echo('rssimport:defaulttags') . "<br>";
	$createform .= elgg_view('input/text', array('name' => 'defaulttags', 'id' => 'defaulttags', 'value' => $value)) . "<br><br>";

	// copyright checkbox
	// not elgg_view checkbox due to limitations (no selected option) - hopefully will be fixed in 1.8
	$checked = "";
	if ($rssimport instanceof ElggObject) {		// we're updating, populate with saved info
		$checked = " checked=\"checked\"";
	}
	if(!empty($_SESSION['rssimport']['copyright'])){ $checked = " checked=\"checked\""; }
	$createform .= "<div class=\"rssimport_copyright_warning\">" . elgg_echo('rssimport:copyright:warning') . "</div>";
	$createform .= "<input type=\"checkbox\" name=\"copyright\" value=\"true\"$checked> " . elgg_echo('rssimport:copyright') . "<br><br>";

	//submit button
	if ($rssimport instanceof ElggObject) {
		$createform .= elgg_view('input/submit', array('value' => elgg_echo('rssimport:update'))) . " ";		
	}
	else{
		$createform .= elgg_view('input/submit', array('value' => elgg_echo('rssimport:create'))) . " ";
	}
	$createform .= elgg_view('input/button', array('value' => elgg_echo('rssimport:cancel'), 'class' => 'formtoggle', 'js' => 'onclick=\'return false\''));

	// create the link to toggle form
	$maincontent .= "<h4 class=\"rssimport_center\"><a href=\"javascript:void(0);\" class=\"formtoggle\">";
	if ($rssimport instanceof ElggObject) {
		$maincontent .= elgg_echo('rssimport:edit:settings');
	}
	else {
		$maincontent .= elgg_echo('rssimport:create:new');	
	}
	$maincontent .= "</a></h4><br>";
	
	//create the div for the form, hidden if we're viewing a feed, visible if we're adding a new feed
	if ($rssimport instanceof ElggObject) {
		$maincontent .= "<div id=\"createrssimportform\">";
	}
	else {
		$maincontent .= "<div id=\"createrssimportform\" style=\"display:block\">";
	}
	
	//different actions depending on whether we're creating new or updating existing
	if ($rssimport instanceof ElggObject) {
		$createform .= elgg_view('input/hidden', array('name' => 'updating_id', 'value' => $rssimport_id));
		$maincontent .= elgg_view('input/form', array('body' => $createform, 'action' => elgg_get_site_url() . "action/rssimport/update"));
	}
	else{
		$maincontent .= elgg_view('input/form', array('body' => $createform, 'action' => elgg_get_site_url() . "action/rssimport/add"));
	}
	
	$maincontent .= "</div>";

/**
 * 	*************************************
 * 		End Import Creation Form
 * 	*************************************
 */