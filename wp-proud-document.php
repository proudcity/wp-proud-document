<?php
/*
Plugin Name: Proud Document
Plugin URI: http://proudcity.com/
Description: Declares an Document custom post type.
Version: 1.0
Author: ProudCity
Author URI: http://proudcity.com/
License: Affero GPL v3
*/

namespace Proud\Document;

// Load Extendible
// -----------------------
if ( ! class_exists( 'ProudPlugin' ) ) {
  require_once( plugin_dir_path(__FILE__) . '../wp-proud-core/proud-plugin.class.php' );
}

class ProudDocument extends \ProudPlugin {

  /*public function __construct() {
    add_action( 'init', array($this, 'initialize') );
    add_action( 'admin_init', array($this, 'document_admin') );
    add_action( 'save_post', array($this, 'add_document_fields'), 10, 2 );
    //add_filter( 'template_include', 'document_template' );
    add_action( 'rest_api_init', array($this, 'document_rest_support') );
  }*/

  public function __construct() {
    /*parent::__construct( array(
      'textdomain'     => 'wp-proud-document',
      'plugin_path'    => __FILE__,
    ) );*/

    $this->hook( 'init', 'create_document' );
    $this->hook( 'admin_init', 'document_admin' );
    $this->hook( 'save_post', 'add_document_fields', 10, 2 );
    $this->hook( 'rest_api_init', 'document_rest_support' );
    $this->hook( 'init', 'document_taxonomy' );

    add_action( 'wp_ajax_proud_document_icon', array($this, 'get_icon') );

  }


  public function create_document() {
      $labels = array(
          'name'               => _x( 'Documents', 'post name', 'wp-document' ),
          'singular_name'      => _x( 'Document', 'post type singular name', 'wp-document' ),
          'menu_name'          => _x( 'Documents', 'admin menu', 'wp-document' ),
          'name_admin_bar'     => _x( 'Document', 'add new on admin bar', 'wp-document' ),
          'add_new'            => _x( 'Add New', 'document', 'wp-document' ),
          'add_new_item'       => __( 'Add New Document', 'wp-document' ),
          'new_item'           => __( 'New Document', 'wp-document' ),
          'edit_item'          => __( 'Edit Document', 'wp-document' ),
          'view_item'          => __( 'View Document', 'wp-document' ),
          'all_items'          => __( 'All Documents', 'wp-document' ),
          'search_items'       => __( 'Search document', 'wp-document' ),
          'parent_item_colon'  => __( 'Parent document:', 'wp-document' ),
          'not_found'          => __( 'No documents found.', 'wp-document' ),
          'not_found_in_trash' => __( 'No documents found in Trash.', 'wp-document' )
      );

      $args = array(
          'labels'             => $labels,
          'description'        => __( 'Description.', 'wp-document' ),
          'public'             => true,
          'publicly_queryable' => true,
          'show_ui'            => true,
          'show_in_menu'       => true,
          'query_var'          => true,
          'rewrite'            => array( 'slug' => 'documents' ),
          'capability_type'    => 'post',
          'has_archive'        => false,
          'hierarchical'       => false,
          'menu_position'      => null,
          'show_in_rest'       => true,
          'rest_base'          => 'documents',
          'rest_controller_class' => 'WP_REST_Posts_Controller',
          'supports'           => array( 'title', 'editor', 'excerpt',)
      );

      register_post_type( 'document', $args );
  }

  function document_taxonomy() {
    register_taxonomy(
        'document_taxonomy',
        'document',
        array(
            'labels' => array(
                'name' => 'Document Types',
                'add_new_item' => 'Add New Document Type',
                'new_item_name' => "New Document Type"
            ),
            'show_ui' => true,
            'show_tagcloud' => false,
            'hierarchical' => true
        )
    );
  }

  public function document_admin() {
    add_meta_box( 'document_file_meta_box',
      'File',
      array($this, 'display_document_file_meta_box'),
      'document', 'normal', 'high'
    );
    add_meta_box( 'document_form_meta_box',
      'Form',
      array($this, 'display_document_form_meta_box'),
      'document', 'normal', 'high'
    );
  }

