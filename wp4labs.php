<?php
/*
Plugin Name: WP4Labs
Plugin URI: http://wordpress.org/extend/plugins/wp4labs/
Description: Adds some lightweight features to manage scientific groups and users.
Version: 1.6
Author: Philipp Franck
Author URI: --
*/

// user profile
add_action('show_user_profile', 'biofoo_profile');
add_action('edit_user_profile', 'biofoo_profile');
//add_action('user_register', 'biofoo_profile');

add_action('personal_options_update', 'save_biofoo_profile');
add_action('edit_user_profile_update', 'save_biofoo_profile');

// menu einträge
add_action('admin_init', 'init_biofoo');
add_action('init', 'init_projects');
add_action('admin_menu', 'biofoo_menu');
add_action('add_meta_boxes', 'biofoo_add_boxes');
add_action('save_post', 'biofoo_save_options');

// css
add_action('admin_print_styles', 'add_biofoocss');
add_action('wp_print_styles', 'add_biofoocss');

//filters
add_filter('user_has_cap', 'give_permissions', 0, 3); // die permissions manipulieren setzen für die projekte
add_filter('get_avatar', 'get_pafs_avatar', 100, 3); // verbesserten avatar auswerfen
//add_filter('single_template', 'biofoo_optional_template'); // FUNKTION REMOVED fügt optionale page templates für biofoo_projects ein

// ajax-spezifische actions
add_action('admin_head', 'all_ajax_functions');
add_action('wp_ajax_remove_user_from_project', 'remove_user_from_project');
add_action('wp_ajax_add_user_to_project', 'add_user_to_project');
add_action('wp_ajax_fetch_countries', 'fetch_it');
add_action('wp_ajax_fetch_inst', 'fetch_it');
add_action('wp_ajax_select_picture', 'select_picure_response');



function add_biofoocss() {
	$biofoocssurl = WP_PLUGIN_URL . '/wp4labs/style.css';
        $biofoocss = WP_PLUGIN_DIR . '/wp4labs/style.css';
        if (file_exists($biofoocss)) {
        	wp_register_style('biofoocss', $biofoocssurl);
        	wp_enqueue_style('biofoocss');
	}
}


function init_biofoo() {
	//register settings
	register_setting('biofoo_settings', 'active_usergroups');
	register_setting('biofoo_settings', 'passive_usergroups');
	register_setting('biofoo_settings', 'dont_use_ariw');
}

function init_projects() {	
	//register post-type or [bad] projects
	$labels = array(
		'name' => 'Project',
    		'singular_name' => 'Project',
		'add_new' => 'Add Project',
		'add_new_item' => 'Add New Project',
		'edit_item' => 'Edit Project',
		'new_item' => 'New Project',
		'view_item' => 'View Project',
		'search_items' => 'Search for Project',
		'not_found' => 'No Projects Found',
		'not_found_in_trash' => 'No Projects in Trashbin', 
		'parent_item_colon' => '',
		'menu_name' => 'Projects'
	);
	$args = array(
    		'labels' => $labels,
    		'public' => true,
    		'publicly_queryable' => true,
    		'show_ui' => true, 
    		'show_in_menu' => true, 
    		'query_var' => true,
    		'rewrite' => true,
    		'capability_type' => 'post',
    		'has_archive' => true, 
    		'hierarchical' => true,
    		'menu_position' => null,
    		'show_in_nav_menus ' => true,
    		'supports' => array('editor', 'revisions', 'title', 'permalink', 'excerpt')
    		
	); 
	register_post_type('biofoo_project', $args);
	
}

	
//boxen für projekte
function biofoo_add_boxes($post_type) {
	add_meta_box('biofoo_box', 'Project Members', 'biofoo_box', 'biofoo_project', 'side');
	//add_meta_box('biofoo_box2', 'Project Options', 'biofoo_box2', 'biofoo_project', 'side'); FUNCTION REMOVED
}
 
// hauptmenueintrag
function biofoo_menu() {
	add_options_page('User Groups', 'User Groups', 'edit_users', 'biofoo_settings', 'biofoo_main');
	
}

