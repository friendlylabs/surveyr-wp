<?php

use Carbon_Fields\Carbon_Fields;
use Carbon_Fields\Container;
use Carbon_Fields\Field;

add_action('after_setup_theme', 'load_carbon_fields');
add_action('carbon_fields_register_fields', 'create_options_page');

function load_carbon_fields() :void
{
    Carbon_Fields::boot();
}

function create_options_page() :void
{
    $surveyr_menu_container = Container::make('theme_options', __( 'Surveyr WP' ))
        ->set_icon('dashicons-editor-spellcheck')
        ->add_fields(array(

            # Instance URL
            Field::make('text', 'surveyr_instance_url', 'Surveyr Instance URL')
                ->set_help_text('The URL of the Surveyr instance you want to connect to.')
                ->set_attribute('placeholder', 'https://surveyr.example.com'),

            # passphrase
            Field::make('text', 'surveyr_passphrase', 'Passphrase')
                ->set_help_text('The passphrase you used to encrypt the key.')
                ->set_attribute('placeholder', 'your-passphrase'),

            # Secret Key
            Field::make('text', 'surveyr_api_key', 'Secret Key')
                ->set_help_text('The secret key of the Surveyr instance you want to connect to.')
                ->set_attribute('placeholder', 'your-secret-key')
        ));

}