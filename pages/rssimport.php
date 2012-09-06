<?php

//get our defaults
$defcontainer_id = get_input('container_guid');
$import_into = get_input('import_into');

// get our feed object
$rssimport_id = get_input('rssimport_guid');
$rssimport = get_entity($rssimport_id);

// make sure we're the owner if selecting a feed
if ($rssimport instanceof ElggObject && elgg_get_logged_in_user_guid() != $rssimport->owner_guid) {
  register_error(elgg_echo('rssimport:not:owner'));
	forward(REFERRER);
}

//simplepie list of allowed tags
$allow_tags = '<a><p><br><b><i><em><del><pre><strong><ul><ol><li><img><hr>';

// set the title
$title = elgg_echo('rssimport:title');

// get the sidebar
$sidebar = elgg_view('rssimport/sidebar', array('container_id' => $defcontainer_id, 'import_into' => $import_into)); 




/**
 * 	**************************************
 * 		Begin Right Column
 * 	**************************************
 */


	$maincontent = "<div class=\"rssimport_feedwrapper\">";
	$owner = get_entity($defcontainer_id);
	if($owner instanceof ElggUser || $owner instanceof ElggGroup){
		$name = $owner->name;
	}
	
	$maincontent .= "<h2>" . $name . " " . $import_into . " " . elgg_echo("rssimport:import:lc") . "</h2>";


$maincontent .= elgg_view_form('rssimport/add', array(), array('entity' => $rssimport));




	
	
	$maincontent .= "<hr><br>";
	
