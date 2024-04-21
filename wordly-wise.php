<?php

/**
 * Plugin Name: Wordly Wise WP
 * Description: A plugin to translate posts using OpenAI.
 * Version: 1.1
 * Author: Cooper
 */

/**
 * Admin Panel
 */
function wordly_wise_add_admin_menu()
{
	add_menu_page('Wordly Wise Settings', 'Wordly Wise', 'manage_options', 'wordly-wise', 'wordly_wise_settings_page', 'dashicons-admin-site-alt3');
}
add_action('admin_menu', 'wordly_wise_add_admin_menu');


/**
 * Settings
 */
function wordly_wise_settings_page()
{
	include 'settings-page.php';
}

function wordly_wise_register_settings()
{
	// Register your settings here using register_setting() function
	register_setting('wordly-wise-settings', 'wordly_wise_api_server', array(
		'default' => 'https://wordly-wise.easyid.dev',
	));
	register_setting('wordly-wise-settings', 'wordly_wise_api_key');
	register_setting('wordly-wise-settings', 'wordly_wise_source_language');
	register_setting('wordly-wise-settings', 'wordly_wise_enabled_languages');
}
add_action('admin_init', 'wordly_wise_register_settings');

function wordly_wise_section_callback()
{
	$quota = wordly_wise_get_quota();
	echo '<p>Configure the settings for Wordly Wise plugin:</p>';
	if ($quota > 0) {
		echo '<p>Your API key usage left: ' . $quota . '</p>';
	}
}

function wordly_wise_add_settings_section()
{
	add_settings_section('wordly-wise-section', 'Wordly Wise Settings', 'wordly_wise_section_callback', 'wordly-wise-settings');
}
add_action('admin_init', 'wordly_wise_add_settings_section');

/**
 * Adds a data-blockid attribute to core/paragraph blocks.
 *
 * @param string $block_content The block content about to be appended.
 * @param array  $block The full block, including name and attributes.
 * @return string Modified block content.
 */
function wordly_wise_add_data_blockid_to_paragraph_blocks($block_content, $block)
{
	// Check if the current block is a core/paragraph block.
	if ('core/paragraph' === $block['blockName']) {
		$md5 = md5($block['innerHTML']);
		$pattern = '/<p( class="[^"]*")?>/';
		$replacement = '<p$1 data-blockid="' . esc_attr($md5) . '">';
		$modified_content = preg_replace($pattern, $replacement, $block_content, 1);

		// Return the modified content.
		return $modified_content;
	}

	// Return the original content for all other block types.
	return $block_content;
}
add_filter('render_block', 'wordly_wise_add_data_blockid_to_paragraph_blocks', 10, 2);

/**
 * JS 
 */
function wordly_wise_enqueue_scripts()
{
	if (is_single() && get_option('wordly_wise_api_key')) {
		wp_enqueue_script('wordly-wise-wp-js', plugin_dir_url(__FILE__) . 'js/wordly-wise.js', array(), '1.0', true);
		wp_enqueue_style('wordly-wise-wp-css', plugin_dir_url(__FILE__) . 'css/wordly-wise.css');

		$available_languages = wordly_wise_get_available_languages();
		$rawEnabledLanguages = get_option('wordly_wise_enabled_languages');
		$enabledLanguages = array_intersect_key($available_languages, array_flip($rawEnabledLanguages));

		wp_localize_script('wordly-wise-wp-js', 'wordlyWiseAjax', array(
			'ajaxUrl' => admin_url('admin-ajax.php'),
			'postId' => get_the_ID(),
			'nonce' => wp_create_nonce('wordly_wise_nonce'),
			'enabledLanguages' => $enabledLanguages,
		));
	}
}
add_action('wp_enqueue_scripts', 'wordly_wise_enqueue_scripts');

/**
 * Get text from unique id
 */
