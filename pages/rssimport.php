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

//include simplepie class
$allow_tags = '<a><p><br><b><i><em><del><pre><strong><ul><ol><li><img><hr>';

// set the title
$title = elgg_echo('rssimport:title');


/**
 * 	*************************************
 * 		Begin Import Listing
 * 	*************************************
 */

// list existing feeds
$leftarea = "<div class=\"rssimport_feedlist\">";
$leftarea .= "<h4 class=\"rssimport_center\">" . elgg_echo('rssimport:listing') . "</h4><br>";

//get an array of our imports
$import = get_user_rssimports(elgg_get_page_owner_entity());

// iterate through, creating a link for each import
if (is_array($import)) {
	$count = count($import);
	for ($i=0; $i<$count; $i++) {
		if ($import[$i]->import_into == $import_into && $import[$i]->rssimport_containerid == $defcontainer_id) {
      $deleteurl = elgg_add_action_tokens_to_url(elgg_get_site_url() . "action/rssimport/delete?id=" . $import[$i]->guid);
		
      $leftarea .= "<div class=\"rssimport_listitem\">";
      $leftarea .= "<a href=\"" . elgg_get_site_url() . "rssimport/" . $defcontainer_id . "/" . $import_into . "/" . $import[$i]->guid . "\" class=\"rssimport_listing\">" . $import[$i]->title . "</a>"; 
      $leftarea .= "<a href=\"" . $deleteurl . "\" class=\"rssimport_deletelisting\" onclick=\"return confirm('" . elgg_echo('rssimport:delete:confirm') . "');\"><img src=\"" . elgg_get_site_url() . "mod/rssimport/graphics/delete.png\"></a>";
      $leftarea .= "<div class=\"rssimport_clear\"></div>";
      $leftarea .= "</div>";
		}
	}
}

$leftarea .= "</div>";

/**
 * 	**************************************
 * 		End Import Listing
 * 	**************************************
 */



/**
 * 	**************************************
 * 		Begin Right Column
 * 	**************************************
 */


	$rightarea = "<div class=\"rssimport_feedwrapper\">";
	$owner = get_entity($defcontainer_id);
	if($owner instanceof ElggUser || $owner instanceof ElggGroup){
		$name = $owner->name;
	}
	
	$rightarea .= "<h2>" . $name . " " . $import_into . " " . elgg_echo("rssimport:import:lc") . "</h2>";



/**
 * ************************************
 * 		Begin Import Creation Form
 * ************************************
 */

	//form for creating an import

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
	$rightarea .= "<h4 class=\"rssimport_center\"><a href=\"javascript:void(0);\" class=\"formtoggle\">";
	if ($rssimport instanceof ElggObject) {
		$rightarea .= elgg_echo('rssimport:edit:settings');
	}
	else {
		$rightarea .= elgg_echo('rssimport:create:new');	
	}
	$rightarea .= "</a></h4><br>";
	
	//create the div for the form, hidden if we're viewing a feed, visible if we're adding a new feed
	if ($rssimport instanceof ElggObject) {
		$rightarea .= "<div id=\"createrssimportform\">";
	}
	else {
		$rightarea .= "<div id=\"createrssimportform\" style=\"display:block\">";
	}
	
	//different actions depending on whether we're creating new or updating existing
	if ($rssimport instanceof ElggObject) {
		$createform .= elgg_view('input/hidden', array('name' => 'updating_id', 'value' => $rssimport_id));
		$rightarea .= elgg_view('input/form', array('body' => $createform, 'action' => elgg_get_site_url() . "action/rssimport/update"));
	}
	else{
		$rightarea .= elgg_view('input/form', array('body' => $createform, 'action' => elgg_get_site_url() . "action/rssimport/add"));
	}
	
	$rightarea .= "</div>";

/**
 * 	*************************************
 * 		End Import Creation Form
 * 	*************************************
 */



	
	
	$rightarea .= "<hr><br>";
	
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
		$rightarea .= elgg_echo('rssimport:no:feed');
	}	
	

	/**
	 * 	*********************************
	 * 		Begin RSS Listing
	 * 	*********************************
	 */
	$rightarea .= "<div class=\"rssimport_rsslisting\">";
	
	
		// The Feed Title
	$rightarea .= "<div class=\"rssimport_blog_title\">";
	$rightarea .= "<h2><a href=\"" . $feed->get_permalink() . "\">" . $feed->get_title() . "</a></h2>";
	$rightarea .= "</div>";
	
	// controls for importing
	$rightarea .= "<div class=\"rssimport_item\" id=\"rssimport_control_box\">";
	$rightarea .= "<div class=\"rssimport_control\">";
	$rightarea .= "<input type=\"checkbox\" name=\"checkalltoggle\" id=\"checkalltoggle\" onclick=\"javascript:rssimportToggleChecked();\">";
	$rightarea .= "<label for=\"checkalltoggle\"> " . elgg_echo('rssimport:select:all') . "</label>";
	$rightarea .= "</div>";
	$rightarea .= "<div class=\"rssimport_control\">";
	
	//	create form for import
	$createform = elgg_view('input/hidden', array('name' => 'rssimportImport', 'id' => 'rssimportImport', 'value' => ''));
	$createform .= elgg_view('input/hidden', array('name' => 'feedid', 'id' => 'feedid', 'value' => $rssimport_id));
	$createform .= elgg_view('input/submit', array('name' => 'submit', 'value' => elgg_echo('rssimport:import:selected')));
	
	$rightarea .= elgg_view('input/form', array('body' => $createform, 'action' => elgg_get_site_url() . "action/rssimport/import"));
	$rightarea .= "</div>";
	$rightarea .= "
