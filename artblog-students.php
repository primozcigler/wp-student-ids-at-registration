<?php
/**
 * @package Artblog_Students
 * @version 1.0
 */
/*
Plugin Name: Artblog Students
Plugin URI: http://www.proteusthemes.com
Description: Plugin made for Artblog.
Author: Primoz Cigler
Version: 1.1
Author URI: http://www.proteusnet.com
Text domain: artblog_students
*/


/**
 * Registration form
 */

//1. Add a new form element...
add_action('register_form','artblog_register_form');
function artblog_register_form (){
	$student_id = ( isset( $_POST['student_id'] ) ) ? $_POST['student_id']: '';
	?>
	<p>
		<label for="student_id"><?php _e('Student ID number','artblog_students') ?><br />
			<input type="text" name="student_id" id="student_id" class="input" value="<?php echo esc_attr(stripslashes($student_id)); ?>" size="25" /></label>
	</p>
	<?php
}

function get_array_of_user_IDs() {
	$out = array();

	$all_ids = new WP_Query( array(
		'post_type'      => 'artblog_student_ids',
		'nopaging'       => true,
		'posts_per_page' => -1,
		'meta_key'       => 'already_used',
		'meta_value'     => 'no',
	) );

	while ( $all_ids->have_posts() ) {
		$all_ids->the_post();
		$out[] = trim( get_the_title() );
	}
	wp_reset_postdata();

	return $out;
}

//2. Add validation. In this case, we make sure student_id is required.
add_filter('registration_errors', 'artblog_registration_errors', 10, 3);
function artblog_registration_errors ($errors, $sanitized_user_login, $user_email) {

	$student_ids = get_array_of_user_IDs();

	if ( ! is_array( $student_ids ) || ! in_array( trim( $_POST['student_id'] ), $student_ids ) ) {
		$errors->add( 'student_id_error', __('<strong>ERROR</strong>: You must include a valid student ID number.','artblog_students') );
	}

	return $errors;
}

//3. Finally, save our extra registration user meta.
add_action('user_register', 'artblog_user_register');
function artblog_user_register ($user_id) {
	global $wpdb;

	if ( isset( $_POST['student_id'] ) ) {
		update_user_meta( $user_id, 'student_id', $_POST['student_id'] );

		$post_id = $wpdb->get_var( sprintf( "SELECT ID FROM $wpdb->posts WHERE post_title = '%s'", $_POST['student_id'] ) );
		update_post_meta( $post_id, 'already_used', 'yes' );
	}
}


/**
 * Custom post type for student IDs
 */
function artblog_custom_post_types() {
	$labels = array(
		'name'               => __( 'Student IDs', 'artblog_students' ),
		'singular_name'      => __( 'Student ID', 'artblog_students' ),
		'add_new'            => __( 'Add New', 'artblog_students' ),
		'add_new_item'       => __( 'Add New Student ID', 'artblog_students' ),
		'edit_item'          => __( 'Edit Student ID', 'artblog_students' ),
		'new_item'           => __( 'New Student ID', 'artblog_students' ),
		'all_items'          => __( 'All Student IDs', 'artblog_students' ),
		'view_item'          => __( 'View Student ID', 'artblog_students' ),
		'search_items'       => __( 'Search Student IDs', 'artblog_students' ),
		'not_found'          => __( 'No Student IDs found', 'artblog_students' ),
		'not_found_in_trash' => __( 'No Student IDs found in Trash', 'artblog_students' ),
		'menu_name'          => __( 'Student IDs', 'artblog_students' ),
	);
	$args = array(
		'labels'              => $labels,
		'public'              => false,
		'show_ui'             => true,
		'show_in_menu'        => true,
		'query_var'           => false,
		'capability_type'     => 'post',
		'has_archive'         => false,
		'hierarchical'        => false,
		'supports'            => array( 'title' )
	);
	register_post_type( 'artblog_student_ids', $args );
}
add_action( 'init', 'artblog_custom_post_types' );






/**
 * Save post metadata when a post is saved.
 *
 * @param int $post_id The ID of the post.
 */
function save_artblog_postmeta( $post_id ) {

	/*
	 * In production code, $slug should be set only once in the plugin,
	 * preferably as a class property, rather than in each function that needs it.
	 */
	$slug = 'artblog_student_ids';

	// If this isn't a 'artblog_student_ids' post, don't update it.
	if ( $slug != $_POST['post_type'] ) {
		return;
	}

	// - Update the post's metadata.

	$current = get_post_meta( $post_id, 'already_used', true );
	if ( empty( $current ) ) {
		update_post_meta( $post_id, 'already_used', 'no' );
	} else {
		update_post_meta( $post_id, 'already_used', $current );
	}
}
add_action( 'save_post', 'save_artblog_postmeta' );



/**
 * Redirect non-admins to the homepage after logging into the site.
 *
 * @since   1.0
 */
function soi_login_redirect( $redirect_to, $request, $user  ) {
	return ( is_array( $user->roles ) && in_array( 'administrator', $user->roles ) ) ? admin_url() : site_url();
} // end soi_login_redirect
add_filter( 'login_redirect', 'soi_login_redirect', 10, 3 );



/**
 * Loading the textdomain
 */
function artblog_students_init() {
	load_plugin_textdomain( 'artblog_students', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
}
add_action('plugins_loaded', 'artblog_students_init');



// custom admin login logo
function artblog_custom_login_logo() {
	echo '<style type="text/css">
	#login h1 a { background-image: url(' . plugin_dir_url( __FILE__ ) . 'arthouse-logo.png); background-size: 198px 120px; margin: 0 0 5px 0; width: 100%; height: 120px; }
	#login #nav {font-size: 1.5em; font-width: 700;}
	</style>';
}
add_action('login_head', 'artblog_custom_login_logo');