<?php
/*
Plugin Name: Simple Admin Notes
Plugin URI: http://www.wpsecure.net/
Description: Adds a simple Notes section to the admin area
Author: Wycks
Author URI: http://wordpress.org/extend/plugins/profile/wycks
Version: 1.0.5
License: GPL2
*/
//

// Adding these in case, might act wierd without
add_action( 'admin_enqueue_scripts',  'default_scripts_note' );
function default_scripts_note() {
    

    wp_enqueue_script( 'jquery' );
    wp_enqueue_script( 'jquery-ui-tabs' );
    wp_enqueue_script( 'jquery-ui-core' );
}


// register a CPT that is only availible in the Admin area called "Notes"

    add_action( 'init', 'register_cpt_note' );

    function register_cpt_note() {

        $labels = array( 
            'name' => _x( 'notes', 'note' ),
            'singular_name' => _x( 'note', 'note' ),
            'add_new' => _x( 'New Note', 'note' ),
            'add_new_item' => _x( 'New note', 'note' ),
            'edit_item' => _x( 'Edit note', 'note' ),
            'new_item' => _x( 'New note', 'note' ),
            'view_item' => _x( 'View note', 'note' ),
            'search_items' => _x( 'Search notes', 'note' ),
            'not_found' => _x( 'No notes found', 'note' ),
            'not_found_in_trash' => _x( 'No notes found in Trash', 'note' ),
            'parent_item_colon' => _x( 'Parent note:', 'note' ),
            'menu_name' => _x( 'Notes', 'Note' ),
        );

        $args = array( 
            'labels' => $labels,
            'hierarchical' => true,
            'description' => 'Registers a notes section for Admin only',
            'supports' => array( 'title', 'editor', 'author',  'revisions' ),
            'public' => false,
            'show_ui' => true,
            'show_in_menu' => true,
            'menu_position' => 10,
            'show_in_nav_menus' => true,
            'publicly_queryable' => false,
            'exclude_from_search' => true,
            'has_archive' => false,
            'query_var' => true,
            'can_export' => true,
            'rewrite' => true,
            'capability_type' => 'page'
        );

        register_post_type( 'note', $args );
    }

    add_action('admin_menu', 'wp_menu_note');


//add sub-menu page to custom post type in menu that will output the actual "notes"

    function wp_menu_note() {
         
        
         remove_submenu_page( 'edit.php?post_type=note', 'edit.php?post_type=note' ); //remove default edit since I wanted to change the text to "Edit Notes"
         // add 2 menu items "My Notes", "Edit Notes"
         add_submenu_page('edit.php?post_type=note','My Notes', 'My Notes', 'manage_options', 'my_notes', 'my_notes_options');
         add_submenu_page('edit.php?post_type=note','Edit Notes', 'Edit Notes', 'manage_options', 'edit.php?post_type=note');
     }

 
// Query and output the Notes onto the admin page under "My Notes"
 
     function my_notes_options(){

        if ( is_admin() ) {

            //wp_reset_query();
           $displayposts = new WP_Query(); $displayposts->query('post_type=note'); ?> 


      <script type="text/javascript"> 
            jQuery(function() {
                    jQuery( "#note-tabs" ).tabs();
            });
      </script> 


       <div class="wrap">
          <div id="icon-edit-pages" class="icon32"></div><h2>My Notes</h2> 
          
            <div class="demo"> 
               <div id="note-tabs"> 

              <?php  }  ?>

                <ul>

                  <?php if ($displayposts->have_posts()) :while ($displayposts->have_posts()) : $displayposts->the_post();
            
                  $tab_id = $displayposts->current_post + 1; 
                
               
                 ?>

                 <li><a href="#tabs-<?php echo $tab_id; ?>">
                        
                 <?php the_title(); ?></a></li> 

               
          <?php endwhile; else:?>
              </ul>
                   
                <div id="no-notes">    
                     <?php _e('You don\'t have any notes yet.' . ''); ?>
                     <a href="post-new.php?post_type=note"> Click here</a> to make your first note.
                </div>
                     
          <?php endif; ?>
             


          <?php rewind_posts(); ?>
                 

            <?php while ($displayposts->have_posts()) : $displayposts->the_post();
            
            $tabs_id = $displayposts->current_post + 1; ?>

 
        <div id="tabs-<?php echo $tabs_id; ?>">

            <p class="note-date">Posted: <?php the_date(); ?> | <?php edit_post_link('Edit this note'); ?></p>

            <?php the_content(); ?>
       
        
        </div>

        
      <?php endwhile; ?>


    <?php
    // Reset Query
    wp_reset_query();


    ?>
       </div> <!-- tabs-->
          </div> <!-- demo-->
           </div> <!-- wrap-->


     <?php } 


   // change sub-menu order this is horrible WordPress..
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



    // add the tabs CSS

    function my_admin_head() {

       echo '<link rel="stylesheet" type="text/css" href="' .plugins_url('simple-notes.css', __FILE__). '" />';
    }

    add_action('admin_head', 'my_admin_head');
?>
