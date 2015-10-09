<?php

namespace AU\RSSImport;

//
//	this function parses the URL to figure out what context and owner it belongs to, so we can generate
// 	a return URL 
//
//	URL is in the form of <baseurl>/rssimport/<container_guid>/<context> where context is "blog", "bookmarks", or "page"
//	Generate a url of <baseurl>/<context>/owner/<owner_name> for personal stuff
//	<baseurl>/<context>/group/<guid>/all for group stuff
function rssimport_get_return_url() {

	$base_path = parse_url(elgg_get_site_url(), PHP_URL_PATH);
	$current_path = parse_url(current_page_url(), PHP_URL_PATH);
	if ($base_path != '/') {
		$current_path = str_replace($base_path, '', $current_path);
	} else {
		$current_path = substr($current_path, 1);
	}
	$parts = explode('/', $current_path);

	// get our owner entity
	$entity = get_entity($parts[1]);

	if ($entity instanceof \ElggGroup) {
		$owner_type = 'group';
		$username = $entity->guid . '/all';
	} elseif ($entity instanceof \ElggUser) {
		$owner_type = 'owner';
		$username = $entity->username;
	}

	$backurl = elgg_get_site_url() . $parts[2] . '/' . $owner_type . '/' . $username;

	//return array of link text and url
	$linktext = elgg_echo('rssimport:back:to:' . $parts[2]);
	return array($linktext, $backurl);
}


// prevent notifications from being sent during an import
//@todo - how to stop notifications in 1.9?
function rssimport_prevent_notification($hook, $type, $return, $params) {
	if (elgg_get_context() == 'rssimport_cron') {
		return TRUE;
	}
}



// Returns a list of links from an importable item
function rssimport_get_source($item) {
	$return = '';
	$items_sources = $item->get_item_tags('', 'source');
	if ($items_sources) {
		foreach ($items_sources as $source) {
			$return[] .= '<a href="' . $source['attribs']['']['url'] . '">' . $source['data'] . '</a>';
		}
		return implode(', ', $return);
	}
	return false;
}

// Convenient function to add the source if it is defined
function rssimport_add_source($item) {
	$item_source = rssimport_get_source($item);
	if ($item_source) {
		return '<p class="rss-source">' . elgg_echo('rssimport:source') . '&nbsp;: ' . $item_source . '<p>';
	}
	return '';
}
