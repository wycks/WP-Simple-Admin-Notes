<?php
/*
Plugin Name: Simple Admin Notes
Plugin URI: https://twitter.com/wycks_s
Description: Adds a simple Notes section to admin areas
Author: Wycks
Author URI: http://wordpress.org/extend/plugins/profile/wycks
Version: 1.0.5
License: GPL2
*/

    // don't load directly
   if ( !defined('ABSPATH') )
      die('dont load directly');


    // enqueue jQuery tabs with default WP bundle in admin
 
    function default_scripts_san() { 
      wp_enqueue_script( 'jquery' );
      wp_enqueue_script( 'jquery-ui-tabs' );
      wp_enqueue_script( 'jquery-ui-core' );
    }

    add_action( 'admin_enqueue_scripts',  'default_scripts_san' );


    // register a CPT that is only available in the admin area called "Notes"

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


    // add custom meta box to notes edit page

    function add_notes_metaboxes_san(){
      add_meta_box('wpt_notes_location', 'Display Notes', 'wpt_notes_san', 'note', 'side', 'default');
    }


    // save post action hook for meta box
   
    function wpt_savehook_san(){    
      add_action( 'save_post', 'wpt_save_san', 10, 2 );
    }

    add_action( 'load-post.php', 'wpt_savehook_san' );
    add_action( 'load-post-new.php', 'wpt_savehook_san' );

    // meta box fields added to sidebar of Notes CPT

    function wpt_notes_san($post) {
   
      wp_nonce_field( basename( __FILE__ ), 'notes_san_noncename' );

      // get placement above or below editor
      $placement_above = get_post_meta($post->ID, 'note_placement_above', true);
      $placement_below = get_post_meta($post->ID, 'note_placement_below', true);

      // check to display in default notes section
      $placement_yes = get_post_meta($post->ID, 'note_placement_yes', true);

      // get the post-page id's if entered
      $location = get_post_meta($post->ID, 'note_ids', true);
      
      // the form
      echo '<form>';
      echo '<input type="text" name="notes-location" value="' . esc_attr($location)  . '" class="widefat"><br>';
      echo 'Please enter the post ids above <br><br>';

      if ($placement_above == '1'){
        echo '<input type="checkbox" name="notes-check-a" value="1" checked="yes"> Set above editor <br>';
      }else{
        echo '<input type="checkbox" name="notes-check-a" value="1"> Set above editor <br>';
      }

      if ($placement_below == '1'){
        echo '<input type="checkbox" name="notes-check-b" value="1" checked="yes"> Set below editor <br><br>';
      }else{
        echo '<input type="checkbox" name="notes-check-b" value="1" > Set below editor <br><br>';
      }

      if ($placement_yes == '1'){
        echo '<input type="checkbox" name="notes-set" value="1" checked="yes"> Do not show in the default notes section<br>';
      }else{
        echo '<input type="checkbox" name="notes-set" value="1"> Do not show in the default notes section<br>';
      }  
      echo '</form>';

    }


    // save post meta box data

    function wpt_save_san($post_id, $post ){

      //check nonce
      if ( !isset( $_POST['notes_san_noncename'] ) || !wp_verify_nonce( $_POST['notes_san_noncename'], basename( __FILE__ ) ) )
        return $post_id;

      $post_type = get_post_type_object( $post->post_type );

      // verify permissions
      if ( !current_user_can( $post_type->cap->edit_post, $post_id ) )
        return $post_id;

      // get POST values
      $new_meta_value      = ( isset( $_POST['notes-location'] ) ? sanitize_text_field($_POST['notes-location'] ) : '' );
      $new_placement_above = ( isset( $_POST['notes-check-a'] ) ? sanitize_text_field($_POST['notes-check-a'] ) : '' );
      $new_placement_below = ( isset( $_POST['notes-check-b'] ) ? sanitize_text_field($_POST['notes-check-b'] ) : '' );
      $new_placement_set   = ( isset( $_POST['notes-set'] ) ? sanitize_text_field($_POST['notes-set'] ) : '' );

      // validate data
      $arr1  = preg_split('/[\s,]+/', $new_meta_value);
      $arr2  = preg_replace('/[^0-9]/', '', $arr1  );
      $arr3  = implode(",", $arr2);

      // update id values
      update_post_meta( $post_id, 'note_ids', $arr3);

      // update placement
      update_post_meta( $post_id, 'note_placement_above', $new_placement_above);
      update_post_meta( $post_id, 'note_placement_below', $new_placement_below);

      // check to display in default notes section
      update_post_meta( $post_id, 'note_placement_yes', $new_placement_set);

    }

    // main query for notes placement on posts based on id and location(above or below editor)
    
    function wpt_box_san($post_id){
  
      // get post id for main post 
       if (isset($_GET['post']))
       $post_id = $_GET['post'] ? $_GET['post'] : $_POST['post_ID'] ;


      var_dump($post_id);

      // make sure Custom Post Types are included
      $post_types= get_post_types('','names'); 
      $posts_separated = implode(",",   $post_types);
      $screens = array( 'post', 'page',  $posts_separated);

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

        $notesposts = get_posts($args);


          foreach(  $notesposts as $notespost ) : setup_postdata($notespost); 

            $placement_above = get_post_meta($notespost->ID, 'note_placement_above', true);
            $placement_below = get_post_meta($notespost->ID, 'note_placement_below', true);       
   
            $title = $notespost->post_title;

            $output = get_the_content();
            $output = apply_filters('the_content', $output);
            $output = str_replace(']]>', ']]&gt;', $output);

          endforeach;
     
        /** 
        *  Add meta box to screens
        *  Uses callback_box_san
        *  Uses render_box_san
        */
       
        if (isset($output)){
          if($placement_above == '1'){
            foreach ($screens as $screen) {
              add_meta_box( 'styles', $title, 'callback_box_san', $screen, 'pre_editor', 'high', array( 'foo' => $output));  
            } 
          }
          if($placement_below == '1'){
            foreach ($screens as $screen) {
              add_meta_box( 'styles', $title, 'callback_box_san', $screen, 'post_editor', 'high', array( 'foo' => $output));  
            } 
          }
        } 
    }
    
    add_action( 'add_meta_boxes', 'wpt_box_san' );
    
  
    // to place meta box above editor need to use this action
    // do_meta_boxes seems to only work on post (under screen options)
      
    function render_above_san($post){
      global $post;   
      do_meta_boxes('post', 'pre_editor', $post);    
    }

    add_action( 'edit_form_after_title', 'render_above_san' );


    // to place meta box above editor need to use this action
      
    function render_bellow_san($post){
      global $post;   
      do_meta_boxes('post', 'post_editor', $post);        
    }

    add_action( 'edit_form_after_editor', 'render_bellow_san' );


    //  output content for meta callback
     
    function callback_box_san($post_id, $metabox){
     echo $metabox['args']['foo'];       
    }


    // Add sub-menu page
    // Removes default edit since I wanted to change the text to "Edit Notes"

    function wp_menu_note_san() {
      remove_submenu_page( 'edit.php?post_type=note', 'edit.php?post_type=note' ); 
      add_submenu_page('edit.php?post_type=note','My Notes', 'My Notes', 'manage_options', 'my_notes', 'my_notes_options_san');
      add_submenu_page('edit.php?post_type=note','Edit Notes', 'Edit Notes', 'manage_options', 'edit.php?post_type=note');
     }

    add_action('admin_menu', 'wp_menu_note_san');


    // Add the tabs CSS
    
    function my_admin_head_san() {
      echo '<link rel="stylesheet" type="text/css" href="' . plugins_url('simple-notes.css', __FILE__). '" />';
    }

    add_action('admin_head', 'my_admin_head_san');



    // Query and output the Notes in the admin page under "My Notes"
    
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
          <a href="post-new.php?post_type=note"> Click here</a> to make your first note.
        </div>
                     
        <?php endif; ?>
             
        <?php rewind_posts(); ?>
                 
        <?php 

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


   // change sub-menu order this is horrible WordPress

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

    add_filter('custom_menu_order', 'custom_menu_order');