  public function document_rest_support() {
    register_api_field( 'document',
          'meta',
          array(
              'get_callback'    => array( $this, 'document_rest_metadata' ),
              'update_callback' => null,
              'schema'          => null,
          )
      );
  }

  // @todo make this work
  // See http://code.tutsplus.com/articles/adding-and-removing-images-with-the-wordpress-media-uploader--cms-22087
  /*public function document_enqueue_scripts() {
    
    wp_enqueue_script(
      $this->name,
      plugin_dir_url( __FILE__ ) . 'assets/admin.js',
      array( 'jquery' ),
      $this->version,
      'all'
    );
    print plugin_dir_url( __FILE__ ) . 'assets/admin.js';die();
  }*/


  /**
   * Alter the REST endpoint.
   * Add metadata to the post response
   */
  public function document_rest_metadata( $object, $field_name, $request ) {
      $return = array();
      foreach ($this->document_fields() as $key => $label) {
        if ($value = get_post_meta( $object[ 'id' ], $key, true )) {
          $return[$key] = $value;
        }
      }
      return $return;
  }

  /**
   * Display the file upload meta box
   */
  public function display_document_file_meta_box( $document ) {
    wp_enqueue_media();
    ?>
      <input id="upload-src" type="hidden" name="upload_src" value="<?php echo get_post_meta( $document->ID, 'document', true ); ?>" />
      <input id="upload-meta" type="hidden" name="upload_meta" value='<?php echo get_post_meta( $document->ID, 'document_meta', true ); ?>' />
      <i class="fa fa-3x filetype-icon text-muted" id="upload-thumb" style="float:left;margin-right: 5px;"></i>
      <strong><div id="upload-filename-text" style="padding-bottom:.5em;"></div></strong>
      <input id="upload-filename" type="text" name="upload_filename" value="<?php echo get_post_meta( $document->ID, 'document_filename', true ); ?>" style="display:none;" />
      <div>
        <button type="button" id="upload-remove" class="button" style="display:none;">Remove</button>
        <button type="button" id="upload-upload" class="button"><span class="wp-media-buttons-icon"></span> <span id="upload-add-text">Add</span><span id="upload-change-text" style="display: none">Change</span> Document</button>
      </div>
      <div class="clearfix"></div>
      <script type="text/javascript">
      function renderMediaUploader() {
        var file_frame, image_data;
        if ( undefined !== file_frame ) {
            file_frame.open();
            return;
        }
        file_frame = wp.media.frames.file_frame = wp.media({
            frame:    'post',
            state:    'insert',
            multiple: false
        });
        file_frame.on( 'insert', function() {
          json = file_frame.state().get( 'selection' ).first().toJSON();
          jQuery('#upload-src').val(json.url);
          jQuery('#upload-filename').val(json.filename);
          jQuery('#upload-meta').val(JSON.stringify({
            size: json.filesizeHumanReadable,
            icon: json.icon,
            mime: json.mime,
            filetype: json.mime.split('/')[1]
          })).trigger('change');
        });
        file_frame.open();
      }
       
      (function( $ ) {
          'use strict';
          $(function() {
              $( '#upload-upload' ).on( 'click', function( evt ) {
                  evt.preventDefault();
                  renderMediaUploader();
              });
              $('#upload-meta').bind('change', function(){changeMeta()});
              function changeMeta() {
                var meta = JSON.parse($('#upload-meta').val());
                if (meta != undefined && meta.mime != undefined) {
                  // Get the icon to use
                  jQuery.get(
                    ajaxurl + '?action=proud_document_icon&filetype=' + meta.filetype, 
                    {}, 
                    function(response){
                      $('#upload-thumb').addClass( response.icon );
                    }
                  );
                  $('#upload-filename-text').html($('#upload-filename').val() + ' ('+ meta.size +') <a href="#">edit</a>');
                  $('#upload-thumb, #upload-filename-text, #upload-remove, #upload-change-text').show();
                  $('#upload-add-text, #upload-filename').hide();
                }
                else {
                  $('#upload-thumb, #upload-filename, #upload-remove, #upload-change-text, #upload-filename-text').hide();
                  $('#upload-add-text').show();
                }
                bindBtns();
              }
              changeMeta();

              function bindBtns() {
                $( '#upload-remove' ).on( 'click', function( evt ) {
                  evt.preventDefault();
                  $('#upload-thumb, #upload-filename, #upload-filename-text, #upload-meta, #upload-src').val('').bind('change').hide();
                  $('#upload-remove').hide();
                  $('#upload-upload').show();
                });
                $( '#upload-filename-text, #upload-filename-text a' ).on( 'click', function( evt ) {
                  evt.preventDefault();
                  $('#upload-filename').show();
                  $('#upload-filename-text').hide();
                });
              }
              bindBtns();
              
          });
      })( jQuery );
    </script>
    <style type="text/css">#wpseo_meta,#advanced-sortables{display:none;}</style><!--@todo: do this with a php hook -->
    <?php 
  }

