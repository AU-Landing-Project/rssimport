<?php

//
//	this function returns an array of all imports for the logged in user
//
function get_user_rssimports($user){

	if (!$user) {
    $user = elgg_get_logged_in_user_entity();
    if (!$user) {
      return false;
    }
	}

	$options = array();
	$options['owner_guids'] = $user->guid;
	$options['type_subtype_pairs'] = array('object' => 'rssimport');
	$options['limit'] = 0;

	return elgg_get_entities($options);
}

//
//	This function adds a list of item ids (passed as array $items)
//	and adds them to the blacklist for the given import
//	These items won't be imported on cron, or visible by default
//
function rssimport_add_to_blacklist($items, $rssimport){
	$blacklist = $rssimport->blacklist;
	
	// turn list into an array
	$blackarray = explode(',', $blacklist);
	
	// add new items to the existing array
	$itemcount = count($items);
	for ($i=0; $i<$itemcount; $i++) {
		$blackarray[] = $items[$i];
	}
	
	// make sure we don't have duplicate entries
	$blackarray = array_unique($blackarray);
	$blackarray = array_values($blackarray);
	
	// reform list from array
	$blacklist = implode(',', $blackarray);
	
	$rssimport->blacklist = $blacklist;
}


//
//	This function annotates an rssimport object with the most recent import
//	stores a string of guids that were created
//
function rssimport_add_to_history($array, $rssimport){
	//create comma delimited string of new guids
	if (is_array($array)) {
		if (count($array) > 0) {
			$history = implode(',', $array);
			$rssimport->annotate('rssimport_history', $history, ACCESS_PRIVATE, $rssimport->owner_guid);		
		}
	}	
}

//
//	Checks if an item has been imported previously
//	was a unique function, now a wrapper for rssimport_check_for_duplicates()
//
function rssimport_already_imported($item, $rssimport){
	return rssimport_check_for_duplicates($item, $rssimport);
}


//
//	this function saves a blog post from an rss item
//
function rssimport_blog_import($item, $rssimport){

	$blog = new ElggBlog();
	$blog->subtype = "blog";
  $blog->excerpt = elgg_get_excerpt($item->get_content());
	$blog->owner_guid = $rssimport->owner_guid;
	$blog->container_guid = $rssimport->containerid;
	$blog->access_id = $rssimport->defaultaccess;
	$blog->title = $item->get_title();
				
	//	build content of blog post
	$author = $item->get_author();
	$blogbody = $item->get_content();
	$blogbody .= "<br><br>";
	$blogbody .= "<hr><br>";
	$blogbody .= elgg_echo('rssimport:original') . ": <a href=\"" . $item->get_permalink() . "\">" . $item->get_permalink() . "</a> <br>";
	
	// some feed items don't have an author to get, check first 
	if(is_object($author)){
		$blogbody .= elgg_echo('rssimport:by') . ": " . $author->get_name() . "<br>";
	}
	
	$blogbody .= elgg_echo('rssimport:posted') . ": " . $item->get_date('F j, Y, g:i a');
	$blog->description = $blogbody;
	
	//add feed tags to default tags and remove duplicates
	$tagarray = string_to_tag_array($rssimport->defaulttags);
	foreach ($item->get_categories() as $category)
		{
			$tagarray[] = $category->get_label();
		}
	$tagarray = array_unique($tagarray);
	$tagarray = array_values($tagarray);
	
		// Now let's add tags. We can pass an array directly to the object property! Easy.
	if (is_array($tagarray)) {
		$blog->tags = $tagarray;
	}
				
	//whether the user wants to allow comments or not on the blog post
	// do we want to make this selectable?
	$blog->comments_on = true;
		// Now save the object
	$blog->save();
				
	//add metadata
	$token = rssimport_create_comparison_token($item);
	$blog->rssimport_token = $token;
	$blog->rssimport_id = $item->get_id();
	$blog->rssimport_permalink = $item->get_permalink();
  $blog->status = 'published';
  
  $blog->time_created = strtotime($item->get_date()) ? strtotime($item->get_date()) : time();
  $blog->save(); // have to save again to set the new time_created
	
	return $blog->guid;	
}

