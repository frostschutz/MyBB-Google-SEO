<?php
/**
 * This file is part of Google SEO plugin for MyBB.
 * Copyright (C) 2008, 2009, 2010 Andreas Klauer <Andreas.Klauer@metamorpher.de>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 */

// Disallow direct access to this file for security reasons
if(!defined("IN_MYBB"))
{
    die("Direct initialization of this file is not allowed.<br /><br />
         Please make sure IN_MYBB is defined.");
}

/* --- Admin CP: --- */

/**
 * The code in this section is only loaded in the Admin CP.
 */

if(defined("IN_ADMINCP"))
{
    global $plugins, $db, $mybb;

    /**
     * Plugin API
     *
     * Please see google_seo/plugin.php for the real Plugin API.
     *
     * Plugin API is huge (>25k) and it's only required on the Admin CP plugin page.
     * Therefore it is loaded only on demand.
     */

    function google_seo_info()
    {
        require_once MYBB_ROOT."inc/plugins/google_seo/plugin.php";
        return google_seo_plugin_info();
    }

    function google_seo_is_installed()
    {
        require_once MYBB_ROOT."inc/plugins/google_seo/plugin.php";
        return google_seo_plugin_is_installed();
    }

    function google_seo_install()
    {
        require_once MYBB_ROOT."inc/plugins/google_seo/plugin.php";
        return google_seo_plugin_install();
    }

    function google_seo_uninstall()
    {
        require_once MYBB_ROOT."inc/plugins/google_seo/plugin.php";
        return google_seo_plugin_uninstall();
    }

    function google_seo_activate()
    {
        require_once MYBB_ROOT."inc/plugins/google_seo/plugin.php";
        return google_seo_plugin_activate();
    }

    function google_seo_deactivate()
    {
        require_once MYBB_ROOT."inc/plugins/google_seo/plugin.php";
        return google_seo_plugin_deactivate();
    }

    /**
     * Load the language variables on the settings page.
     */

    $plugins->add_hook("admin_config_settings_begin", "google_seo_lang_settings");

    function google_seo_lang_settings()
    {
        global $lang;
        $lang->load("googleseo_settings");
    }

    /**
     * Override some Google SEO settings if the database table is missing
     * to avoid Admin CP becoming unuseable when the google_seo table is
     * deleted manually or otherwise lost.
     */
    if(!$db->table_exists("google_seo"))
    {
        $mybb->settings['google_seo_404'] = 0;
        $mybb->settings['google_seo_meta'] = 0;
        $mybb->settings['google_seo_redirect'] = 0;
        $mybb->settings['google_seo_sitemap'] = 0;
        $mybb->settings['google_seo_url'] = 0;
    }
}

/* --- Prerequisites: --- */

/*
 * Unfortunately global.php sets mb_internal_encoding only after running
 * the global_start hook (or maybe even not at all). We need it set it,
 * so we do it ourselves.
 */

if(function_exists('mb_internal_encoding'))
{
    @mb_internal_encoding('UTF-8');
}

/*
 * Load the translation file for Google SEO.
 *
 */

global $plugins;

$plugins->add_hook("global_start", "google_seo_lang");

function google_seo_lang()
{
    global $lang;
    $lang->load("googleseo");
}

/* --- Submodules: --- */

/**
 * Google SEO is split into separate files.
 * Only the functionality that is actually enabled will be loaded.
 * If all options are disabled (default) the plugin does nothing at all.
 */

global $settings;

foreach(array('404', 'meta', 'redirect', 'sitemap', 'url') as $module)
{
    if($settings["google_seo_$module"])
    {
        require_once MYBB_ROOT."inc/plugins/google_seo/$module.php";
    }
}

/* --- End of file. --- */
?>
