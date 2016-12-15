<?php

/* call our code on admin pages only, not on front end requests or during
 * AJAX calls.
 * Always wait for the last possible hook to start your code.
 */
add_action( 'admin_menu', array ( 'ProudDocumentResetOrder', 'admin_menu' ) );
// Add ajax endpoint
add_action( 'wp_ajax_proud-document-reset', array ( 'ProudDocumentResetOrder', 'ajax_response' ) );

class ProudDocumentResetOrder
{
  /**
   * Register the pages and the style and script loader callbacks.
   *
   * @wp-hook admin_menu
   * @return  void
   */
  public static function admin_menu()
  {
    // built with get_plugin_page_hookname( $menu_slug, '' )
    $slug = add_submenu_page(
      null,
      'Reset Document Order',             // page title
      'Reset Document Order',             // menu title
      // Change the capability to make the pages visible for other users.
      // See http://codex.wordpress.org/Roles_and_Capabilities
      'manage_options',                  // capability
      'proud-document-reset',             // menu slug
      array ( __CLASS__, 'render_page' ) // callback function
    );

    // make sure the script callback is used on our page only
    add_action(
      "admin_print_scripts-$slug",
      array ( __CLASS__, 'enqueue_script' )
    );
  }

  /**
   * Load JavaScript on our admin page only.
   *
   * @return void
   */
  public static function enqueue_script()
  {
    wp_register_script(
      'proud-document-reset',
      plugins_url( '/../assets/js/proud-document-reset.js', __FILE__ ),
      array(),
      FALSE,
      TRUE
    );
    wp_localize_script( 'proud-document-reset', 'proudDocumentReset', array(
      'url' => admin_url( 'admin-ajax.php' ),
      'params' => array(
        'action' => 'proud-document-reset', 
        '_wpnonce' => wp_create_nonce( 'proud-document-reset' )
      )
    ) );
    wp_enqueue_script( 'proud-document-reset' );
  }

  /**
   * Print page output.
   *
   * @return  void
   */
  public static function render_page()
  {
    print '<div class="wrap">';
    print "<h1>Reset Document Ordering</h1>";
    print '<div id="message-box"></div>';
    submit_button( 'Reset Document Order!' );
    print '</div>';
  }

  /**
   * Resets the order to date
   */
  public static function reset_document_order() {
    $args = [
      'post_type' => 'document', 
      'post_status' => 'publish',
      'update_post_term_cache' => true, // don't retrieve post terms
      'update_post_meta_cache' => true, // don't retrieve post meta
      'posts_per_page' => 1000,
      'orderby'   => 'post_date',
      'order'     => 'DESC',
      'suppress_filters' => true,
    ];
    $query = new \WP_Query( $args );
    $i = 1;
    global $wpdb;
    foreach( $query->posts as $document ) {      
      $wpdb->update( $wpdb->posts, array( 'menu_order' => $i ), array( 'ID' => $document->ID ) );
      $i++;
    }
    return $i;
  }

  /**
   * Handles the AJAX endpoint to reset everything.
   *
   * @return json response
   */
  public function ajax_response( ) {
    check_ajax_referer( 'proud-document-reset' );
    $count = 0;
    try {
      $count = ProudDocumentResetOrder::reset_document_order();
    } catch ( Exception $e ) {
      wp_send_json_error( array( 'message' => 'An error occurred trying to reset order.' ) );
      wp_die();
    }
    wp_send_json_success( array( 'message' => "Successfully re-ordered $count documents." ) );
    wp_die();
  }
}