function biofoo_box($post) {
	?><div id='biofoobox_inside'><?php
	biofoo_box_inside($post);
	?></div><?php
}
//getrennte funktion, damit die box per ajax als ganzes erneuert werden kann!
function biofoo_box_inside($post) {
	?><p><strong><?php _e('Members'); ?></strong></p><?php
	$memberZ = get_post_meta($post->ID, 'member', false);
	if (!is_array($memberZ)) {$memberZ = array($memberZ);}
	//<div ><span><a class="ntdelbutton" id="post_tag-check-num-0">X</a>&nbsp;dd</span></div>
	echo "<ul id='memberlist' class='tagchecklist'>";
	$agroups = explode("\n", get_option('active_usergroups'));
	foreach ($memberZ as $member) {
		$user = get_userdata($member);
		//remove eventually deleted user!		
		if (!$user->ID) {
			delete_post_meta($post->ID, 'member', $member);
			continue;
		}
		
		//var_dump($user);
		$ugroup = rawurldecode($user->biofoo_usergroup);
		//if ($css == 'boss') {$bosses[] = $member;} VERMUTLICH UNNÖTIG
		echo "<li id='user_{$user->ID}'><span><a class='ntdelbutton' href='javascript:remove_user_from_project({$user->ID}, {$post->ID});'>X</a></span> <a style='padding-left:4px; font-size:11px' href='user-edit.php?user_id={$user->ID}'>{$user->first_name} {$user->last_name} ($ugroup)</a></li>";
	}
	echo "</ul></p>";
	
	$users = get_users(array('orderby' => 'last_name', 'order' => 'ASC', 'exclude' => $memberZ, 'fields' => 'ID'));
	if (count($users) > 0) {
		?><p><strong><?php _e('Add Member'); ?></strong></p>
		<select id='biofoo_userlist' id='biofoo_add_member' size='1'><?php
	
		foreach ($users as $auser) {
			$user = get_userdata($auser);
			echo "<option id='user2_{$user->ID}' value='{$user->ID}' name='user2_{$user->ID}'>{$user->first_name} {$user->last_name}</option>";
		}
		echo "</select><input type='button' value='Add' class='button' onclick='add_user_to_project({$post->ID})'>";
		//var_dump($users);
	}
	
}

/*function biofoo_box2($post) { // template chooser  FUNCTION REMOVED
/*	wp_nonce_field( plugin_basename(__FILE__), 'biofoo_nonce' );
	$template = get_post_meta($post->ID, '_wp_page_template', true);
	$istemplate = ($template != '') ? "checked='checked'" : '';
	echo "<input type='checkbox' $istemplate id='_wp_page_template' name='_wp_page_template' value='lonely_single-biofoo_project.php' /><label for='_wp_page_template'>Don't show in project archive.</label>";
	//var_dump($template);
}*/

function biofoo_save_options($post_id) {
	if (!wp_verify_nonce($_POST['biofoo_nonce'], plugin_basename(__FILE__))) {
		return $post_id;
	}
	if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) { //??
		return $post_id;
	}

	if (('biofoo_project' != $_POST['post_type']) or (!current_user_can( 'edit_page', $post_id ))) {
		return $post_id;
	}
	// Go!
	if ($_POST['_wp_page_template'] != '') {
		update_post_meta($post_id, '_wp_page_template', $_POST['_wp_page_template']);
	} else {
		delete_post_meta($post_id, '_wp_page_template');
	}

}

/* foo it away FUNCTION REMOVED
function biofoo_optional_template($template) { //filter funktion um spezial-templates für einzelne biofoo_ps szu erzeugen
	global $post;
	$option = get_post_meta($post->ID, '_wp_page_template', true);// und zwar wird einfach wie bei pages die _wp_page_template benutzt.
	$option = (!$option) ? 'single-biofoo_project.php' : $option;
	//	echo("<pre>"); var_dump($option); echo "</pre>";
	$templates = locate_template($option);
	return $templates;
}

*/


/* ----------------------------------------------------- */

