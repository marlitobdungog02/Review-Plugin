<?php

namespace Wp_Reviews\Dashboard;

use Wp_Reviews\Includes\Yelp;
use Wp_Reviews\Includes\Google;
use \WP_Error;
use \WP_Query;
use \DateTime;

/**
 * The admin-specific functionality of the plugin.
 *
 * @link       #
 * @since      1.0.0
 *
 * @package    Wp_Reviews
 * @subpackage Wp_Reviews/admin
 */

/**
 * The admin-specific functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the admin-specific stylesheet and JavaScript.
 *
 * @package    Wp_Reviews
 * @subpackage Wp_Reviews/admin
 * @author     # <#>
 */
class Review {

	/**
	 * The ID of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $plugin_name    The ID of this plugin.
	 */
	private $plugin_name;

	/**
	 * The version of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $version    The current version of this plugin.
	 */
	private $version;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 * @param      string    $plugin_name       The name of this plugin.
	 * @param      string    $version    The version of this plugin.
	 */
	public function __construct( $plugin_name, $version ) {

		$this->plugin_name = $plugin_name;
		$this->version = $version;

	}

	/**
	 * Creates a review custom post type
	 *
	 * @since 	1.0.0
	 * @access 	public
	 * @uses 	register_post_type()
	 */
	public static function register_cpt() {

		$cap_type 	= 'post';
		$plural 	= 'Reviews';
		$single 	= 'Review';
		$cpt_name 	= 'wpr_review';

		$opts['can_export']								= TRUE;
		$opts['capability_type']						= $cap_type;
		$opts['description']							= '';
		$opts['exclude_from_search']					= FALSE;
		$opts['has_archive']							= FALSE;
		$opts['hierarchical']							= FALSE;
		$opts['map_meta_cap']							= TRUE;
		$opts['menu_icon']								= 'dashicons-star-half';
		$opts['menu_position']							= 25;
		$opts['public']									= FALSE;
		$opts['publicly_querable']						= FALSE;
		$opts['query_var']								= TRUE;
		$opts['register_meta_box_cb']					= '';
		$opts['rewrite']								= FALSE;
		$opts['show_in_admin_bar']						= FALSE;
		$opts['show_in_menu']							= TRUE;
		$opts['show_in_nav_menu']						= FALSE;
		$opts['show_ui']								= TRUE;
		$opts['supports']								= array( 'title', 'excerpt' );
		$opts['taxonomies']								= array();
		$opts['capabilities']['delete_others_posts']	= "delete_others_{$cap_type}s";
		$opts['capabilities']['delete_post']			= "delete_{$cap_type}";
		$opts['capabilities']['delete_posts']			= "delete_{$cap_type}s";
		$opts['capabilities']['delete_private_posts']	= "delete_private_{$cap_type}s";
		$opts['capabilities']['delete_published_posts']	= "delete_published_{$cap_type}s";
		$opts['capabilities']['edit_others_posts']		= "edit_others_{$cap_type}s";
		$opts['capabilities']['edit_post']				= "edit_{$cap_type}";
		$opts['capabilities']['edit_posts']				= "edit_{$cap_type}s";
		$opts['capabilities']['edit_private_posts']		= "edit_private_{$cap_type}s";
		$opts['capabilities']['edit_published_posts']	= "edit_published_{$cap_type}s";
		$opts['capabilities']['publish_posts']			= "publish_{$cap_type}s";
		$opts['capabilities']['read_post']				= "read_{$cap_type}";
		$opts['capabilities']['read_private_posts']		= "read_private_{$cap_type}s";
		$opts['labels']['add_new']						= esc_html__( "Add New {$single}", 'wp-reviews' );
		$opts['labels']['add_new_item']					= esc_html__( "Add New {$single}", 'wp-reviews' );
		$opts['labels']['all_items']					= esc_html__( $plural, 'wp-reviews' );
		$opts['labels']['edit_item']					= esc_html__( "Edit {$single}" , 'wp-reviews' );
		$opts['labels']['menu_name']					= esc_html__( $plural, 'wp-reviews' );
		$opts['labels']['name']							= esc_html__( $plural, 'wp-reviews' );
		$opts['labels']['name_admin_bar']				= esc_html__( $single, 'wp-reviews' );
		$opts['labels']['new_item']						= esc_html__( "New {$single}", 'wp-reviews' );
		$opts['labels']['not_found']					= esc_html__( "No {$plural} Found", 'wp-reviews' );
		$opts['labels']['not_found_in_trash']			= esc_html__( "No {$plural} Found in Trash", 'wp-reviews' );
		$opts['labels']['parent_item_colon']			= esc_html__( "Parent {$plural} :", 'wp-reviews' );
		$opts['labels']['search_items']					= esc_html__( "Search {$plural}", 'wp-reviews' );
		$opts['labels']['singular_name']				= esc_html__( $single, 'wp-reviews' );
		$opts['labels']['view_item']					= esc_html__( "View {$single}", 'wp-reviews' );

		$opts['rewrite']['ep_mask']						= EP_PERMALINK;
		$opts['rewrite']['feeds']						= FALSE;
		$opts['rewrite']['pages']						= TRUE;
		$opts['rewrite']['slug']						= esc_html__( strtolower( $plural ), 'wp-reviews' );
		$opts['rewrite']['with_front']					= FALSE;

		$opts = apply_filters( 'wp-reviews-cpt-options', $opts );

		register_post_type( strtolower( $cpt_name ), $opts );

	}