// imports a feed item into a bookmark
function rssimport_bookmarks_import($item, $rssimport){
		// flag to prevent saving if there are issues
		$error = false;
		
		$bookmark = new ElggObject;
		$bookmark->subtype = "bookmarks";
		$bookmark->owner_guid = $rssimport->owner_guid;
		$bookmark->container_guid = $rssimport->containerid;
		$bookmark->title = $item->get_title();
		$bookmark->address = $item->get_permalink();
		$bookmark->description = $item->get_description();
		$bookmark->access_id = $rssimport->defaultaccess;
		
		// merge default tags with any from the feed
		$tagarray = string_to_tag_array($rssimport->defaulttags);
		foreach ($item->get_categories() as $category)
		{
			$tagarray[] = $category->get_label();
		}
		$tagarray = array_unique($tagarray);
		$tagarray = array_values($tagarray);
		$bookmark->tags = $tagarray;
		
		//if no errors save it
		if(!$error){
			$bookmark->save();
			
			//add metadata
			$token = rssimport_create_comparison_token($item);
			$bookmark->rssimport_token = $token;
			$bookmark->rssimport_id = $item->get_id();
			$bookmark->rssimport_permalink = $item->get_permalink();
      $bookmark->time_created = strtotime($item->get_date()) ? strtotime($item->get_date()) : time();
      $bookmark->save(); // save again to set time_created
			
			return $bookmark->guid;
		}
}


/**
 * 	Checks if a blog post exists for a user that matches a feed item
 * 	Return true if there is a match
 */
function rssimport_check_for_duplicates($item, $rssimport){
	
	// look for id first - less resource intensive
	// this will filter out anything that has already been imported
	$options = array();
	$options['container_guids'] = $rssimport->containerid;
	$options['type_subtype_pairs'] = array('object' => $rssimport->import_into);
	$options['metadata_name_value_pairs'] = array('name' => 'rssimport_id', 'value' => $item->get_id());
	$blogs = elgg_get_entities_from_metadata($options);
	
	if (!empty($blogs)) {
		return true;
	}
	
	// look for permalink
	// this will filter out anything that has already been imported
	$options = array();
	$options['container_guids'] = $rssimport->containerid;
	$options['type_subtype_pairs'] = array('object' => $rssimport->import_into);
	$options['metadata_name_value_pairs'] = array('name' => 'rssimport_permalink', 'value' => $item->get_permalink());
	$blogs = elgg_get_entities_from_metadata($options);
	
	if(!empty($blogs)){
		return true;
	}
	
	$token = rssimport_create_comparison_token($item);
	
	//check by token - this will filter out anything that was a repost on the feed
	$options = array();
	$options['container_guids'] = $rssimport->containerid;
	$options['type_subtype_pairs'] = array('object' => $rssimport->import_into);
	$options['metadata_name_value_pairs'] = array('name' => 'rssimport_token', 'value' => $token);
	$blogs = elgg_get_entities_from_metadata($options);
	
	if (!empty($blogs)) {
		return true;
	}
	
	return false;
}


/**
 * 	Creates a hash of various feed item variables for
 * 	easy comparison to feed created blogs
 */
function rssimport_create_comparison_token($item){
	$author = $item->get_author();
	$pretoken = $item->get_title();
	$pretoken .= $item->get_content();
	if(is_object($author)){
		$pretoken .= $author->get_name();
	}
	
	return md5($pretoken);
}


/**
 * Trigger imports
 *	use $params['period'] to find out which we are on
 *	eg; $params['period'] = 'hourly'
 */