function biofoo_profile($user) { 
	//get projects
	$args = array(
		'post_type' => 'biofoo_project',
		'numberposts' => -1,
		'post_status' => null,
		'orderby' => 'title',
		'order' => 'ASC',
		'meta_query' => array(array('key' => 'member', 'value' => $user->ID))
	); 
	$projects = get_posts($args);
	$user = get_biofoo($user); //leider leider leider gibt es keinen filterhook in get_userinfo -> zum Kotzen

	?>
	<h3><?php _e("Further Contact Information", "blank"); ?></h3>
 
	<table class="form-table">
		<tr>
			<th><label for="tel_nr"><?php _e("Picture"); ?></label></th>
			<td>
				<div id='select_pic_output';>
				<?php echo_picture_selector(($user->biofoo_picture) ? 1 : 0, $user->biofoo_picture, 0, $user->ID);?>
				</div>
				<span class="description"><?php _e("Click on the picture, to change your local avatar. You can <a  target='_blank' href='media-new.php'>upload</a> your own picture using the media library for it. Instead you may go to <a target='_blank' href='http://gravatar.com'>gravatar.com</a> an set up a global Avatar."); ?></span>
			</td>
		</tr>
		<tr>
			<th><label for="tel_nr"><?php _e("Phone Number"); ?></label></th>
			<td>
				<input type="text" name="tel_nr" id="tel_nr" value="<?php echo $user->tel_nr; ?>" class="regular-text" />
			</td>
		</tr>
		<tr>
			 <th><label for="blog_rss"><?php _e("Blog"); ?></label></th>
			<td>
				<input type="text" name="blog_rss" id="blog_rss" value="<?php echo $user->blog_rss; ?>" class="regular-text" />
				<span class="description"><?php _e("You may a blog-adress additional to your Homepage"); ?></span>
			</td>
		</tr>
		<tr>
			<th><label for="inst_c"><?php _e("Scientific Institution"); ?></label></th>
			<td>
				<?php if (get_option('dont_use_ariw')) : //select-fields ?>
				<div id='output'></div>
				<input type='hidden' id='myurl' value="<?php echo WP_PLUGIN_URL; ?>/wp4labs/" />
				<a href="javascript:fetch_countries()">Select from ariw.org - Database</a>
				<br />
				<?php endif; ?>
				<?php //(auto-filling) text-fields ?>
				<input type="text" name="inst_name" id="inst_name" value="<?php echo $user->inst_name; ?>" class="regular-text" />
				<span class="description"><?php _e("Institution Name"); ?></span><br />
				<input type="text" name="inst_url" id="inst_url" value="<?php echo $user->inst_url; ?>" class="regular-text" />
				<span class="description"><?php _e("Institution URL"); ?></span><br />				
				
				<input type="text" name="inst_dep" id="inst_dep" value="<?php echo $user->inst_dep; ?>" class="regular-text" />
				<span class="description"><?php _e("Departement"); ?></span><br />

			</td>
		</tr>

	</table>
	
	<h3><?php _e("Academic Information", "blank"); ?></h3>
 
	<table class="form-table">
		<tr>
			<th><label for="ba_degree"><?php _e("Bachelor's Degree"); ?></label></th>
			<td>
				<input type="text" name="ba_degree" id="ba_degree" value="<?php echo  $user->ba_degree; ?>" class="regular-text" />
				<span class="description"><?php _e("Date of your bachelor's degree"); ?></span>
		</td>
		</tr>
				<tr>
			<th><label for="ma_degree"><?php _e("Master's Degree"); ?></label></th>
			<td>
				<input type="text" name="ma_degree" id="ma_degree" value="<?php echo $user->ma_degree; ?>" class="regular-text" />
				<span class="description"><?php _e("Date of your master's degree"); ?></span>
			</td>
		</tr>
		<tr>
			<th><label for="phd_degree"><?php _e("Ph.D."); ?></label></th>
			<td>
				<input type="text" name="phd_degree" id="phd_degree" value="<?php echo $user->phd_degree; ?>" class="regular-text" />
				<span class="description"><?php _e("Date of your Ph.D."); ?></span>
			</td>
		</tr>
	</table>
	
	
	<h3><?php _e("Group Information", "blank"); ?></h3>
 	<p class="description"><?php _e("Only the Admin can change that."); ?></span>
	<table class="form-table">
		<tr>
			<th><label for="group"><?php _e("Usergroup"); ?></label></th>
			<td>
				<select name="group" id="group" size="1">
					<?php	if (current_user_can('edit_users')) {
							$agroups = explode("\n", get_option('active_usergroups'));
							$pgroups = explode("\n", get_option('passive_usergroups'));
							$groups = array_merge($agroups, $pgroups);
							foreach ($groups as $group) {echo "<option", (trim($group) == $user->group) ? " selected='selected'" : '', " value='". rawurlencode(trim($group)) . "'>$group</option>\n";}
						} else {
							echo "<option selected='selected' value='{$user->group}'>{$user->group}</option>\n";
						}						
					?>						
				</select>			
				<span class="description"><?php _e("The group you belong to."); ?></span><br />
				<!--<input type="checkbox" name="isactive" id="isactive" value="<?php echo $biofoo['isactive']; ?>" class="regular-text" />Active-->
			</td>
		</tr>
		<tr>
			<th><label for="projects"><?php _e("Projects"); ?></label></th>
			<td>
				<ul id="projects">
					<?php foreach ($projects as $project) {
						echo (get_post_meta($project->ID, 'member', $user->ID) != '') ? "<li><a href='post.php?post={$project->ID}&action=edit'>{$project->post_title}</a></li>\n" : '';	
					}?>						
				</select>	
				<pre><?php //var_dump($user); ?></pre>
				<span class="description"><?php _e("Got to the Projects Page to Add/Remove Users from Project, if you are projects leader."); ?></span> 
			</td>
		</tr>
	</table>	
	

	<?php 
	wp_nonce_field(plugin_basename(__FILE__), 'biofoo_nonce');

}
 

