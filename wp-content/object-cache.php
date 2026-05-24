<?php

/**
 * Plugin Name: Memcached
 * Description: Memcached backend for the WP Object Cache.
 * Version: 4.0.0
 * Author: Automattic
 * Plugin URI: https://wordpress.org/plugins/memcached/
 * License: GPLv2 or later
 *
 * This file is require'd from wp-content/object-cache.php
 */

if ( extension_loaded( 'memcached' ) ) {
    define( 'AUTOMATTIC_MEMCACHED_USE_MEMCACHED_EXTENSION', true );
    require_once __DIR__ . '/drop-ins/wp-cache-memcached/object-cache.php';
} elseif ( extension_loaded( 'memcache' ) ) {
    define( 'AUTOMATTIC_MEMCACHED_USE_MEMCACHED_EXTENSION', false );
    require_once __DIR__ . '/drop-ins/wp-cache-memcached/object-cache.php';
}
