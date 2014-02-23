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
Version: 1.0
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
		$out[] = intval( get_the_title() );
	}
	wp_reset_postdata();

	return $out;
}

//2. Add validation. In this case, we make sure student_id is required.
add_filter('registration_errors', 'artblog_registration_errors', 10, 3);
function artblog_registration_errors ($errors, $sanitized_user_login, $user_email) {

	$student_ids = get_array_of_user_IDs();

	if ( ! in_array( intval( $_POST['student_id'] ), $student_ids ) ) {
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
		'name'               => __( 'Student IDs' ),
		'singular_name'      => __( 'Student ID' ),
		'add_new'            => __( 'Add New' ),
		'add_new_item'       => __( 'Add New Student ID' ),
		'edit_item'          => __( 'Edit Student ID' ),
		'new_item'           => __( 'New Student ID' ),
		'all_items'          => __( 'All Student IDs' ),
		'view_item'          => __( 'View Student ID' ),
		'search_items'       => __( 'Search Student IDs' ),
		'not_found'          => __( 'No Student IDs found' ),
		'not_found_in_trash' => __( 'No Student IDs found in Trash' ),
		'menu_name'          => __( 'Student IDs' ),
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