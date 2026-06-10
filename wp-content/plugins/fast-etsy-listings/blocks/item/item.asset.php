<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly
return array(
    'dependencies' => array(
			'wp-blocks',
		  'wp-i18n',
			'wp-element',
		  'wp-components',
		  'wp-block-editor'
    ),
    'version'      => filemtime( __DIR__ . "/item.js" ),
);