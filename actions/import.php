<?php

namespace AU\RSSImport;

// get our form inputs
$feedid = get_input('feedid');
$rssimport = get_entity($feedid);
$itemidstring = get_input('rssimportImport');
$items = explode(',', $itemidstring);


//sanity checking
if (!($rssimport instanceof RSSImport)) {
	register_error(elgg_echo('rssimport:invalid:id'));
	forward(REFERRER);
}

if (empty($itemidstring)) {
	register_error(elgg_echo('rssimport:none:selected'));
	forward(REFERRER);
}

if (!$rssimport->isContentImportable()) {
	register_error(elgg_echo('rssimport:invalid:content:type', array(elgg_echo($rssimport->import_into))));
	forward(REFERRER);
}

// get our feed
$feed = $rssimport->getFeed();

$history = array();
elgg_push_context('rssimport_cron');
//iterate through and import anything with a matching ID
foreach ($feed->get_items() as $item) {
	if (in_array($item->get_id(true), $items)) {
		if (!$rssimport->isAlreadyImported($item)) {
			// not a duplicate, selected for import - let's do it
			$history[] = $rssimport->importItem($item);
		}
	}
}

elgg_pop_context();
$rssimport->addToHistory($history);

system_message(elgg_echo('rssimport:imported'));
forward(REFERRER);
