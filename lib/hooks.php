<?php

namespace AU\RSSImport;

/**
 * Trigger imports
 * 	use $params['period'] to find out which we are on
 * 	eg; $params['period'] = 'hourly'
 */
function cron($hook, $entity_type, $returnvalue, $params) {
	// change context for permissions
	elgg_push_context('rssimport_cron');
	elgg_set_ignore_access(true);

	// get array of imports we need to look at
	$options = array(
		'types' => array('object'),
		'subtypes' => array('rssimport'),
		'limit' => 0,
		'metadata_name_value_pairs' => array(
			'name' => 'cron',
			'value' => $params['period']
		)
	);

	// using ElggBatch because there may be many, many groups in teh installation
	// try to avoid oom errors
	//@todo - don't use callback
	$batch = new \ElggBatch('elgg_get_entities_from_metadata', $options);

	foreach ($batch as $rssimport) {
		if (!$rssimport->isContentImportable()) {
			continue;
		}

		if (!RSSImport::groupGatekeeper($rssimport->getContainerEntity(), $rssimport->import_into, false)) {
			continue;
		}

		//get the feed
		$feed = $rssimport->getFeed();

		$history = array();
		foreach ($feed->get_items(0, 0) as $item) {
			if (!$rssimport->isAlreadyImported($item) && !$rssimport->isBlacklisted($item)) {
				$history[] = $rssimport->importItem($item);
			}
		}

		$rssimport->addToHistory($history);
	}

	elgg_set_ignore_access(false);
	elgg_pop_context();
}

/**
 * allows write permissions when we are adding metadata to an object
 *
 * @param type $hook
 * @param type $type
 * @param type $return
 * @param type $params
 * @return boolean|null/
 * 
 */
function permissions_check($hook, $type, $return, $params) {
	if (elgg_get_context() == 'rssimport_cron') {
		return true;
	}

	if ($params['entity'] instanceof RSSImport) {
		return $params['entity']->getContainerEntity()->canEdit();
	}

	return $return;
}

/**
 * get url for an import
 * 
 * @param type $rssimport
 * @return type
 */
function rssimport_url($hook, $type, $return, $params) {
	if (!($params['entity'] instanceof RSSImport)) {
		return $return;
	}
	$rssimport = $params['entity'];
	$container = $rssimport->getContainerEntity();

	return elgg_get_site_url() . "rssimport/{$container->guid}/{$rssimport->import_into}/{$rssimport->guid}";
}
