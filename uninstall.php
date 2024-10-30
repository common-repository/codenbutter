<?php

if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

if (get_option('codenbutter_site_id') != false) {
    delete_option('codenbutter_site_id');
}