function rssimport_cron($hook, $entity_type, $returnvalue, $params){
	// change context for permissions
	$context = elgg_get_context();
	elgg_set_context('rssimport_cron');
	elgg_set_ignore_access(TRUE);
	
	rssimport_include_simplepie();
	$cache_location = rssimport_set_simplepie_cache();
	// get array of imports we need to look at
	$options = array();
	$options['metadata_name_value_pairs'] = array('name' => 'cron', 'value' => $params['period']);
	$rssimport = elgg_get_entities_from_metadata($options);	
	$numimports = count($rssimport);
	
	
	// iterate through our imports
	for ($i=0; $i<$numimports; $i++) {
		if ($rssimport[$i]->getSubtype() == "rssimport") { // make sure we're only dealing with our import objects
		
		//get the feed
		$feed = new SimplePie($rssimport[$i]->description, $cache_location);
		
		$history = array();
		// for each feed, iterate through the items
		foreach ($feed->get_items(0,0) as $item):
			if (!rssimport_check_for_duplicates($item, $rssimport[$i]) && !rssimport_is_blacklisted($item, $rssimport[$i])) {
				// no duplicate entries exist
				// item isn't blacklisted
				// import it
				switch ($rssimport[$i]->import_into) {
					case "blog":
						$history[] = rssimport_blog_import($item, $rssimport[$i]);
						break;
					case "blogs":
						$history[] = rssimport_blog_import($item, $rssimport[$i]);
						break;
					case "page":
						$history[] = rssimport_page_import($item, $rssimport[$i]);
						break;
					case "pages":
						$history[] = rssimport_page_import($item, $rssimport[$i]);
						break;
					case "bookmark":
						$history[] = rssimport_bookmarks_import($item, $rssimport[$i]);
						break;
					case "bookmarks":
						$history[] = rssimport_bookmarks_import($item, $rssimport[$i]);
						break;
					default:	// when in doubt, send to a blog
						$history[] = rssimport_blog_import($item, $rssimport[$i]);
						break;
				}
					
			}
		endforeach;

		rssimport_add_to_history($history, $rssimport[$i]);

		}
	}
	elgg_set_ignore_access(FALSE);
	elgg_set_context($context);
}

//
//	returns an array of groups that a user is a member of
//	and can post content to
//	returns false if there are no groups the user can post to
function rssimport_get_postable_groups($user){
	return $user->getGroups('', 0, 0);
}

//
//	this function parses the URL to figure out what context and owner it belongs to, so we can generate
// 	a return URL 
//
//	URL is in the form of <baseurl>/rssimport/<container_guid>/<context> where context is "blog", "bookmarks", or "page"
//	Generate a url of <baseurl>/<context>/owner/<owner_name> for personal stuff
//	<baseurl>/<context>/group/<guid>/all for group stuff
function rssimport_get_return_url(){
	
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
			
	if ($entity instanceof ElggGroup) {
		$owner_type = 'group';
    $username = $entity->guid . '/all';
	} elseif ($entity instanceof ElggUser) {
    $owner_type = 'owner';
    $username = $entity->username;
  }
	
	$backurl = elgg_get_site_url() . $parts[2] . '/' . $owner_type . '/' . $username;
	
	//return array of link text and url
	$linktext = elgg_echo('rssimport:back:to:' . $parts[2]);
	return array($linktext, $backurl);
}

//
//	this function includes the simplepie class if it doesn't exist
//
function rssimport_include_simplepie(){

	if (!class_exists('SimplePie')) {
		require_once elgg_get_plugins_path() . '/rssimport/lib/simplepie.inc';
	}
}

// returns true if the item has been blacklisted by the current user
function rssimport_is_blacklisted($item, $rssimport){
	$blacklist = $rssimport->blacklist;
	
	//create array from our list
	$blackarray = explode(',', $blacklist);
	
	if(in_array($item->get_id(true), $blackarray)){
		return true;
	}
	
	return false;
}


//
//	removes a single item from an array
//	resets keys
//
function rssimport_removeFromArray($value, $array){
	if (!is_array($array)) { return $array; }
	if (!in_array($value, $array)) { return $array; }
	
	for ($i=0; $i<count($array); $i++) {
		if($value == $array[$i]){
			unset($array[$i]);
			$array = array_values($array);
		}
	}
	
	return $array;
}

// this function removes an item from the blacklist
function rssimport_remove_from_blacklist($items, $rssimport){
	$blacklist = $rssimport->blacklist;
	
	// turn list into an array
	$blackarray = explode(',', $blacklist);
	
	// remove items from existing array
	$itemcount = count($items);
	for ($i=0; $i<$itemcount; $i++) {
		$blackarray = rssimport_removeFromArray($items[$i], $blackarray);
	}
	
	// reform list from array
	$blacklist = implode(',', $blackarray);
	
	$rssimport->blacklist = $blacklist;
}