function save_biofoo_profile($user_id) {
	if (!current_user_can( 'edit_user', $user_id ) ) { return false; }
	
	if (!wp_verify_nonce($_POST['biofoo_nonce'], plugin_basename(__FILE__))) {return $post_id;}


	$biofoo = array('tel_nr'=>$_POST['tel_nr'], 'blog_rss'=>$_POST['blog_rss'], 'inst_name'=>$_POST['inst_name'], 'inst_url'=>$_POST['inst_url'], 'inst_dep'=>$_POST['inst_dep'], 'ba_degree'=>$_POST['ba_degree'], 'ma_degree'=>$_POST['ma_degree'], 'phd_degree'=>$_POST['phd_degree'], 'biofoo_picture'=>$_POST['biofoo_picture']);
	$biofoo = array_map('rawurlencode', $biofoo);	
	$biofoo = json_encode($biofoo);
	
	update_usermeta($user_id, 'bio_foo_fields', $biofoo);
	
	if (current_user_can('edit_users')) {
		update_usermeta($user_id, 'biofoo_usergroup', mysql_real_escape_string($_POST['group']));
	}


	//get projects
	/*$args = array(
		'post_type' => 'biofoo_project',
		'numberposts' => -1,
		'post_status' => null,
		'orderby' => 'title',
		'order' => 'ASC',
		'fields' => 'ID'
	); 
	$projects = get_posts($args);
		

	foreach ($projects as $project) {
		delete_post_meta((int) $project, 'member', $user_id);
		if (in_array($project, $_POST['projects'])) {
			add_post_meta((int) $project, 'member', $user_id);
		}	
	}*/

	
}

