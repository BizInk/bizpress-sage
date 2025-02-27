<?php
/**
 * if accessed directly, exit.
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
add_filter( 'display_post_states', 'bizpress_sage_post_states', 10, 2 );
function bizpress_sage_post_states( $post_states, $post ) {
	if( !function_exists( 'cxbc_get_option' ) ) return $post_states;
	$sagePageID =  cxbc_get_option( 'bizink-client_basic', 'sage_content_page' );
    if ( $sagePageID == $post->ID ) {
        $post_states['bizpress_sage'] = __('BizPress Sage Resources','bizink-client');
    }
    return $post_states;
}

function sage_settings_fields( $fields, $section ) {
	$pageselect = false;
	if(defined('CXBPC')){
		$bizpress = get_plugin_data( CXBPC );
		$v = intval(str_replace('.','',$bizpress['Version']));
		if($v >= 151){
			$pageselect = true;
		}
	}
	
	if('bizink-client_basic' == $section['id']){
		$fields['sage_content_page'] = array(
			'id'      => 'sage_content_page',
			'label'     => __( 'Sage Resources', 'bizink-client' ),
			'type'      => $pageselect ? 'pageselect':'select',
			'desc'      => __( 'Select the page to show the content. This page must contain the <code>[bizpress-content]</code> shortcode.', 'bizink-client' ),
			'options'	=> cxbc_get_posts( [ 'post_type' => 'page' ] ),
			'required'	=> false,
			'default_page' => [
				'post_title' => 'Sage Resources',
				'post_content' => '[bizpress-content]',
				'post_status' => 'publish',
				'post_type' => 'page'
			]
		);
	}
	
	if('bizink-client_content' == $section['id']){
		$fields['sage_label'] = array(
			'id' => 'sage',
	        'label'	=> __( 'Bizpress Sage Resources', 'bizink-client' ),
	        'type' => 'divider'
		);
		$fields['sage_title'] = array(
			'id' => 'sage_title',
			'label'     => __( 'Sage Resources Title', 'bizink-client' ),
			'type'      => 'text',
			'default'   => __( 'Sage Resources Resources', 'bizink-client' ),
			'required'	=> true,
		);
		$fields['sage_desc'] = array(
			'id'      	=> 'sage_desc',
			'label'     => __( 'Sage Resources Description', 'bizink-client' ),
			'type'      => 'textarea',
			'default'   => __( 'Free resources to help you use Sage Resources.', 'bizink-client' ),
			'required'	=> true,
		);
	}

	return $fields;
}
add_filter( 'cx-settings-fields', 'sage_settings_fields', 10, 2 );

function sage_content( $types ) {
	$types[] = [
		'key' 	=> 'sage_content_page',
		'type'	=> 'sage-content'
	];
	return $types;
}
add_filter( 'bizink-content-types', 'sage_content' );

if( !function_exists( 'bizink_get_sage_page_object' ) ){
	function bizink_get_sage_page_object(){
		if( !function_exists( 'cxbc_get_option' ) ) return false;
		$post_id = cxbc_get_option( 'bizink-client_basic', 'sage_content_page' );
		$post = get_post( $post_id );
		return $post;
	}
}

add_action( 'init', 'bizink_sage_init');
function bizink_sage_init(){
	$post = bizink_get_sage_page_object();
	if( is_object( $post ) && get_post_type( $post ) == "page" ){
		add_rewrite_tag('%'.$post->post_name.'%', '([^&]+)', 'bizpress=');
		add_rewrite_rule('^'.$post->post_name . '/([^/]+)/?$','index.php?pagename=sage-resources&bizpress=$matches[1]','top');
		add_rewrite_rule("^".$post->post_name."/([a-z0-9-]+)[/]?$",'index.php?pagename=sage-resources&bizpress=$matches[1]','top');
		add_rewrite_rule("^".$post->post_name."/topic/([a-z0-9-]+)[/]?$",'index.php?pagename=sage-resources&topic=$matches[1]','top');
		add_rewrite_rule("^".$post->post_name."/type/([a-z0-9-]+)[/]?$" ,'index.php?pagename=sage-resources&type=$matches[1]','top');
		
		add_rewrite_tag('%sage_resources.xml%', '([^&]+)', 'bizpressxml=');
		add_rewrite_rule('^(sage_resources\.xml)?$','index.php?bizpressxml=sage_resources','top');

		if(get_option('bizpress_sage_flush_update',0) < 1){
			flush_rewrite_rules();
			update_option('bizpress_sage_flush_update',1);
		}
	}
}

add_action('parse_request','bizpress_sagexml_request', 10, 1);
function bizpress_sagexml_request($wp){
	if ( array_key_exists( 'bizpressxml', $wp->query_vars ) && $wp->query_vars['bizpressxml'] == 'sage_resources'){
		$post = bizink_get_sage_page_object();
		if( is_object( $post ) && get_post_type( $post ) == "page" ){
			$data = get_transient("bizinktype_".md5('sage-content'));
			if(empty($data)){
				$data = bizink_get_content('sage-content', 'topics');
				set_transient( "bizinktype_".md5('sage-content'), $data, (DAY_IN_SECONDS * 2) );
			}
			header('Content-Type: text/xml; charset=UTF-8');
			echo '<?xml version="1.0" encoding="UTF-8"?>';
			echo '<?xml-stylesheet type="text/xsl" href="'. plugins_url('wordpress-seo/css/main-sitemap.xsl', dirname(__FILE__)) .'"?>';
			echo '<urlset xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:image="http://www.google.com/schemas/sitemap-image/1.1" xsi:schemaLocation="http://www.sitemaps.org/schemas/sitemap/0.9 http://www.sitemaps.org/schemas/sitemap/0.9/sitemap.xsd http://www.google.com/schemas/sitemap-image/1.1 http://www.google.com/schemas/sitemap-image/1.1/sitemap-image.xsd" xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';
			
			echo '<url>';
			echo '<loc>'.get_home_url().'/'.$post->post_name.'</loc>';
			echo '</url>';
			
			if(empty($data->posts) == false){
				foreach($data->posts as $item){
					echo '<url>';
					echo '<loc>'.get_home_url().'/'.$post->post_name.'/'. $item->slug .'</loc>';
					if($item->thumbnail){
						echo '<image:image>';
						echo '<image:loc>'. $item->thumbnail .'</image:loc>';
						echo '</image:image>'; 
					}
					echo '</url>';
				}
			}
			echo '</urlset>';
		}
		die();
	}
}

add_filter('query_vars', 'bizpress_sage_qurey');
function bizpress_sage_qurey($vars) {
    $vars[] = "bizpress";
    return $vars;
}

add_filter('query_vars', 'bizpress_sagexml_query');
function bizpress_sagexml_query($vars) {
    $vars[] = "bizpressxml";
    return $vars;
}

function bizpress_sage_sitemap_custom_items( $sitemap_custom_items ) {
    $sitemap_custom_items .= '
	<sitemap>
		<loc>'.get_home_url().'/sage_resources.xml</loc>
	</sitemap>';
    return $sitemap_custom_items;
}

add_filter( 'wpseo_sitemap_index', 'bizpress_sage_sitemap_custom_items' );