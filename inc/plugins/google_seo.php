<?php
/**
 * This file is part of Google SEO plugin for MyBB.
 * Copyright (C) 2008-2011 Andreas Klauer <Andreas.Klauer@metamorpher.de>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public License
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
     * Fizzle the cache on settings change.
     */
    $plugins->add_hook('admin_config_settings_change_commit', 'google_seo_fizzle');

    function google_seo_fizzle()
    {
        global $mybb, $cache;

        // Kill the cache when someone changes URL settings the obvious way.
        // Otherwise the cache may cause unnecessary redirects.
        if(array_key_exists('google_seo_url_threads', $mybb->input['upsetting']))
        {
            $cache->update('google_seo_url', NULL);
        }
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
 * Constants. Required by URL and Sitemap.
 */
define('GOOGLE_SEO_USER', 1);
define('GOOGLE_SEO_ANNOUNCEMENT', 2);
define('GOOGLE_SEO_FORUM', 3);
define('GOOGLE_SEO_THREAD', 4);
define('GOOGLE_SEO_EVENT', 5);
define('GOOGLE_SEO_CALENDAR', 6);

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

/*
 * Expand a string using values from an associative array.
 * This function is required by several submodules.
 *
 */
function google_seo_expand($string, $array)
{
    $strtr = array();

    foreach($array as $key => $value)
    {
        $strtr["{{$key}}"] = $value;   // {var} style
        $strtr["{\${$key}}"] = $value; // {$var} style
    }

    return strtr($string, $strtr);
}

/*
 * Guess or query a thread id from a post id.
 * Required by both URL and Redirect.
 */
function google_seo_tid($pid, $tid=0, $mode='default', $limit=1)
{
    global $db, $style, $thread, $post;
    global $google_seo_tid;

    if($google_seo_tid[$pid] === NULL)
    {
        // trust the given tid
        if($tid > 0)
        {
            $tid = (int)$tid;
        }

        // or guess tid
        else if($style['pid'] == $pid && $style['tid'] > 0)
        {
            $tid = (int)$style['tid'];
        }

        else if($thread['firstpost'] == $pid && $thread['tid'] > 0)
        {
            $tid = (int)$thread['tid'];
        }

        else if($post['pid'] == $pid && $post['tid'] > 0)
        {
            $tid = (int)$post['tid'];
        }

        else if($google_seo_tid[-$pid] !== NULL)
        {
            $tid = $google_seo_tid[-$pid];
        }

        // and/or query tid
        if($limit > 0
           && ($mode == 'verify' || ($tid <= 0 && $mode != 'ignore')))
        {
            $pid = (int)$pid;
            $db->google_seo_query_limit--;
            $query = $db->simple_select('posts', 'tid', "pid={$pid}");
            $tid = (int)$db->fetch_field($query, 'tid');

            // positive pid cache for trusted tid
            $google_seo_tid[$pid] = $tid;
        }

        else if($tid > 0)
        {
            // negative pid cache for previously guessed tid
            $google_seo_tid[-$pid] = $tid;
        }

        return $tid;
    }

    return $google_seo_tid[$pid];
}

/*
 * Handle special dynamic URL parameter
 * Required by both URL and Redirect
 */
function google_seo_dynamic($url)
{
    // This is complicated because of ?page&page=2, page being the thread title.

    $query = explode('?', $url);
    $query = $query[1];

    if($query)
    {
        $query = str_replace('&amp;', '&', $query);
        $query = explode('&', $query);

        foreach($query as $key)
        {
            // pick the first parameter that doesn't have a value
            if(strpos($key, '=') === false)
            {
                return urldecode($key);
            }
        }
    }
}

/*
 * Encode URLs, leaving some special characters intact.
 * Required by both URL and Redirect
 */
function google_seo_encode($url)
{
    $url = rawurlencode((string)$url);

    $url = strtr($url, array(
                     '%24' => '$',
                     '%26' => '&',
                     '%2F' => '/',
                     '%3A' => ':',
                     '%3B' => ';',
                     '%3D' => '=',
                     '%3F' => '?',
                     '%40' => '@',
                     '%7B' => '{',
                     '%7D' => '}',
                     ));

    return $url;
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
    if($settings["google_seo_{$module}"])
    {
        require_once MYBB_ROOT."inc/plugins/google_seo/{$module}.php";
    }
}

/* --- End of file. --- */
?>
