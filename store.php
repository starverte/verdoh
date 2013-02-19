<?php
/*
Plugin Name: Sparks Store
Plugin URI: http://starverte.com/plugins/sparks-store
Description: Part of the Sparks Framework. A plugin that allows for easy e-commerce development.
Version: alpha
Author: Star Verte LLC
Author URI: http://www.starverte.com
License: GPLv2 or later

    Copyright 2013  Star Verte LLC  (email : info@starverte.com)
    
    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License, version 2, as 
    published by the Free Software Foundation.
    
    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.
    
    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
    
    You should have received a copy of the GNU General Public License
    along with Store.  If not, see <http://www.gnu.org/licenses/>.
*/

function sparks_store_init() {
  $labels = array(
    'name' => 'Products',
    'singular_name' => 'Product',
    'add_new' => 'Add New',
    'add_new_item' => 'Add New Product',
    'edit_item' => 'Edit Product',
    'new_item' => 'New Product',
    'all_items' => 'All Products',
    'view_item' => 'View Product',
    'search_items' => 'Search Store',
    'not_found' =>  'No products found',
    'not_found_in_trash' => 'No products found in Trash. Did you check recycling?', 
    'parent_item_colon' => '',
    'menu_name' => 'Store'
  );

  $args = array(
    'labels' => $labels,
    'public' => true,
    'publicly_queryable' => true,
    'show_ui' => true, 
    'show_in_menu' => true, 
    'query_var' => true,
    'rewrite' => array( 'slug' => 'products' ),
    'capability_type' => 'post',
    'has_archive' => true, 
    'hierarchical' => false,
    'menu_position' => 5,
    'supports' => array( 'title', 'editor', 'thumbnail', 'comments' ),
  ); 

  register_post_type( 'sp_product', $args );
}
add_action( 'init', 'sparks_store_init' );

//add filter to ensure the text Product, or item, is displayed when user updates an item in the Store 

function codex_sp_product_updated_messages( $messages ) {
  global $post, $post_ID;

  $messages['sp_product'] = array(
    0 => '', // Unused. Messages start at index 1.
    1 => sprintf( __('Product updated. <a href="%s">View product</a>'), esc_url( get_permalink($post_ID) ) ),
    2 => __('Custom field updated.'),
    3 => __('Custom field deleted.'),
    4 => __('Product updated.'),
    /* translators: %s: date and time of the revision */
    5 => isset($_GET['revision']) ? sprintf( __('Product restored to revision from %s'), wp_post_revision_title( (int) $_GET['revision'], false ) ) : false,
    6 => sprintf( __('Product now in store. <a href="%s">View product</a>'), esc_url( get_permalink($post_ID) ) ),
    7 => __('Product saved.'),
    8 => sprintf( __('Product submitted. <a target="_blank" href="%s">Preview product</a>'), esc_url( add_query_arg( 'preview', 'true', get_permalink($post_ID) ) ) ),
    9 => sprintf( __('Product scheduled for: <strong>%1$s</strong>. <a target="_blank" href="%2$s">Preview product</a>'),
      // translators: Publish box date format, see http://php.net/date
      date_i18n( __( 'M j, Y @ G:i' ), strtotime( $post->post_date ) ), esc_url( get_permalink($post_ID) ) ),
    10 => sprintf( __('Product draft updated. <a target="_blank" href="%s">Preview product</a>'), esc_url( add_query_arg( 'preview', 'true', get_permalink($post_ID) ) ) ),
  );

  return $messages;
}
add_filter( 'post_updated_messages', 'codex_sp_product_updated_messages' );

// BEGIN - Create custom fields
add_action( 'add_meta_boxes', 'sp_product_add_custom_boxes' );

function sp_product_add_custom_boxes() {
	add_meta_box('sp_product_meta', 'Details', 'sp_product_meta', 'sp_product', 'side', 'high');
}

/* Product Details */
function sp_product_meta() {
	global $post;
	$custom = get_post_custom($post->ID);
	
?>
    <p><label>Reference</label> 
	<input type="text" size="10" name="item_ref" value="<?php if (isset($custom['item_ref'])) { echo $custom["item_ref"] [0]; } ?>" /></p>
    <p><label>Price</label> 
	<input type="number" size="10" step="0.01" name="item_price" value="<?php if (isset($custom['item_price'])) { echo $custom["item_price"] [0]; } ?>" /></p>
    <p><label>Shipping</label> 
	<input type="number" size="10" step="0.01" name="item_shipping" value="<?php if (isset($custom['item_shipping'])) { echo $custom["item_shipping"] [0]; } ?>" /></p>
    <p><label>Width</label> 
	<input type="number" size="10" step="0.25" name="item_width" value="<?php if (isset($custom['item_width'])) { echo $custom["item_width"] [0]; } ?>" /></p>
    <p><label>Height</label> 
	<input type="number" size="10" step="0.25" name="item_height" value="<?php if (isset($custom['item_height'])) { echo $custom["item_height"] [0]; } ?>" /></p>
    <p><label>Depth</label> 
	<input type="number" size="10" step="0.25" name="item_depth" value="<?php if (isset($custom['item_depth'])) { echo $custom["item_depth"] [0]; } ?>" /></p>
	<?php
}

/* Save Details */
add_action('save_post', 'save_item_details');


