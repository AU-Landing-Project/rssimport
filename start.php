<?php

namespace AU\RSSImport;

const PLUGIN_ID = 'rssimport';
const PLUGIN_VERSION = 20151008;

require_once __DIR__ . '/lib/functions.php';
require_once __DIR__ . '/lib/hooks.php';
require_once __DIR__ . '/lib/events.php';
require_once __DIR__ . '/vendor/autoload.php';

elgg_register_event_handler('init', 'system', __NAMESPACE__ . '\\init');

// our init function
function init() {

	// Extend system CSS with our own styles
	elgg_extend_view('css/elgg', 'rssimport/css');

	if (!RSSImport::canUse()) {
		return;
	}

	//register our actions
	elgg_register_action("rssimport/edit", __DIR__ . "/actions/edit.php");
	elgg_register_action("rssimport/delete", __DIR__ . "/actions/delete.php");
	elgg_register_action("rssimport/import", __DIR__ . "/actions/import.php");
	elgg_register_action("rssimport/blacklist", __DIR__ . "/actions/blacklist.php");
	elgg_register_action("rssimport/undoimport", __DIR__ . "/actions/undoimport.php");

	// register page handler
	elgg_register_page_handler('rssimport', __NAMESPACE__ . '\\rssimport_page_handler');

	// register our hooks
	elgg_register_plugin_hook_handler('cron', 'all', __NAMESPACE__ . '\\cron');
	elgg_register_plugin_hook_handler('permissions_check', 'all', __NAMESPACE__ . '\\permissions_check');
	elgg_register_plugin_hook_handler('entity:url', 'object', __NAMESPACE__ . '\\rssimport_url');

//@todo - how to stop notifications in 1.9?
//	elgg_register_plugin_hook_handler('object:notifications', 'all', __NAMESPACE__ . 'rssimport_prevent_notification', 1000);


	// add group configurations
	$types = array('blog', 'bookmarks', 'pages');
	foreach ($types as $type) {
		if (elgg_is_active_plugin($type)) {
			add_group_tool_option('rssimport_' . $type, elgg_echo('rssimport:enable' . $type), true);
		}
	}

	elgg_register_event_handler('pagesetup', 'system', __NAMESPACE__ . '\\pagesetup');
	elgg_register_event_handler('upgrade', 'system', __NAMESPACE__ . '\\upgrades');
}

/**
 * page structure for imports <url>/rssimport/<container_guid>/<context>/<rssimport_guid>
 * history: <url>/rssimport/<container_guid>/<context>/<rssimport_guid>/history
 * 
 * @param type $page
 * @return boolean
 */
function rssimport_page_handler($page) {
	elgg_gatekeeper();
	
	if ($page[3] == 'history') {
		elgg_set_page_owner_guid(elgg_get_logged_in_user_guid());
		
		$content = elgg_view('resources/rssimport/history', array(
			'container_guid' => $page[0],
			'import_into' => $page[1],
			'guid' => $page[2]
		));
	}
	else {
		$content = elgg_view('resources/rssimport/import', array(
			'container_guid' => $page[0],
			'import_into' => $page[1],
			'guid' => $page[2]
		));
	}
	
	if ($content) {
		echo $content;
		return true;
	}
	
	return false;

	if (is_numeric($page[0])) {
		$container = get_entity($page[0]);
		if (!$container) {
			return FALSE;
		}
		elgg_set_page_owner_guid(elgg_get_logged_in_user_guid());

		// set up breadcrumbs
		if (elgg_instanceof($container, 'user')) {
			$urlsuffix = 'owner/' . $container->username;
			$name = $container->username;
		} elseif (elgg_instanceof($container, 'group')) {
			RSSImport::groupGatekeeper($container, $page[1]);
			$urlsuffix = 'group/' . $container->guid . '/all';
			$name = $container->name;
		}
		$url = elgg_get_site_url() . "{$page[1]}/{$urlsuffix}";

		// push original context
		elgg_push_breadcrumb(elgg_echo($page[1]), $url);

		// push import
		elgg_push_breadcrumb(elgg_echo('rssimport:import'), "rssimport/{$page[0]}/{$page[1]}");


		// we have an rssimport id, set breadcrumbs and page owner
		if ($page[2]) {
			$url = '';
			if (!$rssimport = get_entity($page[2])) {
				return FALSE;
			}
			$name = $rssimport->title;


			if ($page[3]) {
				$url = elgg_get_site_url() . "rssimport/{$page[0]}/{$page[1]}/{$page[2]}";
				elgg_push_breadcrumb($name, $url);
				elgg_push_breadcrumb(elgg_echo('rssimport:history'));

				if (!$rssimport->canEdit()) {
					return FALSE;
				}

				// we're checking history
				set_input('rssimport_guid', $page[2]);
				elgg_set_context('rssimport_history');
				if (!include dirname(__FILE__) . '/pages/history.php') {
					return FALSE;
				}

				return TRUE;
			} else {
				elgg_push_breadcrumb($name, $url);
			}
		}

		// import view or form
		set_input('container_guid', $page[0]);
		set_input('import_into', $page[1]);
		set_input('rssimport_guid', $page[2]);
		if (!include dirname(__FILE__) . '/pages/rssimport.php') {
			return FALSE;
		}

		return TRUE;
	}

	return FALSE;
}