  /**
   * Adds form api fields
   */
  public function build_fields($id) {
    $this->fields = [];
  
    $this->fields['form'] = [
      '#type' => 'gravityform',
      '#title' => __('Form'),
      '#description' => __('Select a form. <a href="admin.php?page=gf_edit_forms" target="_blank">Create a new form</a>. Leave this empty if there is no form version of this Document.', 'wp-proud-document'),
      '#name' => 'form',
      '#value' => get_post_meta( $id, 'form', true ),
    ];

    return $this->fields;
  }

  /**
   * Adds document form metadata field
   */
  public function display_document_form_meta_box( $document ) {
    $this->build_fields($document->ID);
    $form = new \Proud\Core\FormHelper( $this->key, $this->fields );
    $form->printFields();
  }

  /**
   * Saves document metadata fields 
   */
  public function add_document_fields( $id, $document ) {
    if ( $document->post_type == 'document' ) {
      // File fields
      update_post_meta( $id, 'document', $_POST['upload_src'] );
      update_post_meta( $id, 'document_filename', $_POST['upload_filename'] );
      update_post_meta( $id, 'document_meta', $_POST['upload_meta'] );

      // Form api fields
      foreach ($this->build_fields($id) as $key => $field) {
        if ( !empty( $_POST[$key] ) || !empty(get_post_meta( $id, $key, true )) ) {  // @todo: check if it has been set already to allow clearing of value
          update_post_meta( $id, $key, $_POST[$key] );
        }
      }
    }
  }

  /**
   * AJAX callback gets icon from a filetype
   */
  public function get_icon( ) {
    return wp_send_json(array(
      'icon' => get_document_icon( 0, $_GET['filetype'] )
    ));
  }


} // class


new ProudDocument;


/**
 * Helper: Returns the document type (suffix)
 */
function get_document_type($post = 0, $filename = null) {
  $post = $post > 0 ? $post : get_the_ID();
  if ( empty($filename) ) {
    $filename = get_post_meta( $post, 'document_filename', true );
  }
  if ( empty($filename) ) {
    return '';
  }
  $info = pathinfo($filename);
  return strtolower($info['extension']);
}

/**
 * Helper: Returns the document fontawesome icon
 */
function get_document_icon($post = 0, $filename = null) {
  $form = get_post_meta( $post, 'form', true );
  if (!empty($form)) {
    return 'fa-globe';
  }

  switch ( get_document_type($post, $filename) ) {
    case 'pdf':
      return 'fa-file-pdf-o';
      break;
    case 'doc':
    case 'docx':
      return 'fa-file-word-o';
      break;
    case 'ppt':
    case 'pptx':
      return 'fa-file-powerpoint-o';
      break;
    case 'xls':
    case 'xlsx':
      return 'fa-file-excel-o';
      break;
    case 'wav':
    case 'aif':
    case 'mp3':
      return 'fa-file-audio-o';
      break;
    case 'zip':
    case 'tar':
      return 'fa-file-zip-o';
      break;
    case 'png':
    case 'jpg':
    case 'jpeg':
    case 'gif':
      return 'fa-file-photo-o';
      break;
    default:
      return 'fa-file-text-o';
  }
}