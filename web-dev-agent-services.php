<?php
/*
Plugin Name: Web Dev Agent - Services
Plugin URI: 
Description: Display Web Agency Services
Version: 1.0.0
Author: edk
Author URI: evolutiondesuka.com
*/

// ensure application access only
if( !defined('ABSPATH') ) {
   exit;
}


class WedDevAgentServices {

	public function __construct() {

      // create custom post type 'wda_service'
      add_action( 'init', array($this,'create_service_post_type' ));

      // assets
      add_action('wp_enqueue_scripts',array($this,'enqueue_assets'));
      add_action('admin_enqueue_scripts', array($this,'enqueue_admin_assets'));

      // 'edit post' page
		add_action('add_meta_boxes', array( $this,'add_service_meta_box')); 
		add_action('save_post',array($this,'save_custom_meta'));

      // front-end UI
      add_shortcode('services',array($this,'shortcode_html'));

   }


   //
   // create custom post type 'wda_service'
   //
   public function create_service_post_type() {

      $labels = array(
         'name' => __('WDA Services','web-dev-agent'),
         'singular_name' =>  __('WDA Service','web-dev-agent'),
         'menu_name' => 'Services',
      );
      $args = array(
         'labels' => $labels,
         'description' => 'Service Custom Post Type',
         'supports' => array('title','editor','thumbnail'),
         'hierarchical' => true,
         'taxonomies' => array('category'),
         'public' => true,
         'show_ui' => true,
         'show_in_menu' => true,
         'show_in_nav_menus' => true,
         // 'show_in_rest' => true, // in the REST API. Set this to true for the post type to be available in the block editor.
         'has_archive' => true,
         'rewrite' => array( 'slug' => 'service' ),  // custom slug
         'exclude_from_search' => true,
         'publicly_queryable' => true,    // false will exclude archive- and single- templates
         'menu_icon' => 'dashicons-media-text',
      );
      register_post_type('wda_service',$args);
   }


   //
   // assets
   //
   public function enqueue_assets() {
      
   }
   public function enqueue_admin_assets() { 
      wp_register_style('wda_custom_wp_admin_css',plugin_dir_url( __FILE__ ) . 'css/wda-admin-style.css',array(),1,'all'); 
      wp_enqueue_style( 'wda_custom_wp_admin_css' );
   }
   

   //
   // 'edit post' page
   //
	public function add_service_meta_box( $post_type ) {

		// Limit meta box to certain post types
		$post_types = array( 'wda_service' );

		if ( in_array( $post_type, $post_types ) ) {

			add_meta_box(
				'wda_service',
				__( 'Tagline', 'textdomain' ),
				array( $this, 'render_service_meta_box' ),
				$post_types,
				'advanced',
				'high'
			);
		}
	}

   public function render_service_meta_box($post) {

		wp_nonce_field('wda_services_meta_box','wda_services_meta_nonce');

		$tagline = get_post_meta( $post->ID, 'wda_service_tagline', true );
		$url = get_post_meta( $post->ID, 'wda_service_url', true );

      // to do : depr/orig below used this - necessary?
      // <label for="wda_service_custom_metabox_tagline">tagline
      // <label for="wda_service_custom_metabox_url">url
      ?>

      <label class="wda_label">
         <span class="wda_title">Tagline</span>
            <input
               id="wda_service_tagline_field"
               name="wda_service_tagline_field"
               class="wda_input"
               type="text"
               value="<?php echo $tagline; ?>">
      </label>
      <label class="wda_label">
         <span class="wda_title">URL</span>
            <input
               id="wda_service_url_field"
               name="wda_service_url_field"
               class="wda_input"
               type="text"
               value="<?php echo $url; ?>">
      </label> 

         
      <?php
   }

	public function save_custom_meta($post_id) {

      //if (isset($_POST)) die(print_r($_POST));     // debug

		if ( ! isset( $_POST['wda_services_meta_nonce'] ) ) {
			return $post_id;
		}
		$nonce = $_POST['wda_services_meta_nonce'];
		if ( ! wp_verify_nonce( $nonce, 'wda_services_meta_box' ) ) {
			return $post_id;
		}

		// autosave, our form has not been submitted
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return $post_id;
		}

		// Check the user's permissions.
      if (!current_user_can('edit_page',$post_id)) {
         return $post_id;
      }

		// Sanitize the user input
		$tagline = sanitize_text_field( $_POST['wda_service_tagline_field'] );
		$url = sanitize_text_field( $_POST['wda_service_url_field'] );
      // if (isset($_POST)) die(print_r('listen'));     // debug

		// Update the meta fields
		update_post_meta( $post_id, 'wda_service_tagline', $tagline);
		update_post_meta( $post_id, 'wda_service_url', $url );
	}


   //
   // front-end UI - shortcode
   //
   public function shortcode_html() {

      ob_start(); // buffer output

      $args = array(
         'post_type' => 'wda_service',
         'posts_per_page' => 10,
      );
      $loop = new WP_Query($args);

      ?>
      <section class="feature_tiles bg_white">
         <h3>Services</h3>
         <ul>
         <?php
         while ( $loop->have_posts() ) {
            $loop->the_post();
               ?>
               <li>
                  <?php if(has_post_thumbnail()):?>
                     <img src="<?php the_post_thumbnail_url('medium'); ?>"/>
                  <?php endif;?>
                  <h3><?php echo get_the_title();?></h3>
                  <div class="feature_tile_content">
                     <p><?php echo get_the_excerpt();?></p>
                  </div>
                  <button><a href="<?php echo get_permalink(get_the_ID()); ?>">read more</a></button>

               </li>
            <?php
         }
         ?>
         </ul>
      </section>
      <?php

      $buffered_data = ob_get_clean();    // return buffered output
      return $buffered_data;
   }

}


new WedDevAgentServices;