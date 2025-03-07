<?php
/**
 * My Transit Lines
 * Proposal search bar
 *
 * @package My Transit Lines
 */
 
/* created by Jan Garloff, 2023-10-14 */

/**
 * Outputs the search bar using the WP_Query passed.
 * If null or nothing is passed the query from get_query() is used
 *
 * @param WP_Query $query
 * @param string $additional_html This will be included as-is in the form. Can be used to add more input fields
 * @return string
 */
function mtl_search_bar_output($query = null, $additional_html = '') {
    if ($query == null) {
        $query = get_query();
    }

	$output = '';

    // get the mtl options
	$mtl_options3 = get_option('mtl-option-name3');

	$open_class = get_search_bar_open() ? "open" : "closed";
	$open_details = get_search_bar_open() ? "open" : "";

	// filter start
	$output .= '
	<div id="mtl-list-filter">
		<form name="mtl-filter-form" id="mtl-filter-form" method="get" action="'.get_permalink().'" data-mtl-set-tab>
			<details data-mtl-replace-with="#mtl-filter-details" id="mtl-filter-details" '.$open_details.'>
				<summary data-mtl-toggle-class class="'.$open_class.'">
					<span data-mtl-toggle-class id="mtl-search-submit-closed" class="'.$open_class.'">
						<span class="vertical-center">'.__('Search options','my-transit-lines').'</span>'.
						(get_search_bar_open() ? '' :
						'<span class="mtl-search-submit">
							<strong>'.__('Search:','my-transit-lines').'</strong>
							<input type="search" name="search" value="'.get_search_term().'">
						</span>
						<button class="mtl-search-submit" type="submit">'.__('Filter/sort','my-transit-lines').'</button>').
					'</span>
				</summary>'.
				$additional_html.
				'<input type="hidden" name="mtl-search-bar-open" id="mtl-search-bar-open" value="'.$open_details.'">
				<input type="checkbox" id="mtl-filter-multiple">
				<label for="mtl-filter-multiple">' . __('Select multiple values','my-transit-lines') . '</label><hr>
				<p class="mtl-filter-section">
					<strong>'.__('Filter:','my-transit-lines').'</strong>';

	$output .= multi_selector_output(get_query_cats(), array_map(function($cat) {
		return [
			'ID' => $cat->term_id,
			'name' => $cat->name,
		];
	}, get_searchable_categories()), 'mtl-catid', __('All transit modes','my-transit-lines'));

	$output .= multi_selector_output(get_query_users(), array_map(function($user) {
		return [
			'ID' => $user->ID,
			'name' => $user->display_name,
		];
	}, get_users('oderby=display_name')), 'mtl-userid', __('All users (incl. unregistered)','my-transit-lines'));

	// only show tag selector when enabled or for admins
	if (districts_enabled()) {
		$output .= multi_selector_output(get_query_tags(), array_map(function($tag) {
			return [
				'ID' => $tag->term_id,
				'name' => $tag->name,
			];
		}, get_tags()), 'mtl-tag-ids', __('All regions','my-transit-lines'));
	}

	if(current_user_can('administrator')) {
		$is_checked = in_array("draft", get_status());
		$output .= '
					<input id="mtl-show-drafts" name="show-drafts" value="'.($is_checked ? 'true' : 'false').'" autocomplete="off" type="checkbox" '.($is_checked ? 'checked' : '').' onchange="event.target.value = event.target.checked;">
					<label for="mtl-show-drafts">'.__('Show drafts', 'my-transit-lines').'</label>';
	}
	$output .= '</p>';

	$statuses = get_terms(array('taxonomy' => 'sorting-phase-status', 'hide_empty' => false));
	if (count($statuses) > 1) {
		// Sorting phase status selector
		$output .= '
				<p>
					<strong>'.__('Sorting Phase Status:','my-transit-lines').'</strong>
					<select name="mtl-statusid">
						<option value="">'.__('All statuses','my-transit-lines').' </option>';
		foreach( $statuses as $single_status) {
			$statusid = $single_status->term_id;
			$output .= '<option value="'.$statusid.'"'.($statusid === get_query_statusid() ? ' selected="selected"' : '').'>'.$single_status->name.' </option>'."\r\n";
		}
		$output .= '</select>
				</p>';
	}

    $order_by = get_orderby();
    $order = get_order();

	$output .= '<p>
					<strong>'.__('Sort:','my-transit-lines').'</strong>
					<select name="orderby">
						<option'.($order_by=='date' ? ' selected="selected"' : '').' value="date">'.__('Date','my-transit-lines').'</option>
						<option'.($order_by=='comment_count' ? ' selected="selected"' : '').' value="comment_count">'.__('Number of comments','my-transit-lines').'</option>
						<option'.($order_by=='rand' ? ' selected="selected"' : '').' value="rand">'.__('Random','my-transit-lines').'</option>
					</select>
					<select name="order">
						<option'.($order=='DESC' ? ' selected="selected"' : '').' value="DESC">'.__('Descendent','my-transit-lines').'</option>
						<option'.($order == 'ASC' ? ' selected="selected"' : '').' value="ASC">'.__('Ascendent','my-transit-lines').'</option>
					</select>
	';

    $posts_per_page = get_posts_per_page();

	// Selector for amount of proposals shown
	$amounts = [25, 50, 100, 250];
	$output .= '	<strong>'.__('Amount:','my-transit-lines').'</strong>';
	$output .= '	<select name="num">';
	if (!in_array($posts_per_page, $amounts)) {
		$output .= '	<option selected="selected" value="'.$posts_per_page.'">'.($posts_per_page > -1 ? $posts_per_page : __('all', 'my-transit-lines')).'</option>';
	}
	foreach ($amounts as $amount) {
		$output .= '	<option '.($posts_per_page == $amount ? ' selected="selected"' : '').' value="'.$amount.'">'.$amount.'</option>';
	}
	$output .= '	</select>
				</p>';

	$output .= '<p id="mtl-search-submit-open">'.
					(get_search_bar_open() ?
					'<span class="mtl-search-submit">
						<strong>'.__('Search:','my-transit-lines').'</strong>
						<input type="search" name="search" value="'.get_search_term().'">
					</span>
					<button class="mtl-search-submit" type="submit">'.__('Filter/sort','my-transit-lines').'</button>' : '').
				'</p>';
	
	$output .= '
			</details>
		</form>
	</div>'."\r\n";

    return $output;
}

/**
 * Returns the output for a multi-selector
 * @param array $queried_options array containing all searched for ids
 * @param array $all_options array containing associative arrays with ID and name fields
 * @param string $selector_name name for the selector. Must be a valid html name
 * @param string $all_selected_option text of the all_selected option
 * @return string
 */
function multi_selector_output($queried_options, $all_options, $selector_name, $all_selected_option) {
	$output = '';
	if (count($all_options) > 1) {
		$all_selected = empty($queried_options) || $queried_options == array_map(function($option) {
			return $option['ID'];
		}, $all_options);
	
		$multiple = count($queried_options) > 1 ? " multiple" : "";
		$selected = $all_selected ? " selected" : "";

		// selector
		$output .= "<select name=\"$selector_name\" class=\"allowsMultiple\" $multiple>\r\n
					<option value=\"\"$selected>$all_selected_option</option>\r\n";
		foreach($all_options as $option) {
			$id = $option['ID'];

			$selected = !$all_selected && in_array($id, $queried_options) ? " selected" : "";

			$output .= "<option value=\"$id\"$selected>{$option['name']}</option>\r\n";
		}
		$output .= "</select>";
	}
	return $output;
}

/**
 * Returns the paginate links
 *
 * @param int $max_num_pages
 * @return string
 */
function get_paginate_links($max_num_pages) {
	$big = 999999999; // need an unlikely integer
	return ('<div data-mtl-replace-with=".mtl-paginate-links" class="mtl-paginate-links">'.
	paginate_links( array(
		'base' => str_replace( $big, '%#%', get_pagenum_link( $big ) ),
		'format' => '?paged=%#%',
		'current' => max( 1, get_query_var('paged') ),
		'total' => $max_num_pages,
		'prev_text' => '',
		'next_text' => ''
	) ).'</div>');
}

/**
 * Adds the data-mtl-set-tab attribute to pagination links
 */
function paginate_links_filter($output) {
	return str_replace('<a', '<a data-mtl-set-tab', $output);
}
add_filter('paginate_links_output', 'paginate_links_filter');

/**
 * Returns the WP_Query determined by the $_GET arguments
 * 
 * @param int $per_page_multiple which number the posts per page has to be a multiple of
 *
 * @return WP_Query
 */
function get_query($per_page_multiple = 1) {

    $posts_per_page = get_posts_per_page();
    
    $query_string = array(
        'posts_per_page' => max(($posts_per_page - (($posts_per_page + 1) % $per_page_multiple)), -1),
        'post_type' => 'mtlproposal',
        'author__in' => get_query_users(),
        'category__in' => get_query_cats_children(),
		'tag__in' => get_query_tags(),
        's' => get_search_term(),
		'search_columns' => ['post_title'],
        'post_status' => get_status(),
        'orderby' => get_orderby(),
        'order' => get_order(),
        'paged' => get_query_var('paged') ? get_query_var('paged') : 1,
        'tax_query' => get_taxonomy_status(),
		'post__in' => get_post_in(),
    );

    return new WP_Query($query_string);
}

/**
 * Returns all term ids of the given taxonomy except for the excluded one
 *
 * @param string $taxonomy
 * @param int $exclude_id
 * @return int[]
 */
function other_term_ids($taxonomy, $exclude_id) {
	$all_terms = get_terms( array(
		'taxonomy' => $taxonomy,
		'hide_empty' => false,
	));

	$term_ids = array();
	foreach ($all_terms as $current_term) {
		if ($current_term->term_id != $exclude_id)
			$term_ids[] = $current_term->term_id;
	}

	return $term_ids;
}

/**
 * Returns the amount of posts to be displayed per page
 * @return int
 */
function get_posts_per_page() {
	if (isset($_GET['num']))
		return intval($_GET['num']);

	return 25;
}

/**
 * Returns the categories to query for, including children of passed in categories
 *
 * @return array
 */
function get_query_cats_children() {
	if(isset($_GET['mtl-catid']) && $_GET['mtl-catid'] != '') {
		return array_merge(...array_map(function($catid) {
			return get_cat_children($catid);
		}, explode(',', $_GET['mtl-catid'])));
	}

	return [];
}

/**
 * Recursively returns an id array of all children of the specified category and the category itself
 * 
 * @return array
 */
function get_cat_children($catid) {
	if (!isset(get_option('mtl-option-name')['mtl-also-search-for-cat'.$catid]))
		return [$catid];

	$also_search_for_cats = get_option('mtl-option-name')['mtl-also-search-for-cat'.$catid];
	if ($also_search_for_cats == '')
		return [$catid];

	return array_merge([$catid], ...array_map(function($id) {
		return get_cat_children($id);
	}, explode(',', $also_search_for_cats)));
}

/**
 * Returns the categories to query for without children
 * 
 * @return array
 */
function get_query_cats() {
	if(isset($_GET['mtl-catid']) && $_GET['mtl-catid'] != '')
		return explode(',', $_GET['mtl-catid']);

	return [];
}

/**
 * Returns the user id to query for
 *
 * @return array
 */
function get_query_users() {
	if(isset($_GET['mtl-userid']) && $_GET['mtl-userid'] != '')
		return explode(',', $_GET['mtl-userid']);

	return [];
}

/**
 * Returns the search term to query for
 *
 * @return string
 */
function get_search_term() {
    if(isset($_GET['search']))
        return $_GET['search'];
	
		return '';
}

/**
 * Returns the post status to query for
 *
 * @return array
 */
function get_status() {
	$status = array('publish');

	$show_drafts = show_drafts();
    if($show_drafts) {
        $status[] = 'draft';
    }
	if($show_drafts || current_user_can( 'administrator' )) {
		$status[] = 'pending';
	}
	
	return $status;
}

/**
 * Returns if drafts should be shown
 *
 * @return bool
 */
function show_drafts() {
	return isset($_GET['show-drafts']) && $_GET['show-drafts'] == 'true' && ((isset($_GET['mtl-userid']) && $_GET['mtl-userid'] == get_current_user_id()) || ( current_user_can( 'administrator' )));
}

/**
 * Returns the order to query for
 *
 * @return string
 */
function get_order() {
	if (isset($_GET['order']))
		return $_GET['order'];

	return 'DESC';
}

/**
 * Returns the orderby to query for
 *
 * @return string
 */
function get_orderby() {
	if (isset($_GET['orderby']))
		return $_GET['orderby'];

	return 'date';
}

/**
 * Returns the sorting phase status to query for
 *
 * @return array
 */
function get_taxonomy_status() {
	if(isset($_GET['mtl-statusid']) && $_GET['mtl-statusid'] != '') {
		$single_statusid = intval($_GET['mtl-statusid']);

		// if default status is searched for also find proposals without status
		if (get_term_by('id', $single_statusid, 'sorting-phase-status')->slug == get_taxonomy('sorting-phase-status')->default_term['slug']) {
			return array( array (
				'taxonomy' => 'sorting-phase-status',
				'terms' => other_term_ids('sorting-phase-status', $single_statusid),
				'operator' => 'NOT IN',
			));
		}

		return array( array (
			'taxonomy' => 'sorting-phase-status',
			'terms' => $single_statusid,
		),);
	}

	return array();
}

/**
 * Returns the sorting phase status id
 *
 * @return int|string
 */
function get_query_statusid() {
	if(isset($_GET['mtl-statusid']) && $_GET['mtl-statusid'] != '')
		return intval($_GET['mtl-statusid']);
	
	return '';
}

/**
 * Returns the list of ids to search in
 *
 * @return array
 */
function get_post_in() {
	if(isset($_GET['mtl-post-ids']) && $_GET['mtl-post-ids'] != '')
		return explode(",", $_GET['mtl-post-ids']);

	return array();
}

/**
 * Returns the list of tags to search in
 *
 * @return array
 */
function get_query_tags() {
	if(isset($_GET['mtl-tag-ids']) && $_GET['mtl-tag-ids'] != '')
		return explode(",", $_GET['mtl-tag-ids']);

	return [];
}

/**
 * Returns whether the search bar is open
 * 
 * @return bool
 */
function get_search_bar_open() {
	if(!empty($_GET['mtl-search-bar-open']))
		return $_GET['mtl-search-bar-open'] === "open";
	
	return false;
}
