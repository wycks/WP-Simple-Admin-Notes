<?php
/*
Plugin Name: Simple Admin Notes
Plugin URI: https://github.com/wycks/Simple-Admin-Notes
Description: Adds a simple Notes section to admin areas
Author: Wycks
Author URI: http://wordpress.org/extend/plugins/profile/wycks
Version: 1.2.0
License: GPL2
*/

		// don't load directly
	 if ( !defined('ABSPATH') )
			die('Nope');


		/**
		* Enqueue jQuery UI scripts
		*/
		function default_scripts_san() { 
			wp_enqueue_script( 'jquery' );
			wp_enqueue_script( 'jquery-ui-tabs' );
			wp_enqueue_script( 'jquery-ui-core' );
		}
		add_action( 'admin_enqueue_scripts',  'default_scripts_san' );


		/**
		*  Add CSS
		*/ 
		function my_admin_head_san() {
			wp_enqueue_style( 'style-san', plugins_url('simple-notes.css', __FILE__));
		}
		add_action( 'admin_enqueue_scripts', 'my_admin_head_san' );


		/**
		* Register CPT that is only available in the admin area called "Notes"
		*/
		function register_cpt_san() {

				$labels = array( 
						'name'               => _x( 'notes', 'note' ),
						'singular_name'      => _x( 'note', 'note' ),
						'add_new'            => _x( 'New Note', 'note' ),
						'add_new_item'       => _x( 'New note', 'note' ),
						'edit_item'          => _x( 'Edit note', 'note' ),
						'new_item'           => _x( 'New note', 'note' ),
						'view_item'          => _x( 'View note', 'note' ),
						'search_items'       => _x( 'Search notes', 'note' ),
						'not_found'          => _x( 'No notes found', 'note' ),
						'not_found_in_trash' => _x( 'No notes found in Trash', 'note' ),
						'parent_item_colon'  => _x( 'Parent note:', 'note' ),
						'menu_name'          => _x( 'Notes', 'Note' ),
				);

				$args = array( 
						'labels'               => $labels,
						'hierarchical'         => true,
						'description'          => 'Registers a notes section for Admin only',
						'supports'             => array( 'title', 'editor', 'author',  'revisions' ),
						'public'               => false,
						'show_ui'              => true,
						'show_in_menu'         => true,
						'menu_position'        => 10,
						'show_in_nav_menus'    => true,
						'publicly_queryable'   => false,
						'exclude_from_search'  => true,
						'has_archive'          => false,
						'query_var'            => true,
						'can_export'           => true,
						'rewrite'              => false,
						'register_meta_box_cb' => 'add_notes_metaboxes_san',
						'capability_type'      => 'page'
				);
				register_post_type( 'note', $args );
		}
		add_action( 'init', 'register_cpt_san' );

		
		/**
		*  Adds "Display Notes" meta box to notes edit page on the side
		*/
		function add_notes_metaboxes_san(){
			add_meta_box( 'wpt_notes_location', 'Display Notes', 'wpt_notes_san', 'note', 'side', 'default' );
		}


		/**
		*  Meta box added to side of Notes CPT
		*
		* @param string $placement_above Placement above editor
		* @param string $placement_below Placement below editor
		* @param string $placement_yes Placement
		* @param string $location Placement location
		*/
		function wpt_notes_san($post) {
	 
			wp_nonce_field( basename( __FILE__ ), 'notes_san_noncename' );

			$placement_above = get_post_meta( $post->ID, 'note_placement_above', true );
			$placement_below = get_post_meta( $post->ID, 'note_placement_below', true );
			$placement_yes   = get_post_meta( $post->ID, 'note_placement_yes', true );
			$location        = get_post_meta( $post->ID, 'note_ids', true );
			
			// the form
			echo '<form>';
			echo '<input type="text" name="notes-location" value="' . esc_attr( $location )  . '" class="widefat"><br>';
			echo 'Please enter the post ids above <br><br>';

			if ( $placement_above == '1' ){
				echo '<input type="checkbox" id="san-above" name="notes-check-a" value="1" checked="yes"> Set above editor <br>';
			}else{
				echo '<input type="checkbox" id="san-above" name="notes-check-a" value="1"> Set above editor <br>';
			}

			if ( $placement_below == '1' ){
				echo '<input type="checkbox" id="san-below" name="notes-check-b" value="1" checked="yes"> Set below editor <br><br>';
			}else{
				echo '<input type="checkbox" id="san-below" name="notes-check-b" value="1" > Set below editor <br><br>';
			}

			if ( $placement_yes == '1' ){
				echo '<input type="checkbox" name="notes-set" value="1" checked="yes"> Do not show in the default notes section<br>';
			}else{
				echo '<input type="checkbox" name="notes-set" value="1"> Do not show in the default notes section<br>';
			}  
			echo '</form>';
		}


		/**
		*  Save post action hook for "Display Notes" options
		*/ 
		function wpt_savehook_san(){    
			add_action( 'save_post', 'wpt_save_san', 10, 2 );
		}
		add_action( 'load-post.php', 'wpt_savehook_san' );
		add_action( 'load-post-new.php', 'wpt_savehook_san' );


		/**
		*  Save post meta box data from $_POST
		*
		* @param string $new_meta_value  notes-location
		* @param string $new_placement_above above editor
		* @param string $new_placement_below below editor
		* @param string $new_placement_set visible in defualt notes section
		* 
		* @param string $arr1  validate input
		* @param string $arr2  validate input
		* @param string $arr3  validate input
		*/
		function wpt_save_san($post_id, $post ){

			if ( !isset( $_POST['notes_san_noncename'] ) || !wp_verify_nonce( $_POST['notes_san_noncename'], basename( __FILE__ ) ) )
				return $post_id;

			$post_type = get_post_type_object( $post->post_type );

			if ( !current_user_can( $post_type->cap->edit_post, $post_id ) )
				return $post_id;

			$new_meta_value      = ( isset( $_POST['notes-location'] ) ? sanitize_text_field($_POST['notes-location'] ) : '' );
			$new_placement_above = ( isset( $_POST['notes-check-a'] ) ? sanitize_text_field($_POST['notes-check-a'] ) : '' );
			$new_placement_below = ( isset( $_POST['notes-check-b'] ) ? sanitize_text_field($_POST['notes-check-b'] ) : '' );
			$new_placement_set   = ( isset( $_POST['notes-set'] ) ? sanitize_text_field($_POST['notes-set'] ) : '' );

			// validate data
			$arr1  = preg_split( '/[\s,]+/', $new_meta_value );
			$arr2  = preg_replace( '/[^0-9]/', '', $arr1 );
			$arr3  = implode( ",", $arr2 );

			// update values
			update_post_meta( $post_id, 'note_ids', $arr3 );
			update_post_meta( $post_id, 'note_placement_above', $new_placement_above );
			update_post_meta( $post_id, 'note_placement_below', $new_placement_below );
			update_post_meta( $post_id, 'note_placement_yes', $new_placement_set );
		}


		/**
		*  Main query for notes placement on posts based on id and location (above or below editor)
		*
		* @param string $post_id  
		* @param string $screens visible in defualt notes section
		* @param string $notesposts query
		*/
		function wpt_box_san($post_id){
	
			// get post id for main post 
			 if ( isset($_GET['post']) )
			 $post_id = $_GET['post'] ? $_GET['post'] : $_POST['post_ID'] ;

			// make sure Custom Post Types are included
			$post_types      = get_post_types( '','names' ); 
			$posts_separated = implode( ",",   $post_types );
			$screens         = array( 'post', 'page',  $posts_separated );

				// query CPT use get_posts in admin areas!
				$args = array(
					'post_type'       => 'note',
					'posts_per_page'  => -1,
					'no_found_rows'   => true,
					'meta_query'     => array(
							array(  
								'value'   => $post_id,
								'compare' => 'LIKE',
								),
							)
					);

				$notesposts = get_posts( $args );

					foreach(  $notesposts as $notespost ) : setup_postdata( $notespost ); 

						$placement_above = get_post_meta( $notespost->ID, 'note_placement_above', true );
						$placement_below = get_post_meta( $notespost->ID, 'note_placement_below', true );       
	 
						$title = $notespost->post_title;

						$output = get_the_content();
						$output = apply_filters( 'the_content', $output );
						$output = str_replace( ']]>', ']]&gt;', $output );

					endforeach;
		 
				// Add meta box to screens  
				if ( isset( $output ) ){
					if( $placement_above == '1' ){
						foreach ( $screens as $screen ) {
							add_meta_box( 'styles', $title, 'callback_box_san', $screen, 'pre_editor', 'high', array( 'foo' => $output ) );  
						} 
					}
					if( $placement_below == '1' ){
						foreach ( $screens as $screen ) {
							add_meta_box( 'styles', $title, 'callback_box_san', $screen, 'post_editor', 'high', array( 'foo' => $output ) );  
						} 
					}
				} 
		}
		add_action( 'add_meta_boxes', 'wpt_box_san' );
		
	
		/**
		*  Place meta box above editor
		*/ 
		function render_above_san($post){
			global $post;   
			do_meta_boxes( 'post', 'pre_editor', $post );    
		}
		add_action( 'edit_form_after_title', 'render_above_san' );


		/**
		*  Place meta box below editor
		*/   
		function render_bellow_san($post){
			global $post;   
			do_meta_boxes( 'post', 'post_editor', $post );        
		}
		add_action( 'edit_form_after_editor', 'render_bellow_san' );


		/**
		*  Output for meta callback
		*/ 
		function callback_box_san($post_id, $metabox){
		 echo $metabox['args']['foo'];       
		}


		/**
		*  Shuffle admin menu items to change the text to "Edit Notes"
		*/ 
		function wp_menu_note_san() {
			remove_submenu_page( 'edit.php?post_type=note', 'edit.php?post_type=note' ); 
			add_submenu_page( 'edit.php?post_type=note','My Notes', 'My Notes', 'manage_options', 'my_notes', 'my_notes_options_san' );
			add_submenu_page( 'edit.php?post_type=note','Edit Notes', 'Edit Notes', 'manage_options', 'edit.php?post_type=note' );
		 }
		add_action( 'admin_menu', 'wp_menu_note_san' );


		/**
		*  Main query and output the Notes in the admin page under "My Notes"
		*/
		function my_notes_options_san(){

			if ( is_admin() ) { ?>

			<script type="text/javascript"> 
				var $note = jQuery.noConflict(); 
					$note(function() {
					$note( "#note-tabs" ).tabs();
				});
			</script> 

			<div class="wrap">
			<div id="icon-edit-pages" class="icon32"></div>
			<h2>My Notes</h2>         
				<div class="demo"> 
				<div id="note-tabs"> 
				<ul>

				<?php  }

				// Need to do 2 loops to set <li> first for tab and then the actual content
				// check for empty value so it won't show if selected to not show via check-box
				$args = array(
					'post_type' => 'note',
					'meta_query' => array(
							array(
								'key' => 'note_placement_yes', 
								'value' => '', 
								'compare' => 'EXISTS'
							)
					)
				);

				$displayposts = new WP_Query($args); 
		
				if ($displayposts->have_posts()) : while ($displayposts->have_posts()) : $displayposts->the_post();        
				$tab_id = $displayposts->current_post + 1;               
							 
				?>

				<li><a href="#tabs-<?php echo $tab_id; ?>"><?php the_title(); ?></a></li> 
							 
				<?php endwhile; else: ?>
						 
				</ul>
									 
				<div id="no-notes">    
					<?php _e('You don\'t have any notes yet.' . ''); ?>
				</div>
										 
				<?php 

				endif;       
				rewind_posts(); 
								 
				while ($displayposts->have_posts()) : $displayposts->the_post();           
				$tabs_id = $displayposts->current_post + 1; 

				?>

					<div id="tabs-<?php echo $tabs_id; ?>">
					<p class="note-date">Posted: <?php the_date("j M Y"); ?> | <?php edit_post_link('Edit this note'); ?></p>

					<?php the_content(); ?>  

					</div>
			 
				<?php endwhile; wp_reset_query(); ?>

			</div> <!-- tabs-->
			</div> <!-- demo-->
			</div> <!-- wrap-->

		 <?php } 


		/**
		*  Change sub-menu order
		*/
		function custom_menu_order() {

			global $submenu;

			$find_page = 'edit.php?post_type=note';
			$find_sub = 'My Notes';

			foreach($submenu as $page => $items):
					if($page == $find_page):
							foreach($items as $id => $meta):
									if($meta[0] == $find_sub):
										$submenu[$find_page][0] = $meta;
										unset ($submenu[$find_page][$id]);
										ksort($submenu[$find_page]);
									endif;
							endforeach;
					endif;
			endforeach;
		}
		add_filter( 'custom_menu_order', 'custom_menu_order' );


	/**
	*  Need to use js otherwise the box won't show if using "_" to hide it in default custom fields dropdown
	*/
	function hide_meta_san(){ ?>
	<script>

	var metaArray = ["note_ids", "note_placement_above", "note_placement_below", "note_placement_yes"];
	jQuery.each(metaArray , function(index, value){
			jQuery("#metakeyselect option[value=" + value + "]").hide();
		});

	</script>
	<?php    }
	add_action( 'admin_footer', 'hide_meta_san' );


	/**
	*  Toggle checkbox for above and below editor selection
	*/
	function checkbox_san(){

		global $current_screen;
		if( 'note' == $current_screen->post_type ){ ?>
		<script>

		jQuery('#san-above').click(function(){
			if (this.checked) {
				jQuery('#san-below').prop('checked', false);
			}
		}) 
		
	 	jQuery('#san-below').click(function(){
			if (this.checked) {
				jQuery('#san-above').prop('checked', false);
			} 
		})

		</script>
	<?php }}
	add_action( 'admin_footer', 'checkbox_san' );