	/**
	 * Undocumented function
	 *
	 * @param [type] $columns
	 * @return void
	 */
	public function reviews_table_columns( $columns ) {
		unset( $columns['date'] );
		$columns['rating'] = __( 'Rating', 'wp-reviews' );
		$columns['reviewed_by'] = __( 'Reviewed by', 'wp-reviews' );
		$columns['source'] = __( 'Source', 'wp-reviews' );
		$columns['review_date'] = __( 'Date', 'wp-reviews' );
		return $columns;
	}

	/**
	 * Undocumented function
	 *
	 * @param [type] $column
	 * @param [type] $post_id
	 * @return void
	 */
	public function reviews_table_custom_columns( $column, $post_id ) {
		$options = get_post_meta( $post_id, 'wpr_review_options', true );
		$options = json_decode( $options, true );
		if ( 'source' === $column ) echo ucfirst( $options['source'] );
		if ( 'rating' === $column ) echo $options['rating'];
		if ( 'reviewed_by' === $column ) echo $options['author'];
		if ( 'review_date' === $column ) echo '<abbr title="' . esc_attr( get_the_date( 'Y/m/d h:s:i', $post_id ) ) . '">' . esc_html( get_the_date( 'Y/m/d', $post_id ) ) . '</abbr>';
	}

	/**
	 * Add Sync Buttons to allow user to manually sync reviews for each platform.
	 *
	 * @param string $which WP_LIST_TABLE actions section. top / bottom
	 * @return void
	 */
	public function reviews_table_custom_actions( $which ) {
		$screen = get_current_screen();
		if ( 'top' === $which && 'edit-wpr_review' === $screen->id ) :

		ob_start();
			?>
			<div class="alignleft actions">
				<a href="<?php echo admin_url( 'admin-post.php?action=wpr_sync_yelp' ); ?>" class="button"><?php echo __( 'Sync Yelp', 'wp-reviews' ); ?></a>
				<a href="<?php echo admin_url( 'admin-post.php?action=wpr_sync_google' ); ?>" class="button"><?php echo __( 'Sync Google', 'wp-reviews' ); ?></a>
			</div>
			<?php
		echo ob_get_clean();
		endif;
	}

