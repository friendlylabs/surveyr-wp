<?php


declare(strict_types=1);

use SurveyrWP\SurveyrWP;
use SurveyrWP\Utils\Fetch;
use SurveyrWP\Utils\Template;

require_once SurveyrWP::PLUGIN_DIR  . '/utils/Fetch.php';
require_once SurveyrWP::PLUGIN_DIR  . '/utils/Template.php';

add_shortcode('surveyr_form', 'surveyr_form_shortcode');
add_action('rest_api_init', fn() => register_rest_route(
    'surveyr/v1',
    '/submit/(?P<formId>[a-zA-Z0-9]+)',
    [
        'methods'  => 'POST',
        'callback' => 'surveyr_form_submit_callback',
    ]
));

/**
 * Shortcode callback function
 * 
 * @param array $attributes
 * @return string
 */
function surveyr_form_shortcode(array $attributes): string
{
    $attributes = shortcode_atts(
        ['form_id' => ''],
        $attributes,
        'surveyr_form'
    );

    return surveyr_form_render($attributes['form_id']);
}

/**
 * Renders the form
 * 
 * @param string $formId
 * @return string
 */
function surveyr_form_render(string $formId): string
{
    surveyr_load_assets();

    $baseUrl = carbon_get_theme_option('surveyr_instance_url') ?: throw new RuntimeException('Instance URL not configured.');
    $passphrase = carbon_get_theme_option('surveyr_passphrase') ?: throw new RuntimeException('Passphrase not configured.');
    $bearerToken = carbon_get_theme_option('surveyr_api_key') ?: throw new RuntimeException('API Key not configured.');

    try {
        $form = Fetch::get("$baseUrl/api/forms/fetch/$formId", [ 
            'headers' => [
                'Authorization' => "Bearer $bearerToken",
            ],
            'body' => [
                'passphrase' => $passphrase,
            ],
        ]);

        $formData = json_decode($form['body'], false, flags: JSON_THROW_ON_ERROR);
        if(!$formData->status) {
            return '<p>Error loading form. Please try again later.</p>';
        }

        return Template::view('form', [
            'formId' => $formId,
            'render' => $formData,
        ]);
    }
    
    catch (Throwable $e) {
        error_log('Failed to fetch form: ' . $e->getMessage());
        return '<p>Error loading form. Please try again later.</p>.';
    }
}

/**
 * Loads assets for the form
 * 
 * @return void
 */
function surveyr_load_assets(): void
{
    // this is to include the assets for the form only once
    if(defined('surveyr_assets_loaded')) return;
    define('surveyr_assets_loaded', true);

    $basePath = plugin_dir_url(__FILE__) . '../assets/';

    // CSS assets
    wp_enqueue_style('surveyr-css', "{$basePath}css/surveyr.css");
    wp_enqueue_style('surveyr-toast-css', "{$basePath}vendor/toast/toast.min.css");
    wp_enqueue_style('surveyjs-core-css', "{$basePath}vendor/surveyjs/default.min.css");

    // JS assets
    wp_enqueue_script('surveyr-js', "{$basePath}js/surveyr.js", ['jquery'], null, true);
    wp_enqueue_script('surveyr-toast-js', "{$basePath}vendor/toast/toast.min.js", [], null, true);
    wp_enqueue_script('surveyjs-core-js', "{$basePath}vendor/surveyjs/survey.core.min.js", [], null, true);
    wp_enqueue_script('surveyjs-ui-js', "{$basePath}vendor/surveyjs/survey-js-ui.min.js", [], null, true);
    wp_enqueue_script('surveyjs-themes-js', "{$basePath}vendor/surveyjs/themes/index.min.js", [], null, true);
}

/**
 * Form submission callback
 * 
 * @param WP_REST_Request $request
 * @return WP_REST_Response
 */
function surveyr_form_submit_callback(WP_REST_Request $request): WP_REST_Response
{
    // fetch the necessary configuration options
    $baseUrl = carbon_get_theme_option('surveyr_instance_url') ?: throw new RuntimeException('Instance URL not configured.');

    $passphrase = carbon_get_theme_option('surveyr_passphrase') ?: throw new RuntimeException('Passphrase not configured.');
    $bearerToken = carbon_get_theme_option('surveyr_api_key') ?: throw new RuntimeException('API Key not configured.');


    // validate request
    $formId = $request->get_param('formId') ?? '';
    $content = $request->get_param('content') ?? [];

    if (empty($formId) || empty($content)) {
        return new WP_REST_Response(
            ['status' => false, 'message' => 'Invalid form ID or content'],
            400
        );
    }

    // Send the form submission to the Surveyr instance
    try {
        $response = Fetch::post("$baseUrl/api/collection/store", [
            'headers' => [
                'Authorization' => "Bearer $bearerToken",
            ],
            
            'body' => [
                'formId'  => $formId,
                'passphrase' => $passphrase,
                'content' => json_encode($content, JSON_THROW_ON_ERROR),
            ],
        ]);

        $output = json_decode($response['body'], true, flags: JSON_THROW_ON_ERROR);
        return new WP_REST_Response(
            ['status' => $output['status'], 'message' => $output['message'] ?? 'Form submitted successfully'],
            $output['status'] ? 200 : 400
        );
    } catch (Throwable $e) {
        error_log('Form submission error: ' . $e->getMessage());

        return new WP_REST_Response(
            ['status' => false, 'message' => 'Form submission failed. Please try again later.'],
            500
        );
    }
}