if ($rssimport instanceof ElggObject) {	
	// Begin showing our feed
  $feed = rssimport_simplepie_feed($rssimport->description);
	$num_posts_in_feed = $feed->get_item_quantity();
	
	/**
	 * 	************************************
	 * 			Begin Actual Item Listing Controls
	 * 	************************************
	 */
	
	// if there are no items, let us know
	if (!$num_posts_in_feed) {
		$maincontent .= elgg_echo('rssimport:no:feed');
	}	
	

	/**
	 * 	*********************************
	 * 		Begin RSS Listing
	 * 	*********************************
	 */
	$maincontent .= "<div class=\"rssimport_rsslisting\">";
	
	
		// The Feed Title
	$maincontent .= "<div class=\"rssimport_blog_title\">";
	$maincontent .= "<h2><a href=\"" . $feed->get_permalink() . "\">" . $feed->get_title() . "</a></h2>";
	$maincontent .= "</div>";
	
	// controls for importing
	$maincontent .= "<div class=\"rssimport_item\" id=\"rssimport_control_box\">";
	$maincontent .= "<div class=\"rssimport_control\">";
	$maincontent .= "<input type=\"checkbox\" name=\"checkalltoggle\" id=\"checkalltoggle\" onclick=\"javascript:rssimportToggleChecked();\">";
	$maincontent .= "<label for=\"checkalltoggle\"> " . elgg_echo('rssimport:select:all') . "</label>";
	$maincontent .= "</div>";
	$maincontent .= "<div class=\"rssimport_control\">";
	
	//	create form for import
	$createform = elgg_view('input/hidden', array('name' => 'rssimportImport', 'id' => 'rssimportImport', 'value' => ''));
	$createform .= elgg_view('input/hidden', array('name' => 'feedid', 'id' => 'feedid', 'value' => $rssimport_id));
	$createform .= elgg_view('input/submit', array('name' => 'submit', 'value' => elgg_echo('rssimport:import:selected')));
	
	$maincontent .= elgg_view('input/form', array('body' => $createform, 'action' => elgg_get_site_url() . "action/rssimport/import"));
	$maincontent .= "</div>";
	$maincontent .= "
<script type=\"text/javascript\">
	var idarray = new Array();
</script>
";
	$maincontent .= "</div><!-- /rssimport_control_box -->";
	
	//if no items are importable, display message instead of form - controlled by jquery at the bottom of the page
	$maincontent .= "<div class=\"rssimport_item\" id=\"rssimport_nothing_to_import\">";
	$maincontent .= elgg_echo('rssimport:nothing:to:import');
	$maincontent .= "</div><!-- /rssimport_nothing_to_import -->";
	
	//Display each item
	$importablecount = 0;
	foreach ($feed->get_items() as $item):
		if (!rssimport_already_imported($item, $rssimport)) {
			// set some convenience variables
			$importablecount++;
			$class = "";
			$checkboxname = "rssmanualimport";
			$checkboxdisabled = "";
			$itemid = $item->get_id(true);
			
			if (rssimport_is_blacklisted($item, $rssimport)) {
				$importablecount--;
				$class = " rssimport_blacklisted";
				$checkboxname = "rssmanualimportblacklisted";
				$checkboxdisabled = " disabled";
			}
		
			//wrapper div
			$maincontent .= "<div class=\"rssimport_item" . $class . "\">";
		
			$maincontent .= "<table><tr><td>";

			// 	checkbox here
			// 	using hash of the id, because the id is a URL and could potentially contain commas which will screw up our array
			$maincontent .= "<input type=\"checkbox\" name=\"$checkboxname\" value=\"" . $itemid . "\" onclick=\"javascript:rssimportToggle('" . $itemid . "');\"$checkboxdisabled>";
			
		
			$maincontent .= "</td><td>";
			//item title
			$maincontent .= "<div class=\"rssimport_title\">";
			$maincontent .= "<h4><a href=\"" . $item->get_permalink() . "\">" . $item->get_title() . "</a></h4>";
			$maincontent .= "</div>";

			//if content is long (more than 800 characters) create a short excerpt to show so page isn't really long
			$content = strip_tags($item->get_content(), $allow_tags);
			$use_excerpt = false;
			if (strlen($content) > 800) {
				$excerpt = elgg_get_excerpt($content, 800);
				$excerpt .= " (<a href=\"javascript:rssimportToggleExcerpt('$itemid');\">" . elgg_echo('rssimport:more') . "</a>)<br><br>";
				$content .= " (<a href=\"javascript:rssimportToggleExcerpt('$itemid');\">" . elgg_echo('rssimport:less') . "</a>)<br><br>";
				$use_excerpt = true;
			}
		
			// description excerpt
			$maincontent .= "<div class=\"rssimport_excerpt\" id=\"rssimport_excerpt" . $itemid . "\">";
			if ($use_excerpt) {
				$maincontent .= $excerpt;
			}
			else {
				$maincontent .= $content;
			}
			$maincontent .= "</div>";
		
			$maincontent .= "<div class=\"rssimport_content\" id=\"rssimport_content" . $itemid . "\">";
			$maincontent .= $content;
			$maincontent .= "</div>";

			// date of posting	
			$maincontent .= "<div class=\"rssimport_date\">";
			$maincontent .= elgg_echo('rssimport:postedon'); 
			$maincontent .= $item->get_date('F j, Y | g:i a');
			$maincontent .= "</div>";
		
			$maincontent .= "<div class=\"tags\">";
			$maincontent .= elgg_echo('rssimport:tags') . ": ";
			foreach ($item->get_categories() as $category) {
				$maincontent .= $category->get_label() . ", ";
			}
 			$maincontent .= "</div>";
			$maincontent .= "</td></tr></table>";
			
			//create delete/undelete link
			if (rssimport_is_blacklisted($item, $rssimport)) {
				$url = elgg_get_site_url() . "action/rssimport/blacklist?id=" . $itemid . "&feedid=" . $rssimport_id . "&method=undelete";
				$url = elgg_add_action_tokens_to_url($url);
				$maincontent .= "<a href=\"$url\">" . elgg_echo('rssimport:undelete') . "</a>";
			}
			else {
				$url = elgg_get_site_url() . "action/rssimport/blacklist?id=" . $itemid . "&feedid=" . $rssimport_id . "&method=delete";
				$url = elgg_add_action_tokens_to_url($url);
				$maincontent .= "<a href=\"$url\">" . elgg_echo('rssimport:delete') . "</a>";
			}
			//end of wrapper div
			$maincontent .= "</div>";
		}
	endforeach;
	
	$class = "";
	if ($visiblecount == 0) {
		$class = " rssimport_form_hidden"; 	
	}

	
	$maincontent .= "</div><!-- rssimport_rsslisting -->";
	
}	
	$maincontent .= "</div>";


// some items can be imported, so make that div visible
if($importablecount > 0){
	$maincontent .= "<script type=\"text/javascript\">
$(document).ready(function() {
	$('#rssimport_control_box').toggle(0, function(){ });
});
</script>";
}


// no items can be imported, so make message visible
if($importablecount > 0){
	$maincontent .= "<script type=\"text/javascript\">
$(document).ready(function() {
	$('#rssimport_nothing_to_import').toggle(0, function(){ });
});
</script>";
}


unset($_SESSION['rssimport']);

// place the form into the elgg layout
$body = elgg_view_layout('one_sidebar', array('content' => $maincontent, 'sidebar' => $sidebar));

// display the page
echo elgg_view_page($title, $body);