function save_item_details(){
  global $post;
  if ( defined('DOING_AUTOSAVE') && DOING_AUTOSAVE && (isset($post_id)) ) {
	return $post_id;
  }

  if( defined('DOING_AJAX') && DOING_AJAX && (isset($post_id)) ) { //Prevents the metaboxes from being overwritten while quick editing.
	return $post_id;
  }

  if( ereg('/\edit\.php', $_SERVER['REQUEST_URI']) && (isset($post_id)) ) { //Detects if the save action is coming from a quick edit/batch edit.
	return $post_id;
  }
  // save all meta data
  if (isset($_POST['item_ref'])) {
  	update_post_meta($post->ID, "item_ref", $_POST["item_ref"]);
  }
  if (isset($_POST['item_price'])) {
  	update_post_meta($post->ID, "item_price", $_POST["item_price"]);
  }
  if (isset($_POST['item_shipping'])) {
	  update_post_meta($post->ID, "item_shipping", $_POST["item_shipping"]);
  }
  if (isset($_POST['item_width'])) {
	  update_post_meta($post->ID, "item_width", $_POST["item_width"]);
  }
  if (isset($_POST['item_height'])) {
	  update_post_meta($post->ID, "item_height", $_POST["item_height"]);
  }
  if (isset($_POST['item_depth'])) {
	  update_post_meta($post->ID, "item_depth", $_POST["item_depth"]);
  }
}
// END - Custom Fields

add_action( 'init', 'create_sp_product_taxonomies', 0 );

function create_sp_product_taxonomies() 
{

  $labels = array(
    'name' => _x( 'Departments', 'sparks-store' ),
    'singular_name' => _x( 'Department', 'sparks-store' ),
    'search_items' =>  __( 'Search Departments' ),
    'all_items' => __( 'All Departments' ),
    'parent_item' => __( 'Parent Department' ),
    'parent_item_colon' => __( 'Parent Department:' ),
    'edit_item' => __( 'Edit Department' ), 
    'update_item' => __( 'Update Department' ),
    'add_new_item' => __( 'Add New Department' ),
    'new_item_name' => __( 'New Department Name' ),
    'menu_name' => __( 'Departments' ),
  ); 	

  register_taxonomy('department',array('sp_product'), array(
    'hierarchical' => true,
    'labels' => $labels,
    'show_ui' => true,
    'show_admin_column' => true,
    'query_var' => true,
    'rewrite' => array( 'slug' => 'store' ),
  ));

  $labels = array(
    'name' => _x( 'Keywords', 'sparks-store' ),
    'singular_name' => _x( 'Keyword', 'sparks-store' ),
    'search_items' =>  __( 'Search Keywords' ),
    'popular_items' => __( 'Popular Keywords' ),
    'all_items' => __( 'All Keywords' ),
    'parent_item' => null,
    'parent_item_colon' => null,
    'edit_item' => __( 'Edit Keyword' ), 
    'update_item' => __( 'Update Keyword' ),
    'add_new_item' => __( 'Add New Keyword' ),
    'new_item_name' => __( 'New Keyword Name' ),
    'separate_items_with_commas' => __( 'Separate keywords with commas' ),
    'add_or_remove_items' => __( 'Add or remove keywords' ),
    'choose_from_most_used' => __( 'Choose from the most used keywords' ),
    'menu_name' => __( 'Keywords' ),
  ); 

  register_taxonomy('keyword','sp_product',array(
    'hierarchical' => false,
    'labels' => $labels,
    'show_ui' => true,
    'show_admin_column' => true,
    'update_count_callback' => '_update_post_term_count',
    'query_var' => true,
    'rewrite' => array( 'slug' => 'key' ),
  ));
}

function item_ref( $before = '<div class="item-ref">Reference <span>#' , $after = '</span></div>' ) {
	$custom = get_post_custom();
	if (isset($custom['item_ref']) && !empty($custom['item_ref'] [0])) {
		$item_ref = $custom["item_ref"] [0];
		printf( $before . $item_ref . $after);
	}
}
function item_price( $before = '<div class="item-price">Price: <span>$' , $after = '</span></div>' ) {
	$custom = get_post_custom();
	if (isset($custom['item_price'])) {
		$item_price = $custom["item_price"] [0];
		printf( $before . $item_price . $after);
	}
}
function item_shipping( $before = '<div class="item-shipping">Additional Shipping Cost: <span>$' , $after = '</span></div>' ) {
	$custom = get_post_custom();
	if (isset($custom['item_shipping']) && !empty($custom['item_shipping'] [0])) {
		$item_shipping = $custom["item_shipping"] [0];
		printf( $before . $item_shipping . $after);
	}
}
function item_dimensions( $args = array(), $sep = ' x ' ) {
	$custom = get_post_custom();
	$defaults = array (
		'before' => '<div class="item-dimensions">Dimensions: <span>' ,
		'after' => '</span></div>' ,
		'sep1' => $sep,
		'sep2' => $sep,
		'dimensions' => 3,
		'unit' => ' in',
	);
	$args = wp_parse_args($args, $defaults);
	$args = (object) $args;
	if ( $dimensions = 3 && !empty($custom['item_width'] [0]) && !empty($custom['item_height'] [0]) && !empty($custom['item_depth'] [0])) {
		if (isset($custom['item_width'])) {
			$item_width = $custom["item_width"] [0];
		}
		if (isset($custom['item_height'])) {
			$item_height = $custom["item_height"] [0];
		}
		if (isset($custom['item_depth'])) {
			$item_depth = $custom["item_depth"] [0];
		}
		printf( $args->before . $item_width . $args->unit . $args->sep1 . $item_height . $args->unit . $args->sep2 . $item_depth . $args->unit . $args->after );
	}
	elseif ( !empty($custom['item_width'] [0]) && !empty($custom['item_height'] [0]) ) {
		if (isset($custom['item_width'])) {
			$item_width = $custom["item_width"] [0];
		}
		if (isset($custom['item_height'])) {
			$item_height = $custom["item_height"] [0];
		}
		printf( $args->before . $item_width . $args->unit . $args->sep1 . $item_height . $args->unit . $args->after );
	}
}
?>
