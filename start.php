<?php

include_once 'lib/functions.php';


// our init function
function rssimport_init() {

	// Extend system CSS with our own styles
  elgg_extend_view('css/elgg', 'rssimport/css');
  
  // extend js with our own
  elgg_extend_view('js/elgg', 'rssimport/js');
	
  //register our actions
  elgg_register_action("rssimport/add", dirname(__FILE__) . "/actions/add.php");
	elgg_register_action("rssimport/delete", dirname(__FILE__) . "/actions/delete.php");
	elgg_register_action("rssimport/update", dirname(__FILE__) . "/actions/update.php");
  elgg_register_action("rssimport/import", dirname(__FILE__) . "/actions/import.php");
  elgg_register_action("rssimport/blacklist", dirname(__FILE__) . "/actions/blacklist.php");
  elgg_register_action("rssimport/undoimport", dirname(__FILE__) . "/actions/undoimport.php");

	// register page handler
	elgg_register_page_handler('rssimport','rssimport_page_handler');
	
	// register our hooks
  elgg_register_plugin_hook_handler('cron', 'all', 'rssimport_cron');
	elgg_register_plugin_hook_handler('permissions_check', 'all', 'rssimport_permissions_check');
}


// page structure for imports <url>/rssimport/<container_guid>/<context>/<rssimport_guid>
// history: <url>/pg/rssimport/history/<rssimport_guid>
function rssimport_page_handler($page){
	
	if(is_numeric($page[0])){
	
		//set import_into based on context
		//sometimes context is plural, make it match the subtype in the database
		if($page[1] == 'blog'){ $import_into = "blog"; }
		if($page[1] == 'blogs'){ $import_into = "blog"; }
		if($page[1] == 'bookmark'){ $import_into = "bookmarks"; }
		if($page[1] == 'bookmarks'){ $import_into = "bookmarks"; }
		if($page[1] == 'pages'){ $import_into = "page"; }
		if($page[1] == 'page'){ $import_into = "page"; }
		//first page of "pages" has context of search
		if($page[1] == "search"){ $import_into = "page"; }
	
		set_input('container_guid', $page[0]);
		set_input('import_into', $import_into);
		set_input('rssimport_guid', $page[2]);
		if(!include dirname(__FILE__) . '/pages/rssimport.php'){
			return FALSE;
		}		
	}
	else{		//not numeric first option, so must be another page
		if($page[0] == "history" && is_numeric($page[1])){
			set_input('rssimport_guid', $page[1]);
			set_context('rssimport_history');
			if(!include dirname(__FILE__) . '/pages/history.php'){
				return FALSE;
			}
		}
	}
  
  return TRUE;
}

// add links to submenus
function rssimport_pagesetup() {

	// Get the page owner entity
	$page_owner = elgg_get_page_owner_entity();
	$context = elgg_get_context();
	$rssimport_guid = get_input('rssimport_guid');
	$rssimport = get_entity($rssimport_guid);
	$createlink = false;

	// Submenu items for group pages, if logged in and context is one of our imports
	if(elgg_is_logged_in() && in_array($context, array('blog', 'pages', 'bookmarks'))){
		// if we're on a group page, check that the user is a member of the group
		if(elgg_instanceof($page_owner, 'group', '', 'ElggGroup')){
			if($page_owner->isMember(elgg_get_logged_in_user_entity())) {
				$createlink = true;
			}
		}
		
		// if we are the owner
		if($page_owner->guid == elgg_get_logged_in_user_guid()){
			$createlink = true;
		}
	}
	
	if($createlink){
    $item = new ElggMenuItem('rssimport', elgg_echo('rssimport:import'), 'rssimport/' . $page_owner->guid . '/' . $context);
		elgg_register_menu_item('page', $item);
	}
	
	// create "back" link on import page - go back to blogs/pages/etc.
	if(elgg_is_logged_in() && $context == "rssimport"){
		//have to parse URL to figure out what page type and owner to send them back to
		//this function does it, and returns an array('link_text','url')
		$linkparts = rssimport_get_return_url();

    $item = new ElggMenuItem('rssimport_back', $linkparts[0], $linkparts[1]);
		elgg_register_menu_item('page', $item);
	}
	
	// create link to "View History" on import page
	if(elgg_is_logged_in() && $context == "rssimport" && !empty($rssimport_guid)){
    $item = new ElggMenuItem('rssimport_history', elgg_echo('rssimport:view:history'), 'rssimport/history' . $rssimport_guid);
		elgg_register_menu_item('page', $item);
	}
	

	// create link to "View Import" on history page
	if(elgg_is_logged_in() && $context == "rssimport_history" && !empty($rssimport_guid)){
    $item = new ElggMenuItem('rssimport_view', elgg_echo('rssimport:view:import'), 'rssimport/' . $rssimport->containerid . '/' . $rssimport->import_into . '/' . $rssimport_guid);
		elgg_register_menu_item('page', $item);
	}
}

// register for events
elgg_register_event_handler('init','system','rssimport_init');
elgg_register_event_handler('pagesetup','system','rssimport_pagesetup');