function rssimport_page_import($item, $rssimport){
	//check if we have a parent page yet
	$options = array();
	$options['type_subtype_pairs'] = array('object' => 'page_top');
	$options['container_guids'] = $rssimport->containerid;
	$options['metadata_name_value_pairs'] = array(array('name' => 'rssimport_feedpage', 'value' => $rssimport->title), array('name' => 'rssimport_url', 'value' => $rssimport->description));
	$testpage = elgg_get_entities_from_metadata($options);
	
	if(!$testpage){
		//create our parent page
		$parent = new ElggObject();
		$parent->subtype = 'page_top';
		$parent->container_guid = $rssimport->containerid;
		$parent->owner_guid = $rssimport->owner_guid;
		$parent->access_id = $rssimport->defaultaccess;
		$parent->parent_guid = 0;
		$parent->write_access_id = $rssimport->defaultaccess;
		$parent->title = $rssimport->title;
		$parent->description = $rssimport->description;
		//set default tags
		$tagarray = string_to_tag_array($rssimport->defaulttags);
		$parent->tags = $tagarray;
		$parent->save();
		
		$parent->annotate('page', $parent->description, $parent->access_id, $parent->owner_guid);
		
		$parent_guid = $parent->guid;
		
		//add our identifying metadata
		$parent->rssimport_feedpage = $rssimport->title;
		$parent->rssimport_url = $rssimport->description;
	}
	else{
		$parent_guid = $testpage[0]->guid;
	}
	
	//initiate our object
	$page = new ElggObject();
	$page->subtype = 'page';
	$page->container_guid = $rssimport->containerid;
	$page->owner_guid = $rssimport->owner_guid;
	$page->access_id = $rssimport->defaultaccess;
	$page->parent_guid = $parent_guid;
	$page->write_access_id = $rssimport->defaultaccess;
	$page->title = $item->get_title();
	
	$author = $item->get_author();
	$pagebody = $item->get_content();
	$pagebody .= "<br><br>";
	$pagebody .= "<hr><br>";
	$pagebody .= elgg_echo('rssimport:original') . ": <a href=\"" . $item->get_permalink() . "\">" . $item->get_permalink() . "</a> <br>";
	if(is_object($author)){
		$pagebody .= elgg_echo('rssimport:by') . ": " . $author->get_name() . "<br>";
	}
	$pagebody .= elgg_echo('rssimport:posted') . ": " . $item->get_date('F j, Y, g:i a');
	
	$page->description = $pagebody;
	
	//set default tags
	$tagarray = string_to_tag_array($rssimport->defaulttags);
	foreach ($item->get_categories() as $category)
		{
			$tagarray[] = $category->get_label();
		}
	$tagarray = array_unique($tagarray);
	$tagarray = array_values($tagarray);

	// Now let's add tags. We can pass an array directly to the object property! Easy.
	if (is_array($tagarray)) {
		$page->tags = $tagarray;
	}
	
	$page->save();
	
	$page->annotate('page', $page->description, $page->access_id, $page->owner_guid);
	
	//add our identifying metadata
	$token = rssimport_create_comparison_token($item);
	$page->rssimport_token = $token;
	$page->rssimport_id = $item->get_id();
	$page->rssimport_permalink = $item->get_permalink();
  $page->time_created = strtotime($item->get_date()) ? strtotime($item->get_date()) : time();
  $page->save(); // save again to set proper time_created
	
	return $page->guid;
}

// allows write permissions when we are adding metadata to an object
function rssimport_permissions_check(){
	if (elgg_get_context() == 'rssimport_cron') {
		return true;
	}
 
	return null;
}


function rssimport_set_simplepie_cache(){

	// 	set cache for simplepie if it doesn't exist
	$cache_location = elgg_get_config('dataroot') . '/simplepie_cache/';
	if (!file_exists($cache_location)) {
		mkdir($cache_location, 0777);
	}
	
	return $cache_location;
}


// prevent notifications from being sent during an import
function rssimport_prevent_notification($hook, $type, $return, $params) {
  if (elgg_get_context() == 'rssimport_cron') {
    return TRUE;
  }
}