function biofoo_main() { ?>
	<div class='wrap'>
		<h2><?php echo _e('User Groups'); ?></h2>
		<form method='post' action='options.php'>
			<?php settings_fields('biofoo_settings'); ?>
			<label for='active_usergroups'><p><?php echo _e('Active Usergroups'); ?></label><br />
			<textarea rows='9' id='active_usergroups' name='active_usergroups'><?php echo get_option('active_usergroups', "P.I.\nPostdoc\nGraduate Student\nUndergraduate Student\nTechnician\nCollaborator\nSecretary"); ?></textarea>
			
			<label for='passive_usergroups'><p><?php echo _e('Passive Usergroups'); ?></label><br />
			<textarea rows='9' id='passive_usergroups' name='passive_usergroups'><?php echo get_option('passive_usergroups', 'Alumni'); ?></textarea>				
					
			<p><?php echo _e("Enter one Name of a Usergroup each line. Note: The topmost name in the active user groups field represents always the principal investigator's group."); ?></p>
			
			<h3><?php echo _e('Other Options'); ?></h3>
			<?php $dont_use_ariw = (get_option('dont_use_ariw') != '') ? "checked='checked'" : ''; ?>
			<input type='checkbox' id='dont_use_ariw' name='dont_use_ariw' value='dont' <?php echo $dont_use_ariw; ?> /><label for='dont_use_ariw'><?php echo _e("Show the button to use the Ariw.org-database of worldwide scientific institution."); ?></label><br />
			
			
			
			<p class="submit"><input type="submit" class="button-primary" value="<?php _e('Save Changes') ?>" /></p>
			
			
		</form>
	</div>
	<p><?php echo _e("Provided by Biofoo-Plugin, written by Philipp Franck 2011"); ?></p>
	<?php
}


/* the power of ajax */
// functions for the picture-selector, the user-zu-projekt-hinzufügen, the institution auswählen

function all_ajax_functions() { ?>
	<script type="text/javascript" >
		function remove_user_from_project(Suser_index, Spost_index) {
			jQuery(document).ready(function($) {
				var data = {
					action: 'remove_user_from_project',
					user_index: Suser_index,
					post_index: Spost_index
					
				};
				jQuery.post(ajaxurl, data, function(response) {
					if (response!='') {
						document.getElementById('biofoobox_inside').innerHTML = response;
					}
				});
			});
		}
		
		function add_user_to_project(Spost_index) {
			jQuery(document).ready(function($) {
				var data = {
					action: 'add_user_to_project',
					user_index: document.getElementById('biofoo_userlist')[document.getElementById('biofoo_userlist').selectedIndex].value,
					post_index: Spost_index
					
				};
				jQuery.post(ajaxurl, data, function(response) {
					if (response!='') {
						document.getElementById('biofoobox_inside').innerHTML = response;
					}
				});
			});
		}
		
		function fetch_countries() {
			//var myurl = document.getElementById('myurl').value;
			document.getElementById('output').innerHTML='<span class="description">...fetching Data from ariw.org...</span>';
			//var myRequest = new ajaxObject(myurl + 'fetch_institution_data.php', spit_data);
			//myRequest.update('countryslug=slux', 'POST');
			jQuery(document).ready(function($) {
				var data = {
					action: 'fetch_countries',
					countryslug: 'slux',
					
				};
				jQuery.post(ajaxurl, data, function(response) {document.getElementById('output').innerHTML = response;});
			});
		}

		function fetch_inst() {
			//var myurl = document.getElementById('myurl').value;
			var Scountryslug = document.getElementById('countryslux')[document.getElementById('countryslux').selectedIndex].value;
			document.getElementById('output').innerHTML='<span class="description">...fetching Data from ariw.org...</span>';	
			//var myRequest = new ajaxObject(myurl + 'fetch_institution_data.php', spit_data);
			//myRequest.update('countryslug='+countryslug, 'POST');
			jQuery(document).ready(function($) {
				var data = {
					action: 'fetch_inst',
					countryslug: Scountryslug,
					
				};
				jQuery.post(ajaxurl, data, function(response) {document.getElementById('output').innerHTML = response;});
			});
		}
		

		function fill_fields() {
			document.getElementById('inst_url').value = document.getElementById('instslux')[document.getElementById('instslux').selectedIndex].value;
			document.getElementById('inst_name').value = document.getElementById('instslux')[document.getElementById('instslux').selectedIndex].innerHTML;
		}
		
		//picture selector
		function select_picture(Show, Sold_pic, Spage, Suser_id) {
			//var myurl = document.getElementById('myurl').value;
			document.getElementById('select_pic_output').innerHTML='<span class="description">...connecting to media library...</span>';
			jQuery(document).ready(function($) {
				var data = {
					action: 'select_picture',
					oldpic: Sold_pic,
					page: Spage,
					show: Show,
					user_id: Suser_id
					
				};
				jQuery.post(ajaxurl, data, function(response) {document.getElementById('select_pic_output').innerHTML = response;});
			});
		}
		
	</script>
	<?php 
}