	/**
	 * Hide the visibility options of the wpr_review post type
	 * to prevent user to edit review visibility.
	 *
	 * @param object $post The current post object to be edited.
	 * @return void
	 */
	public function post_submitbox_minor_actions( $post ) {
		if ( 'wpr_review' === $post->post_type ) {
			?>
				<style>
					#submitpost .misc-pub-post-status,
					#submitpost .misc-pub-visibility {
						display:none;
					}
				</style>
			<?php
		}
	}

	/**
	 * Undocumented function
	 *
	 * @return void
	 */
	public function admin_sync_yelp() {
		$message = '';
		$status  = 'success';
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_safe_redirect( admin_url( '/edit.php?post_type=wpr_review' ) );
			exit;
		}

		$yelp = $this->sync_yelp();

		if ( is_wp_error( $yelp ) ) {
			$status  = 'error';
			$message = $this->sync_yelp()->get_error_message();
		} else {
			if ( $yelp <= 0 ) {
				$message = __( 'Latest Yelp reviews are already imported.', 'wp-reviews' );
			} else {
				$message = __( sprintf( '%u reviews imported from Yelp.', $yelp ), 'wp-reviews' );
			}
		}

		wp_safe_redirect(
			add_query_arg(
				array(
					'wpr_sync'         => urlencode( $status ),
					'wpr_sync_message' => urlencode( $message ),
				),
				admin_url( '/edit.php?post_type=wpr_review' )
			)
		);
		exit;
	}

	/**
	 * Undocumented function
	 *
	 * @return void
	 */
	public function admin_sync_google() {
		$status = 'success';
		$message = '';
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_safe_redirect( admin_url( '/edit.php?post_type=wpr_review' ) );
			exit;
		}

		$google = $this->sync_google();

		if ( is_wp_error( $google ) ) {
			$status  = 'error';
			$message = $google->get_error_message();
		} else {
			if ( $google <= 0 ) {
				$message = __( 'Latest Google reviews are already imported.', 'wp-reviews' );
			} else {
				$message = __( sprintf( '%u reviews imported from Google.', $google ), 'wp-reviews' );
			}
		}

		wp_safe_redirect(
			add_query_arg(
				array(
					'wpr_sync'         => urlencode( $status ),
					'wpr_sync_message' => urlencode( $message ),
				),
				admin_url( '/edit.php?post_type=wpr_review' )
			)
		);
		exit;

		wp_safe_redirect( admin_url( '/edit.php?post_type=wpr_review' ) );
		exit;
	}

	/**
	 * Undocumented function
	 *
	 * @return void
	 */
	public function sync_google() {
		$options = get_option( 'wpr_integration_options' );
		$reviews = null;
		$counter = 0;

		if ( empty( $options['google_api_key'] ) || empty( $options['google_place_id'] ) ) {
			return new WP_Error( 'google_api_settings', 'Google integrations fields are not set in the settings page.' );
		}

		$google = new Google( $options['google_api_key'], $options['google_place_id'] );

		$reviews = $google->get_reviews();

		if ( is_wp_error( $reviews ) ) return new WP_Error( 'google_api_response', $reviews->get_error_message() );
		if ( count( $reviews['result']['reviews'] ) <= 0 ) return new WP_Error( 'google_api_response', 'No reviews found.' );

		foreach ( $reviews['result']['reviews'] as $review ) {

			$new_review = array(
				'author'         => sanitize_text_field( $review['author_name'] ),
				'author_picture' => esc_url( $review['profile_photo_url'] ),
				'source'         => 'google',
				'source_url'     => esc_url( $review['author_url'] ),
				'rating'         => intval( $review['rating'] ),
				'excerpt'        => urlencode( wp_kses_post( $review['text'] ) ), // use urlencode for emoji support.
			);

			$date = date( 'Y/m/d H:i:s', $review['time'] );

			$new_review      = wp_json_encode( $new_review );
			$new_review_hash = md5( $new_review );

			if ( false === $this->has_duplicate( $new_review_hash ) ) {

				$post_data = array(
					'post_type'    => 'wpr_review',
					'post_title'   => 'Google Review',
					'post_excerpt' => $review['text'],
					'post_status'  => 'publish',
					'post_date'    => $date,
				);

				$review_id = wp_insert_post( $post_data, true );

				if ( ! is_wp_error( $review_id ) ) {
					update_post_meta( $review_id, 'wpr_review_options', $new_review );
					update_post_meta( $review_id, 'wpr_review_hash', $new_review_hash );
					update_post_meta( $review_id, 'wpr_review_status', 'declined' );
					update_post_meta( $review_id, 'wpr_review_rating', intval( $review['rating'] ) );
				}
				$counter++;
			}
		}

		return $counter;
	}

	public function sync_yelp() {
		$options = get_option( 'wpr_integration_options' );
		$reviews = null;
		$counter = 0;

		if ( empty( $options['yelp_business_id'] ) || empty( $options['yelp_api_key'] ) ) {
			return new WP_Error( 'yelp_api_settings', 'Yelp integrations fields are not set in the settings page.' );
		}

		$yelp = new Yelp( $options['yelp_api_key'], $options['yelp_business_id'] );

		$reviews = $yelp->get_reviews();

		if ( is_wp_error( $reviews ) ) return new WP_Error( 'yelp_api_response', $reviews->get_error_message() );

		foreach ( $reviews['reviews'] as $review ) {
			// Get the required data to check for a duplicate hash.
			$date = new \DateTime( $review['time_created'] );

			$new_review = array(
				'author'         => sanitize_text_field( $review['user']['name'] ),
				'author_picture' => esc_url( $review['user']['image_url'] ),
				'source'         => 'yelp',
				'source_url'     => esc_url( $review['url'] ),
				'rating'         => intval( $review['rating'] ),
				'excerpt'        => urlencode( wp_kses_post( $review['text'] ) ), // use url encode to support emoji.
			);

			$new_review      = wp_json_encode( $new_review );
			$new_review_hash = md5( $new_review );

			if ( false === $this->has_duplicate( $new_review_hash ) ) {

				$post_data = array(
					'post_type'     => 'wpr_review',
					'post_title'    => __( 'Yelp Review', 'wp-reviews' ),
					'post_excerpt'  => $review['text'],
					'post_status'   => 'publish',
					'post_date'     => $date->format('Y/m/d H:i:s'),
				);

				$review_id = wp_insert_post( $post_data, true );

				if ( ! is_wp_error( $review_id ) ) {
					update_post_meta( $review_id, 'wpr_review_options', $new_review );
					update_post_meta( $review_id, 'wpr_review_hash', $new_review_hash );
					update_post_meta( $review_id, 'wpr_review_status', 'declined' );
					update_post_meta( $review_id, 'wpr_review_rating', intval( $review['rating'] ) );
				}
				$counter++;
			}
		}
		return $counter;
	}

	/**
	 * Update reviews twice daily using WP CRON.
	 *
	 * @return void
	 */
	public function cron_sync_reviews() {
		$this->sync_yelp();
		$this->sync_google();
	}

	/**
	 * Check the database if we have a duplicate hash to avoid
	 * adding a duplicate review.
	 *
	 * @param string hash The review hash to be added.
	 * @return boolean
	 */
	public function has_duplicate( $hash ) {
		global $wpdb;
		$hash_exists = $wpdb->get_results( $wpdb->prepare( "SELECT meta_id FROM $wpdb->postmeta WHERE meta_value = '%s'", $hash) );
		if ( count( $hash_exists ) > 0 ) {
			return true;
		} else {
			return false;
		}
	}

	/**
	 * Hide certain elements in wpr_review list table using css.
	 *
	 * @return void
	 */
	public function admin_footer() {
		$screen = get_current_screen();
		if ( 'edit-wpr_review' !== $screen->id ) return;
		?>
			<style>
				.wp-list-table .inline-edit-status,
				.wp-list-table .inline-edit-date + .clear + .inline-edit-group {
					display: none;
				}
			</style>
		<?php
	}

	/**
	 * Make the default view of the review table in excerpt to show 
	 * the review excerpt below the title.
	 *
	 * @param string default The default settings for the WP_LIST_TABLE
	 * @return string
	 */
	public function reviews_table_list_mode( $default ) {
		$screen = get_current_screen();

		if ( 'edit-wpr_review' !== $screen->id ) return $default;

		return 'excerpt';
	}

	/**
	 * Delete the postmeta upon deletion of the revew.
	 * To avoid getting false positive if an non-existing review has a duplicate hash.
	 *
	 * @param int review_id The id of the review to be deleted.
	 * @return void
	 */
	public function delete_custom_post_meta( $review_id ) {
		delete_post_meta( $review_id, 'wpr_review_options' );
		delete_post_meta( $review_id, 'wpr_review_hash' );
		delete_post_meta( $review_id, 'wpr_review_status' );
		delete_post_meta( $review_id, 'wpr_review_rating' );
		delete_post_meta( $review_id, 'wpr_review_invitation' );
	}

	/**
	 * Add WP Reviews admin notices query parameters for admin notices.
	 *
	 * @param array $array List of removable query args
	 * @return array $array Filtered list of removable query args
	 */
	public function removable_query_args( $array ) {
		$array[] = 'wpr_sync';
		$array[] = 'wpr_sync_message';
		$array[] = 'wpr_bulk_status';
		$array[] = 'wpr_bulk_status_count';
		return $array;
	}

	/**
	 * Handle custom admin notices for syncing yelp or google reivews 
	 * in the wpr_review list table.
	 *
	 * @return void
	 */
	public function adimn_notice_sync_reviews() {
		$screen = get_current_screen();
		if ( 'edit-wpr_review' !== $screen->id ) return;
		if ( ! isset( $_GET['wpr_sync'] ) || empty( $_GET['wpr_sync'] ) )  return;
		if ( ! isset( $_GET['wpr_sync_message'] ) && empty( $_GET['wpr_sync_message'] ) ) return;

		$status  = sanitize_text_field( wp_unslash( $_GET['wpr_sync'] ) );		
		$message = sanitize_text_field( wp_unslash( urldecode( $_GET['wpr_sync_message'] ) ) );

		if ( 'success' === $status  ) :
			?>
			<div class="notice notice-success is-dismissible">
				<p><?php _e( $message, 'wp-reviews' ); ?></p>
			</div>
			<?php
		endif;

		if ( 'error' === $status ) :
			?>
			<div class="notice notice-error is-dismissible">
				<p><?php _e( $message, 'wp-reviews' ); ?></p>
			</div>
			<?php
		endif;
	}

	/**
	 * Handle admin notices for review approved and declined bulk actions
	 *
	 * @return void
	 */
	public function adimn_notice_bulk_actions() {
		$screen = get_current_screen();
		if ( 'edit-wpr_review' !== $screen->id ) return;
		if ( ! isset( $_GET['wpr_bulk_status'] ) || empty( $_GET['wpr_bulk_status'] ) )  return;
		if ( ! isset( $_GET['wpr_bulk_status_count'] ) || empty( $_GET['wpr_bulk_status_count'] ) )  return;
		$status = sanitize_text_field( wp_unslash( $_GET['wpr_bulk_status'] ) );
		$count = intval( wp_unslash( $_GET['wpr_bulk_status_count'] ) );
		$reviews = ( $count > 1 ) ? __( 'reviews', 'wp-reviews' ) : __( 'review', 'wp-reviews' );
		?>
		<div class="notice notice-success is-dismissible">
			<p><?php echo wp_sprintf( __('%d %s %s.', 'wp-reviews'), $count, $reviews, $status ); ?></p>
		</div>
		<?php
	}

	/**
	 * Filters the default post display states used in the posts list table.
	 * Add the review status in the post list table.
	 * 
	 * @return void
	 */
	public function display_post_states( $post_states, $post ) {

		if ( 'wpr_review' === $post->post_type ) {
			$status = get_post_meta( $post->ID , 'wpr_review_status', true );
			if ( 'declined' === $status ) {
				return array( 'Declined' );
			} else {
				return array( 'Approved' );
			}
		}

		return $post_states;
	}

	/**
	 * Filters the list table Bulk Actions drop-down
	 * to remove the edit bulk action and add approved and disapproved bulk actions.
	 *
	 * @return $actions array of actions.
	 */
	public function bulk_actions_edit_wpr_review( $actions ) {
		unset( $actions['edit'] );
		$actions['approved'] = 'Approved';
		$actions['declined'] = 'Declined';
		return $actions;
	}

	/**
	 * Handle custom bulk actions for wpr_review list table.
	 *
	 * @param [type] $redirect_to
	 * @param [type] $action_name
	 * @param [type] $post_ids
	 * @return string URL to redirect.
	 */
	public function handle_bulk_actions_edit_wpr_review( $redirect_to, $action_name, $post_ids ) {
		if ( 'approved' === $action_name ) {
			foreach ( $post_ids as $post_id ) { 
				update_post_meta( $post_id, 'wpr_review_status', 'approved' );
			} 
			$redirect_to = add_query_arg( array(
				'wpr_bulk_status' => 'approved',
				'wpr_bulk_status_count' => count( $post_ids ),
			), $redirect_to ); 
			return $redirect_to; 
		}

		if ( 'declined' === $action_name ) {
			foreach ( $post_ids as $post_id ) { 
				update_post_meta( $post_id, 'wpr_review_status', 'declined' );
			} 
			$redirect_to = add_query_arg( array(
				'wpr_bulk_status' => 'declined',
				'wpr_bulk_status_count' => count( $post_ids ),
			), $redirect_to ); 
			return $redirect_to;
		}
	}

	/**
	 * Change the admin notice of wpr_review post type 
	 * to be align with the custom post type name.
	 *
	 * @return void
	 */
	public function review_admin_notice_update( $messages ) {
		global $post, $post_ID;
		$messages['wpr_review'] = array(
			0  => '', // Unused. Messages start at index 1.
			1  => __( 'Review updated.', 'wp-reviews' ),
			2  => '',
			3  => '',
			4  => __( 'Reviews updated.', 'wp-reviews' ),
			5  => isset( $_GET['revision'] ) ? sprintf( __( 'Review restored to revision from %s', 'wp-reviews' ), wp_post_revision_title( (int) $_GET['revision'], false ) ) : false,
			6  => __( 'Review published.', 'wp-reviews' ),
			7  => __( 'Review saved.', 'wp-reviews' ),
			8  => '',
			9  => '',
			10 => '',
		);
		return $messages;
	}

	/**
	 * Filter the list of available wpr_review list table views.
	 *
	 * @param array $views An array of available list table views.
	 * @return array $views An array of filtered available list table views.
	 */
	public function review_table_custom_filter( $views ) {
		global $wpdb;

		unset( $views['publish'] );
		unset( $views['mine'] );

		$review_table_url = admin_url( 'edit.php?post_type=wpr_review' );

		$approved_count = $wpdb->get_var( "SELECT COUNT(*) FROM $wpdb->posts INNER JOIN $wpdb->postmeta ON ( $wpdb->posts.ID = $wpdb->postmeta.post_id ) WHERE 1=1 AND ( ( $wpdb->postmeta.meta_key = 'wpr_review_status' AND $wpdb->postmeta.meta_value = 'approved' ) ) AND $wpdb->posts.post_type = 'wpr_review' AND (($wpdb->posts.post_status = 'publish'))" );
		$declined_count = $wpdb->get_var( "SELECT COUNT(*) FROM $wpdb->posts INNER JOIN $wpdb->postmeta ON ( $wpdb->posts.ID = $wpdb->postmeta.post_id ) WHERE 1=1 AND ( ( $wpdb->postmeta.meta_key = 'wpr_review_status' AND $wpdb->postmeta.meta_value = 'declined' ) ) AND $wpdb->posts.post_type = 'wpr_review' AND (($wpdb->posts.post_status = 'publish'))" );

		$high_rating_count = $wpdb->get_var( "SELECT COUNT(*) FROM $wpdb->posts INNER JOIN $wpdb->postmeta ON ( $wpdb->posts.ID = $wpdb->postmeta.post_id ) WHERE 1=1 AND ( ( $wpdb->postmeta.meta_key = 'wpr_review_rating' AND $wpdb->postmeta.meta_value >= '4' ) ) AND $wpdb->posts.post_type = 'wpr_review' AND (($wpdb->posts.post_status = 'publish'))" );
		$low_rating_count  = $wpdb->get_var( "SELECT COUNT(*) FROM $wpdb->posts INNER JOIN $wpdb->postmeta ON ( $wpdb->posts.ID = $wpdb->postmeta.post_id ) WHERE 1=1 AND ( ( $wpdb->postmeta.meta_key = 'wpr_review_rating' AND $wpdb->postmeta.meta_value <= '3' ) ) AND $wpdb->posts.post_type = 'wpr_review' AND (($wpdb->posts.post_status = 'publish'))" );

		$invitaion_count = $wpdb->get_var( "SELECT COUNT(*) FROM $wpdb->posts INNER JOIN $wpdb->postmeta ON ( $wpdb->posts.ID = $wpdb->postmeta.post_id ) WHERE 1=1 AND ( ( $wpdb->postmeta.meta_key = 'wpr_review_invitation' AND $wpdb->postmeta.meta_value = 1 ) ) AND $wpdb->posts.post_type = 'wpr_review' AND (($wpdb->posts.post_status = 'publish'))" );

		$approved_current = '';
		$high_current = '';
		$low_current = '';
		$declined_current = '';
		$invitation_current = '';

		if ( isset( $_GET['wpr_review_status'] ) && ! empty( $_GET['wpr_review_status'] ) ) {
			$status = sanitize_text_field( wp_unslash( $_GET['wpr_review_status'] ) );
			if ( 'approved' === $status ) {
				$approved_current = 'class="current"';
			}
			if ( 'declined' === $status ) {
				$declined_current = 'class="current"';
			}
		}

		if ( isset( $_GET['wpr_review_rating'] ) && ! empty( $_GET['wpr_review_rating'] ) ) {
			$rating = sanitize_text_field( wp_unslash( $_GET['wpr_review_rating'] ) );
			if ( 'high' === $rating ) {
				$high_current = 'class="current"';
			}
			if ( 'low' === $rating ) {
				$low_current = 'class="current"';
			}
		}

		if ( isset( $_GET['wpr_review_type'] ) && ! empty( $_GET['wpr_review_type'] ) ) {
			$review_type = sanitize_text_field( wp_unslash( $_GET['wpr_review_type'] ) );
			if ( 'invitation' === $review_type ) {
				$invitation_current = 'class="current"';
			}
		}

		$views['wpr_approved'] = '<a href="' . esc_url( add_query_arg( 'wpr_review_status', 'approved', $review_table_url ) ) . '" ' . $approved_current . '>Approved <span class="count">(' . esc_html( $approved_count ) . ')</span></a>';
		$views['wpr_declined'] = '<a href="' . esc_url( add_query_arg( 'wpr_review_status', 'declined', $review_table_url ) ) . '" ' . $declined_current . '>Declined <span class="count">(' . esc_html( $declined_count ) . ')</span></a>';
		$views['wpr_high_rating'] = '<a href="' . esc_url( add_query_arg( 'wpr_review_rating', 'high', $review_table_url ) ) . '" ' . $high_current . '>High Rating <span class="count">(' . esc_html( $high_rating_count ) . ')</span></a>';
		$views['wpr_low_rating'] = '<a href="' . esc_url( add_query_arg( 'wpr_review_rating', 'low', $review_table_url ) ) . '" ' . $low_current . '>Low Rating <span class="count">(' . esc_html( $low_rating_count ) . ')</span></a>';
		$views['wpr_invitation'] = '<a href="' . esc_url( add_query_arg( 'wpr_review_type', 'invitation', $review_table_url ) ) . '" ' . $invitation_current . '>Invitation <span class="count">(' . esc_html( $invitaion_count ) . ')</span></a>';
		return $views;
	}

	/**
	 * Undocumented function
	 *
	 * @param [type] $query
	 * @return void
	 */
	public function pase_query_reviews_table( $query ) {
		global $pagenow;
		$post_type = (isset($_GET['post_type'])) ? sanitize_text_field( wp_unslash( $_GET['post_type'] ) ) : 'post';

		if ( $post_type == 'wpr_review' && $pagenow=='edit.php' && isset($_GET['wpr_review_status']) && !empty($_GET['wpr_review_status']) ) {
			$query->query_vars['meta_key'] = 'wpr_review_status';
			$query->query_vars['meta_value'] = sanitize_text_field( wp_unslash( $_GET['wpr_review_status'] ) );
		}

		if ( $post_type == 'wpr_review' && $pagenow=='edit.php' && isset($_GET['wpr_review_rating']) && !empty($_GET['wpr_review_rating']) ) {
			$rating = sanitize_text_field( wp_unslash( $_GET['wpr_review_rating'] ) );
			if ( 'high' === $rating ) {
				$query->query_vars['meta_key'] = 'wpr_review_rating';
				$query->query_vars['meta_value'] = 4;
				$query->query_vars['meta_compare'] = '>=';
			} 

			if ( 'low' === $rating ) {
				$query->query_vars['meta_key'] = 'wpr_review_rating';
				$query->query_vars['meta_value'] = 3;
				$query->query_vars['meta_compare'] = '<=';
			}
		}

		if ( $post_type == 'wpr_review' && $pagenow=='edit.php' && isset($_GET['wpr_review_rating_filter']) && !empty($_GET['wpr_review_rating_filter']) ) {
			$query->query_vars['meta_key'] = 'wpr_review_rating';
			$query->query_vars['meta_value'] = intval( wp_unslash( $_GET['wpr_review_rating_filter'] ) );
		}

		if ( $post_type == 'wpr_review' && $pagenow=='edit.php' && isset($_GET['wpr_review_type']) && !empty($_GET['wpr_review_type']) ) {
			$query->query_vars['meta_key'] = 'wpr_review_invitation';
			$query->query_vars['meta_value'] = 1;
		}

	}

	/**
	 * Undocumented method
	 *
	 * @param [type] atts
	 * @return void
	 */
	public function review_table_rating_filter() {
		$post_type = (isset($_GET['post_type'])) ? sanitize_text_field( wp_unslash( $_GET['post_type'] ) ) : 'post';
		if ($post_type == 'wpr_review'){
			//query database to get a list of years for the specific post type:
			$values = array(
				'5' => 5,
				'4' => 4,
				'3' => 3,
				'2' => 2,
				'1' => 1,
			);

			?><select name="wpr_review_rating_filter">
				<option value="">Filter by rating</option>
				<?php 
				$current_v = isset($_GET['wpr_review_rating_filter'])? $_GET['wpr_review_rating_filter'] : '';
				foreach ($values as $label => $value) {
					printf(
						'<option value="%s"%s>%s</option>',
						$value,
						$value == $current_v? ' selected="selected"':'',
						$label
					);
				}
				?>
			</select>
			<?php
		}
	}
}