function wordly_wise_get_text_from_unique_id($post_id, $bid)
{
	$post = get_post($post_id);
	if (!$post) {
		wp_send_json_error('Post not found');
		wp_die();
	}

	// Parse blocks from the post content
	$blocks = parse_blocks($post->post_content);
	foreach ($blocks as $block) {
		if ($block['blockName'] !== 'core/paragraph') {
			continue;
		}

		$block_content = $block['innerHTML'];
		$md5 = md5($block_content);
		if ($md5 === $bid) {
			return $block_content;
		}
	}
}

/**
 * Network
 */
function wordly_wise_translate_post()
{
	check_ajax_referer('wordly_wise_nonce', '_ajax_nonce'); // Security check.

	$bid = $_POST['bid'];
	$post_id = $_POST['postId'];
	$lang = $_POST['lang'];
	$text = wordly_wise_get_text_from_unique_id($post_id, $bid);
	$translated_paragraph = wordly_wise_get_translation(get_option('wordly_wise_source_language'), $lang, trim($text));
	wp_send_json_success(array('content' => $translated_paragraph));
}
add_action('wp_ajax_translate_post', 'wordly_wise_translate_post');
add_action('wp_ajax_nopriv_translate_post', 'wordly_wise_translate_post');

function wordly_wise_get_translation($from, $to, $text)
{
	$api_url = get_option('wordly_wise_api_server') . '/api/translation/';
	$api_key = get_option('wordly_wise_api_key');

	// Set the request headers
	$headers = array(
		'Content-Type' => 'application/json',
		'Authorization' => 'Bearer ' . $api_key,
	);

	$data = array(
		'from' => $from,
		'to' => $to,
		'text' => $text,
	);

	$data = json_encode($data); // Convert the array to JSON

	// Make the API request
	$response = wp_remote_post(
		$api_url,
		array(
			'headers' => $headers,
			'body' => $data,
			'blocking' => true,
			'timeout' => 600,
		)
	);

	// Check if the request was successful
	if (is_wp_error($response)) {
		$error_message = $response->get_error_message();
		echo "Something went wrong: $error_message";
	} else {
		$body = wp_remote_retrieve_body($response);
		$data = json_decode($body);
		return $data->result->response;
	}
}

function wordly_wise_get_quota()
{
	$api_key = get_option('wordly_wise_api_key');
	$api_url = get_option('wordly_wise_api_server') . '/api/account/' . $api_key;

	// Set the request headers
	$headers = array(
		'Content-Type' => 'application/json',
	);

	// Make the API request
	$response = wp_remote_get(
		$api_url,
		array(
			'headers' => $headers,
			'blocking' => true,
			'timeout' => 600,
		)
	);

	// Check if the request was successful
	if (is_wp_error($response)) {
		$error_message = $response->get_error_message();
		echo "Something went wrong: $error_message";
	} else {
		$body = wp_remote_retrieve_body($response);
		$data = json_decode($body);
		return $data->result->account->quota;
	}
}

function wordly_wise_get_available_languages()
{
	$available_languages = array(
		'es-ES' => 'Spanish',
		'fr-FR' => 'French',
		'de-DE' => 'German',
		'zh-CN' => 'Chinese',
		'hi-IN' => 'Hindi',
		'ar-SA' => 'Arabic',
		'pt-PT' => 'Portuguese',
		'bn-BD' => 'Bengali',
		'ru-RU' => 'Russian',
		'ja-JP' => 'Japanese',
		'pa-IN' => 'Punjabi',
		'jv-ID' => 'Javanese',
		'ms-MY' => 'Malay',
		'sw-TZ' => 'Swahili',
		'vi-VN' => 'Vietnamese',
		'ko-KR' => 'Korean',
		'ta-IN' => 'Tamil',
		'it-IT' => 'Italian',
		'th-TH' => 'Thai',
		'pl-PL' => 'Polish',
		'uk-UA' => 'Ukrainian',
		'ro-RO' => 'Romanian',
		'nl-NL' => 'Dutch',
		'tr-TR' => 'Turkish',
		'fa-IR' => 'Persian',
	);
	return $available_languages;
}