function remove_user_from_project() {
	//global $wpdb; 
	$user_index = (int) $_POST['user_index'];
	$post_index = (int) $_POST['post_index'];
	$success = delete_post_meta($post_index, 'member', $user_index);
	/*$user = get_userdata($user_index);
	$user = rawurldecode($user->biofoo_projects);
	$user = str_replace(array("$post_index,", $post_index), '', $user);
	$success += update_usermeta($user_index, 'biofoo_projects', rawurlencode($user));*/
	biofoo_box_inside(get_post($post_index));
	die();
}

function add_user_to_project() {
	//var_dump($_POST);
	$user_index = (int) $_POST['user_index'];
	$post_index = (int) $_POST['post_index'];
	$success = add_post_meta($post_index, 'member', $user_index);
	/*$user = get_userdata($user_index);
	$user = rawurldecode($user->biofoo_projects);
	$user .= ',' . $post_ID;
	$success += update_usermeta($user_index, 'biofoo_projects', rawurlencode($user));*/
	biofoo_box_inside(get_post($post_index));
	die();
}

function fetch_it() {
	

	$lug = rawurlencode($_POST['countryslug']);
	require_once(WP_PLUGIN_DIR . '/wp4labs/ariw.org_connection.php');
	ariw_fetch($lug);
	die();
}


/* picture selector */

function echo_picture_selector($show , $picid, $page, $user_id) { //bildauswahl von php oder ajax aus aufrufbar // $show means 0 = kein bild, 1 = ein bild, 2 = bild auswahl [, -1 = default]
	//echo "$show , $picid, $page";

	$pix_per_page = 10;
	echo "<div id='pic_select0r' class='regular-text'>";
	
	if ($show == -1) {$show = 0; remove_filter('get_avatar', 'get_pafs_avatar',10,3);}//just avatar extension

	if ($show == 0) {
		echo "<div class='select0r_pic' onclick='select_picture(2, 0, 0, $user_id)'>" . get_avatar($user_id, 64, '', "gravatar.com\nnot reachable") . "</div>";
	} elseif ($show == 1) {
		echo "<div class='select0r_pic' onclick='select_picture(2, $picid, 0, $user_id)'>", wp_get_attachment_image($picid, array(64,64)), "</div>";
	} elseif ($show == 2) {
		$media = get_posts(array('post_type' => 'attachment', 'numberposts' => $pix_per_page, 'orderby' => 'title', 'offset' => $page, 'order' => 'ASC'));
		if ($page > 0) {//zurück button
			$tpage = $page - $pix_per_page;
			echo "<input type='button' class='select0r_pic' id='pic_select0r_back' onclick='select_picture(2, $picid, $tpage, $user_id)' value='back' />";
		}

		foreach($media as $medium) {
			$mime = explode('/', $medium->post_mime_type);
			$mime = $mime[0];
			if ($mime == 'image') {
				$selected = ($medium->ID == $picid) ? "select0red_pic" : '';
				echo "<div onclick='select_picture(1, {$medium->ID}, $page, $user_id) 'class='select0r_pic $selected'>", wp_get_attachment_image($medium->ID, array(64,64)), "<span class='hoverfoo'>", $medium->post_title, "</span></div>";
			}
		}
		if (count($media) >= $pix_per_page) {//weiter button
			$tpage = $page + $pix_per_page;
			echo "<input type='button' class='select0r_pic' id='pic_select0r_next' onclick='select_picture(2, $picid, $tpage, $user_id)' value='continue' />";
		}
		//no pic button
		echo "<input type='button' class='select0r_pic' onclick='select_picture(-1, 0, 0, $user_id)' value='Use\nDefault/\nGlobal\nAvatar' />";
		
	}
	echo "</div><input type='hidden' id='biofoo_picture' name='biofoo_picture' value='$picid' /><br style='clear:both' />";		
}

