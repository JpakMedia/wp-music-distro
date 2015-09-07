<?php
/*
 * WP MusicDistro: Admin Dashboard Modifications
 *
 * @description         Removes all EDD stuff not needed
 * @author              Chris Christoff, Jordan Pakrosnis
*/


//-- If Admin --//
if(is_admin())
{

    // Run edd_loaded() after plugin is loaded
	add_action('plugins_loaded', 'edd_loaded');

    /**
     * Post-Load Modifications
     */
    function edd_loaded() {

        add_filter('gettext', 'remove_admin_stuff', 20, 3);

        if (!defined('EDD_DISABLE_ARCHIVE')) {
		  define('EDD_DISABLE_ARCHIVE', false);
        }

        if (!defined('EDD_DISABLE_REWRITE')) {
            define('EDD_DISABLE_REWRITE', false);
        }

        // Change Slug
        if (!defined('EDD_SLUG')) {
            define('EDD_SLUG', 'Arrangements');
        }

    } // edd_loaded()




    // Disable Post Archives & Redirect Download "Single" to Home URL
	add_filter('edd_download_post_type_args', 'disable_archives');
	add_action('template_redirect', 'download_redirect_post');

    /**
     * Remove Download Archive and Rewrite
     */
    function disable_archives($download_args) {
        $download_args['has_archive'] = false;
        $download_args['rewrite'] = false;
        return $download_args;
	}

    /**
     * Redirect Download Single to Home
     */
	function download_redirect_post() {
        $queried_post_type = get_query_var('post_type');

        if (is_single() && 'download' == $queried_post_type) {
            wp_redirect(home_url() , 301);
            exit;
        }

    } // download_redirect_post()




    /**
     * Remove & Modify Admin Dashboard Elements
     */
	function remove_admin_stuff($translated_text, $untranslated_text, $domain) {

        // If not Download
        if (get_current_post_type() !== 'download') {
            return $untranslated_text;
        }

        switch ($untranslated_text) {

            case 'Show all tags':
                $translated_text = __('Show all Arrangement Types', 'edd_music');
                break;

            case 'Show all categories':
                $translated_text = __('Show all Band & Instrument(s)', 'edd_music');
                break;

            case 'Arrangement Tags':
                $translated_text = __('Arrangement Types', 'edd_music');
                break;

            case 'Search Categories':
                $translated_text = __('Search Band & Instrument(s)', 'edd_music');
                break;

            case 'Search Tags':
                $translated_text = __('Search Arrangement Types', 'edd_music');
                break;

            case 'Popular Tags':
                $translated_text = __('Popular Arrangement Types', 'edd_music');
                break;

            case 'Add New Category':
                $translated_text = __('Add New Band & Instrument(s)', 'edd_music');
                break;

            case 'Add New Tag':
                $translated_text = __('Add New Arrangement Type', 'edd_music');
                break;

            case 'Tags':
                $translated_text = __('Arrangement Type', 'edd_music');
                break;

            case 'Category':
            case 'Categories':
                $translated_text = __('Band & Instrument(s)', 'edd_music');
                break;

            case 'Download Tags':
                $translated_text = __('Arrangement Type', 'edd_music');
                break;

            case 'Arrangement Categories':
                $translated_text = __('Band & Instrument(s)', 'edd_music');
                break;

            case 'File Downloads:':
                $translated_text = "";
                break;
              //add more items

	  } // switch

	  return $translated_text;

    } // remove_admin_stuff()




    // Not exactly sure what this does?
	add_action('edd_download_file_table_row', 'output_nounce');

    function output_nounce() {
        echo wp_nonce_field('metabox.php', 'edd_download_meta_box_nonce');
	}

	function get_current_post_type() {

        global $post, $typenow, $current_screen;

        // We have a post so we can just get the post type from that
        if ($post && $post->post_type) return $post->post_type;

        // Check the global $typenow - set in admin.php
        elseif ($typenow) return $typenow;

        // Check the global $current_screen object - set in sceen.php
        elseif ($current_screen && $current_screen->post_type) return $current_screen->post_type;

        // Lastly check the post_type querystring
        elseif (isset($_REQUEST['post_type'])) return sanitize_key($_REQUEST['post_type']);

        // We do not know the post type!
        return null;

	} // get_current_post_type()




	add_action('admin_menu', 'edd_menu_items', 10);
    /**
     * Modify Admin Dashboard Menu for EDD
     */
    function edd_menu_items() {
        global $edd_upgrades_screen;
        $edd_upgrades_screen = add_submenu_page(null, __('EDD Upgrades', 'edd') , __('EDD Upgrades', 'edd') , 'install_plugins', 'edd-upgrades', 'edd_upgrades_screen');
	}




	add_filter('edd_download_columns', 'remove_columns');
    /**
     * Remove Admin Columns
     */
	function remove_columns($columns) {
        unset($columns['price']);
        unset($columns['sales']);
        unset($columns['earnings']);
        unset($columns['shortcode']);

        return $columns;
	}




	remove_action('admin_menu', 'edd_add_options_link', 10);
    /**
     * Rename Menu Labels
     */
	function pw_edd_product_labels($labels) {

        $labels = array(
            'singular' => __('Arrangement', 'your-domain') ,
            'plural' => __('Arrangements', 'your-domain')
        );

        return $labels;

    } // pw_edd_product_labels




	add_filter('edd_default_downloads_name', 'pw_edd_product_labels');
    /**
     * Meta Boxes?
     */
    function edd_add_download_meta_boxes() {

        $post_types = apply_filters('edd_download_metabox_post_types', array(
            'download'
        ));

        foreach ($post_types as $post_type) {
            /** Product Files (and bundled products) **/
            add_meta_box('edd_product_files', sprintf(__('%1$s Files', 'edd') , edd_get_label_singular() , edd_get_label_plural()) , 'edd_render_files_meta_box', $post_type, 'normal', 'high');
        }

    } // edd_add_download_meta_boxes()


    // Switch out metaboxes (titles?)
	remove_action('add_meta_boxes', 'edd_add_download_meta_box');
	add_action('add_meta_boxes', 'edd_add_download_meta_boxes');




    // Add support for Title & Revisions
	add_filter('edd_download_supports', 'edd_supports');
	function edd_supports($supports) {
        return array(
            'title',
            'revisions'
        );
	}


    remove_action('edd_meta_box_files_fields', 'edd_render_product_type_field', 10);




    add_action('do_meta_boxes', 'change_meta_box_titles');
    /**
     * Modify Meta Box Titles
     */
    function change_meta_box_titles() {
        global $wp_meta_boxes;
        $wp_meta_boxes['download']['side']['core']['download_categorydiv']['title'] = 'Band & Instrument(s)';
        $wp_meta_boxes['download']['side']['core']['tagsdiv-download_tag']['title'] = 'Arrangement Type';
	}




	add_action('admin_head', 'edd_css');
    /**
     * Admin CSS Modification
     */
	function edd_css() {
        if (get_current_post_type() !== 'download') {
            return;
        }

        echo "<style>.nosubsub h2{ display: none !important; } .wrap { margin: 0 0 0 20 !important; } </style>";

    } // edd_css()


} // EndIf