<script type=\"text/javascript\">
	var idarray = new Array();
</script>
";
	$rightarea .= "</div><!-- /rssimport_control_box -->";
	
	//if no items are importable, display message instead of form - controlled by jquery at the bottom of the page
	$rightarea .= "<div class=\"rssimport_item\" id=\"rssimport_nothing_to_import\">";
	$rightarea .= elgg_echo('rssimport:nothing:to:import');
	$rightarea .= "</div><!-- /rssimport_nothing_to_import -->";
	
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
			$rightarea .= "<div class=\"rssimport_item" . $class . "\">";
		
			$rightarea .= "<table><tr><td>";

			// 	checkbox here
			// 	using hash of the id, because the id is a URL and could potentially contain commas which will screw up our array
			$rightarea .= "<input type=\"checkbox\" name=\"$checkboxname\" value=\"" . $itemid . "\" onclick=\"javascript:rssimportToggle('" . $itemid . "');\"$checkboxdisabled>";
			
		
			$rightarea .= "</td><td>";
			//item title
			$rightarea .= "<div class=\"rssimport_title\">";
			$rightarea .= "<h4><a href=\"" . $item->get_permalink() . "\">" . $item->get_title() . "</a></h4>";
			$rightarea .= "</div>";

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
			$rightarea .= "<div class=\"rssimport_excerpt\" id=\"rssimport_excerpt" . $itemid . "\">";
			if ($use_excerpt) {
				$rightarea .= $excerpt;
			}
			else {
				$rightarea .= $content;
			}
			$rightarea .= "</div>";
		
			$rightarea .= "<div class=\"rssimport_content\" id=\"rssimport_content" . $itemid . "\">";
			$rightarea .= $content;
			$rightarea .= "</div>";

			// date of posting	
			$rightarea .= "<div class=\"rssimport_date\">";
			$rightarea .= elgg_echo('rssimport:postedon'); 
			$rightarea .= $item->get_date('F j, Y | g:i a');
			$rightarea .= "</div>";
		
			$rightarea .= "<div class=\"tags\">";
			$rightarea .= elgg_echo('rssimport:tags') . ": ";
			foreach ($item->get_categories() as $category) {
				$rightarea .= $category->get_label() . ", ";
			}
 			$rightarea .= "</div>";
			$rightarea .= "</td></tr></table>";
			
			//create delete/undelete link
			if (rssimport_is_blacklisted($item, $rssimport)) {
				$url = elgg_get_site_url() . "action/rssimport/blacklist?id=" . $itemid . "&feedid=" . $rssimport_id . "&method=undelete";
				$url = elgg_add_action_tokens_to_url($url);
				$rightarea .= "<a href=\"$url\">" . elgg_echo('rssimport:undelete') . "</a>";
			}
			else {
				$url = elgg_get_site_url() . "action/rssimport/blacklist?id=" . $itemid . "&feedid=" . $rssimport_id . "&method=delete";
				$url = elgg_add_action_tokens_to_url($url);
				$rightarea .= "<a href=\"$url\">" . elgg_echo('rssimport:delete') . "</a>";
			}
			//end of wrapper div
			$rightarea .= "</div>";
		}
	endforeach;
	
	$class = "";
	if ($visiblecount == 0) {
		$class = " rssimport_form_hidden"; 	
	}

	
	$rightarea .= "</div><!-- rssimport_rsslisting -->";
	
}	
	$rightarea .= "</div>";


// some items can be imported, so make that div visible
if($importablecount > 0){
	$rightarea .= "<script type=\"text/javascript\">
$(document).ready(function() {
	$('#rssimport_control_box').toggle(0, function(){ });
});
</script>";
}


// no items can be imported, so make message visible
if($importablecount > 0){
	$rightarea .= "<script type=\"text/javascript\">
$(document).ready(function() {
	$('#rssimport_nothing_to_import').toggle(0, function(){ });
});
</script>";
}


unset($_SESSION['rssimport']);

// place the form into the elgg layout
$body = elgg_view_layout('one_sidebar', array('content' => $rightarea, 'sidebar' => $leftarea));

// display the page
echo elgg_view_page($title, $body);