function select_picure_response () { //ajax response function
	$oldpic = (int) $_POST['oldpic'];
	$page = (int) $_POST['page'];
	$how = $_POST['show'];
	$user_id = (int) $_POST['user_id'];
	echo_picture_selector($how, $oldpic, $page, $user_id);
	die();
}


/* ============ Filter Functions ============== */

function get_pafs_avatar($pic, $id_or_email, $size) {
	global $wpdb;
	$user = null;
	if (is_numeric($id_or_email)) {//got ID
		$user = get_userdata($id_or_email);//echo "----[A $id_or_email]-----";
	} elseif (is_string($id_or_email)) { //got email
 		$user = get_user_by('email', $id_or_email);//echo "----[B $id_or_email]-----";
	} elseif (is_object($id_or_email)) { //got comment user object whatever foo
 		$user = get_userdata($id_or_email->user_id);//echo "----[C $id_or_email]-----";
	}
 	if (!is_object($user)) {return $pic;}
 	$user = get_biofoo($user);
 	
	if ((int) $user->biofoo_picture == 0) {return $pic;}
	
	return wp_get_attachment_image($user->biofoo_picture, array($size, $size), false, array("style"=>"height: {$size}px", 'class'=>'avatar'));
}

function give_permissions($allcaps, $cap, $args) {
	$user_id = $args[1];
	$post_id = $args[2];
	
	$post = get_post($post_id);
	if (($post_id) and ($post->post_type = 'biofoo_project')) {		//in_array('edit_projects', $cap)
		$user = get_userdata($user_id);

		$members = get_post_meta($post_id, 'member', false);
		$is_member = ($members) ? in_array($user_id, $members) : '';
		//echo "$user_id x $post_id = $is_member |";
		if($is_member != '') {
			if($user->active_group) {
				$allcaps['edit_others_posts'] = true;				
				$allcaps['delete_others_posts'] = true;	
				$allcaps['publish_posts'] = true;
				$allcaps['edit_private_posts'] = true;
				$allcaps['edit_published_posts'] = true;
				$allcaps['edit_posts'] = true;
			}
		}
		if (!$user->boss_type) {
			$allcaps['publish_posts'] = false;
		}
		
	}

	//echo $post_id;
	return $allcaps;
}


// helping hand functions to build template!

$agroups = explode("\n", get_option('active_usergroups'));
$agroups = array_map('trim', $agroups);

function get_biofoo($user) {
	global $agroups; //that seems to be ugly but saves some time
	
	$user->group = trim(rawurldecode($user->biofoo_usergroup));
	$user->active_group = (in_array($user->group, $agroups));
	$user->boss_group = ($user->group == $agroups[0]);
	$user->group_css = ($user->active_group) ? ($user->boss_group) ? 'boss' : 'active' : 'passive';
	$user->group_ranking = array_search($user->group, $agroups);
	if ($user->group_ranking === false) {$user->group_ranking = 1000;}
	
	//other fields
	$biofoo = json_decode(get_the_author_meta('bio_foo_fields', $user->ID), true);
	if (is_array($biofoo)) {
		foreach($biofoo as $poo=>$foo) {$user->$poo = rawurldecode($foo);}
	}
	$user->nice_email = str_replace(array('@'), array('{at}'), $user->user_email);
	$user->foo = 'bar';
	
	$user->guid = get_author_posts_url($user->ID);
	
	return $user;
}

function echo_project_members($post_id, $style='boxes', $allgroups=true) {
	if ($post_id == 'all') {
		$memberZ = get_users();
	} else {
		//get memberz of project
		$memberZ = get_post_meta($post_id, 'member', false);
		if (!is_array($memberZ)) {$memberZ = array($memberZ);}
		$memberZ = array_map('intval', $memberZ);
	}

	if (!$memberZ) {return null;}
	//	var_dump(count($memberZ));
	//load corresponding users
	$userZ = array();$i = 1;
	foreach ($memberZ as $member) {
		if (is_object($member)) {$member = $member->ID;}
		$user = get_userdata($member);
		
		//remove eventually deleted user!		
		if (!$user->ID) {
			delete_post_meta($post->ID, 'member', $member);
			continue;
		}
		
		$user = get_biofoo($user);
		if ($allgroups or ($user->group_css != 'passive')) {$userZ[$i++ + 1000 * $user->group_ranking] = $user;}
	}
	ksort($userZ); //sort by group ranking

	switch ($style) {
	case 'boxes':
		echo "<div class='staff'>\n";
		foreach ($userZ as $user) {
			echo "<span class='{$user->group_css}'><div class='project_member'><a href='{$user->guid}'>", get_avatar($user->ID, 48);
			echo "<div class='username'>{$user->first_name} {$user->last_name}<div class='usergroup'> ({$user->group})</div></div></a></div></span>\n";
		}
		echo "</div>\n<div class='floatkiller'></div>";
	
	break;
	
	case 'return';
		return $memberZ;
	break;

	case 'longlist': /*longlist is long! */
		echo "<div class='staff_longlist'>\n";
		$passiveones = false;
		foreach ($userZ as $kid=>$user) {
			echo "<div class='{$user->group_css}'><a href='{$user->guid}'><div class='longlist_avatar'>", get_avatar($user->ID);
			echo "</div><h2 class='username'>{$user->first_name} {$user->last_name}</h2></a>";
			
			echo "<p class='usergroup'>{$user->group}</p>";
			if (($user->ba_degree or $user->ma_degree) or $user->phd_degree) {
				echo "<h4>", _e('Academic Degrees'), "</h4><table class='user_info'>";
				if ($user->ba_degree) { echo "<tr><td>B.A.</td><td>{$user->ba_degree}</td></tr>";}
				if ($user->ma_degree) { echo "<tr><td>M.A.</td><td>{$user->ma_degree}</td></tr>";}
				if ($user->phd_degree) { echo "<tr><td>Ph.D.</td><td>{$user->phd_degree}</td></tr>";}
				echo "</table>";
			}	
			echo "<table class='user_info'><tr><td>Email:</td><td>{$user->nice_email}</td></tr></table>";
			echo "</div>\n";
			if (($user->group_ranking >= 1000) and (!$passiveones)) {
				$passiveones = true;
				echo "<div style='margin-top: 50px'><h3>Former Members</h3></div>";
			}
		}
		echo "</div>";
	break;
	
	default:
		foreach ($userZ as $user) {
			echo "<span class='{$user->group_css}'><a href='{$user->guid}'>{$user->first_name} {$user->last_name}<!--({$user->group})--></a></span>|";
		}
	break;
	}
	
}	

function the_boss($post_id) {
	$memberZ = get_post_meta($post_id, 'member', false);
	if (!is_array($memberZ)) {$memberZ = array($memberZ);}
	$bosses = array();
	foreach ($memberZ as $member) {
		$user = get_biofoo(get_userdata($member));
		if ($user->boss_group) {$bosses[] = "<a href='?author={$user->ID}'>{$user->first_name} {$user->last_name}</a>";}
	}
	echo implode(',', $bosses);
}	
	
/*function get_project_option($post_id) {
	$option = get_post_meta($post_id, '_wp_page_template', true);
	return str_replace('-biofoo_project.php', '', $option);
}*/

function list_users_projects($user_id) {
	$args = array(
		'post_type' => 'biofoo_project',
		'numberposts' => -1,
		'post_status' => null,
		'orderby' => 'title',
		'order' => 'ASC',
		'meta_query' => array(array('key' => 'member', 'value' => $user_id))
	); 
	echo "<ul>";
	$projects = get_posts($args);
	
	//echo "<pre>"; var_dump($projects); "</pre>";
	//$projects = array_unique($projects);
	$shown = array();
	foreach ($projects as $project) {
		if (!in_array($project->ID, $shown)) {
			$shown[] = $project->ID;
			echo (get_post_meta($project->ID, 'member', $user_id) != '') ? "\n\t<li><a href='{$project->guid}'>{$project->post_title}</a></li>\n" : '';	
		}
		
	}
	echo "\n</ul>";
	return count($projects);
}	
		
?>
