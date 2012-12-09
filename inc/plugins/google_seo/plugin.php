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

if(!defined("PLUGINLIBRARY"))
{
    define("PLUGINLIBRARY", MYBB_ROOT."inc/plugins/pluginlibrary.php");
}

global $lang;
$lang->load("googleseo_plugin");

/* --- Plugin Info: --- */

/**
 * Basic information about the plugin.
 *
 * @return Plugin info array.
 */
function google_seo_plugin_info()
{
    global $lang, $settings, $plugins_cache;

    // Check for edit action.
    google_seo_plugin_edit();

    $info = array(
        "name"          => "Google SEO",
        "website"       => "http://mods.mybb.com/view/google-seo",
        "description"   => "{$lang->googleseo_plugin_description}",
        "author"        => "Andreas Klauer",
        "authorsite"    => "mailto:Andreas.Klauer@metamorpher.de",
        "version"       => "1.6.5",
        "guid"          => "8d12371391e1c95392dd567617e40f7f",
        "compatibility" => "16*",
    );

    // Provide some additional status information, if the plugin is enabled.
    if(google_seo_plugin_is_installed() &&
       is_array($plugins_cache) &&
       is_array($plugins_cache['active']) &&
       $plugins_cache['active']['google_seo'])
    {
        $info['description'] = $info['description'].google_seo_plugin_status();
    }

    return $info;
}

/**
 * Additional status information about the plugin.
 *
 * @return string status string
 */
function google_seo_plugin_status()
{
    global $lang, $mybb, $config, $settings, $db;
    global $PL;
    $PL or require_once PLUGINLIBRARY;

    $success = array();
    $warning = array();
    $error = array();
    $htaccess = array();
    $lines = array();

    if(!$settings['google_seo_url']
       || !$settings['google_seo_url_cache'])
    {
        // Good place as any to delete cache if disabled.
        $PL->cache_delete('google_seo_url');
    }

    // Required for 404 and URL
    $base = $settings['bburl'];
    $base = preg_replace('#^[^/]*://[^/]*#', '', $base);

    // Warning about disabled plugins.
    if(defined("NO_PLUGINS") || $settings['no_plugins'])
    {
        $warning[] = $lang->googleseo_plugin_no_plugins;
    }

    // UTF-8 is required:
    if($mybb->config['database']['encoding'] != 'utf8')
    {
        $warning[] = $lang->sprintf($lang->googleseo_plugin_warn_encoding,
                                    $mybb->config['database']['encoding']);
    }

    // Google SEO 404:
    if($settings['google_seo_404'])
    {
        $success[] = $lang->googleseo_plugin_404;

        $htaccess[] = array("ErrorDocument 404 {$base}/misc.php?google_seo_error=404",
                            0,
                            $lang->googleseo_plugin_htaccess_404);
    }

    else
    {
        $error[] = $lang->googleseo_plugin_404;
    }

    // Google SEO Meta:
    if($settings['google_seo_meta'])
    {
        $success[] = $lang->googleseo_plugin_meta;
    }

    else
    {
        $error[] = $lang->googleseo_plugin_meta;
    }

    // Google SEO Redirect:
    if($settings['google_seo_redirect'])
    {
        $success[] = $lang->googleseo_plugin_redirect;

        if(!$settings['google_seo_url'])
        {
            $warning[] = $lang->googleseo_plugin_redirect_warn_url;
        }

        $current_url = google_seo_redirect_current_url();
        $pos = my_strpos($current_url, "/{$config['admin_dir']}/index.php");

        if($pos)
        {
            $current_url = my_substr($current_url, 0, $pos);
        }


        if(!$settings['bburl'] || $settings['bburl'] != $current_url)
        {
            $warning[] = $lang->sprintf($lang->googleseo_plugin_redirect_warn_bburl,
                                        htmlspecialchars($settings['bburl'], ENT_COMPAT, "UTF-8"),
                                        htmlspecialchars($current_url), ENT_COMPAT, "UTF-8");
        }
    }

    else
    {
        $error[] = $lang->googleseo_plugin_redirect;
    }

    // Google SEO Sitemap:
    if($settings['google_seo_sitemap'])
    {
        if($settings['google_seo_sitemap_url'])
        {
            $link = "{$settings['bburl']}/{$settings['google_seo_sitemap_url']}";

            $htaccess[] = array($settings['google_seo_sitemap_url'],
                                'misc.php',
                                'google_seo_sitemap',
                                $lang->googleseo_plugin_htaccess_sitemap);
        }

        else
        {
            $link = "{$settings['bburl']}/misc.php?google_seo_sitemap={url}";
        }

        $link = google_seo_expand($link, array('url' => 'index'));

        $success[] = "<a href=\"{$link}\" target=\"_blank\">{$lang->googleseo_plugin_sitemap}</a>";
    }

    else
    {
        $error[] = $lang->googleseo_plugin_sitemap;
    }

    // Google SEO URL:
    if($settings['google_seo_url'])
    {
        $success[] = $lang->googleseo_plugin_url;

        if($settings['google_seo_url_translate'] &&
           !file_exists(MYBB_ROOT."inc/plugins/google_seo/translate.php"))
        {
            $warning[] = $lang->googleseo_plugin_url_warn_translate;
        }

        if($settings['google_seo_url_forums'])
        {
            $htaccess[] = array($settings['google_seo_url_forums'],
                                'forumdisplay.php',
                                'google_seo_forum',
                                $lang->googleseo_plugin_htaccess_forums);
        }

        if($settings['google_seo_url_threads'])
        {
            $htaccess[] = array($settings['google_seo_url_threads'],
                                'showthread.php',
                                'google_seo_thread',
                                $lang->googleseo_plugin_htaccess_threads);
        }

        if($settings['google_seo_url_announcements'])
        {
            $htaccess[] = array($settings['google_seo_url_announcements'],
                                'announcements.php',
                                'google_seo_announcement',
                                $lang->googleseo_plugin_htaccess_announcements);
        }

        if($settings['google_seo_url_users'])
        {
            $htaccess[] = array($settings['google_seo_url_users'],
                                'member.php',
                                'google_seo_user',
                                $lang->googleseo_plugin_htaccess_users,
                                'action=profile&');
        }

        if($settings['google_seo_url_calendars'])
        {
            $htaccess[] = array($settings['google_seo_url_calendars'],
                                'calendar.php',
                                'google_seo_calendar',
                                $lang->googleseo_plugin_htaccess_calendars);
        }

        if($settings['google_seo_url_events'])
        {
            $htaccess[] = array($settings['google_seo_url_events'],
                                'calendar.php',
                                'google_seo_event',
                                $lang->googleseo_plugin_htaccess_events,
                                'action=event&');
        }
    }

    else
    {
        $error[] = $lang->googleseo_plugin_url;
    }

    // URL scheme conflict detection
    $pattern = $settings['google_seo_url_punctuation'];

    if($pattern)
    {
        // Escape the pattern.
        // (preg_quote breaks UTF-8 and doesn't escape -)
        $pattern = preg_replace("/[\\\\\\^\\-\\[\\]\\/]/u",
                                "\\\\\\0",
                                $pattern);
    }

    for($a=count($htaccess); $a--; )
    {
        if(!$htaccess[$a][1])
        {
            continue;
        }

        for($b=$a; $b--; )
        {
            if(!$htaccess[$b][1])
            {
                continue;
            }

            // for any pair a-b:

            $rule_a = explode('?', $htaccess[$a][0]);
            $rule_a = $rule_a[0];
            $rule_b = explode('?', $htaccess[$b][0]);
            $rule_b = $rule_b[0];

            $test_a = google_seo_expand($rule_a, array('url' => ''));
            $test_b = google_seo_expand($rule_b, array('url' => ''));

            if($pattern)
            {
                $test_a = preg_replace("/^[$pattern]+|[$pattern]+$/u",
                                       "",
                                       $test_a);
                $test_b = preg_replace("/^[$pattern]+|[$pattern]+$/u",
                                       "",
                                       $test_b);
            }

            $test_ab = google_seo_expand($rule_a, array('url' => $test_b));
            $test_ba = google_seo_expand($rule_b, array('url' => $test_a));

            $regexp_a = preg_quote($rule_a, '#');
            $regexp_a = preg_replace('/\\\\{(\\\\\\$|)url\\\\}/', '{url}', $regexp_a);
            $regexp_a = google_seo_expand($regexp_a, array('url' => '([^./]+)'));
            $regexp_b = preg_quote($rule_b, '#');
            $regexp_b = preg_replace('/\\\\{(\\\\\\$|)url\\\\}/', '{url}', $regexp_b);
            $regexp_b = google_seo_expand($regexp_b, array('url' => '([^./]+)'));

            // Could there be a conflict?
            if((preg_match("#^{$regexp_a}\$#u", $test_ab) && preg_match("#^{$regexp_b}\$#u", $test_ab))
               || (preg_match("#^{$regexp_a}\$#u", $test_ba) && preg_match("#^{$regexp_b}\$#u", $test_ba))
               || (preg_match("#^{$regexp_a}\$#u", $test_ab) && preg_match("#^{$regexp_a}\$#u", $test_ba))
               || (preg_match("#^{$regexp_b}\$#u", $test_ab) && preg_match("#^{$regexp_b}\$#u", $test_ba)))
            {
                $warning[] = $lang->sprintf($lang->googleseo_plugin_htaccess_conflict,
                                            htmlspecialchars_uni($htaccess[$a][0]),
                                            htmlspecialchars_uni($htaccess[$b][0]));
            }
        }
    }

    // Check htaccess.
    if(count($htaccess) || !$settings['google_seo_404'])
    {
        $file = @file_get_contents(MYBB_ROOT.".htaccess");

        if($file)
        {
            $file = preg_replace('/^[\s\t]*#.*$/m', '', $file);
            $file = preg_replace("/\s*\n\s*/m", "\n", $file);
            $file .= "\n"; // no newline at end of file
        }

        foreach($htaccess as $v)
        {
            if($v[1])
            {
                $rewrite = 1;
                $rule = explode('?', $v[0]); // ignore dynamic part, if present
                $rule = $rule[0];

                if($rule != $v[1])
                {
                    $rule = preg_quote($rule);
                    $rule = preg_replace('/\\\\{(\\\\\\$|)url\\\\}/', '{url}', $rule);

                    if(strpos($rule, '{url}') !== false)
                    {
                        $url = "([^./]+)";
                        $rule = google_seo_expand($rule, array('url' => $url));
                        $rule = "RewriteRule ^{$rule}\$ {$v[1]}?{$v[4]}{$v[2]}=\$1 [L,QSA,NC]";
                    }

                    else
                    {
                        $rule = "RewriteRule ^{$rule}\$ {$v[1]}?{$v[4]} [L,QSA,NC]";
                    }

                    if(strpos($file, "{$rule}\n") === false)
                    {
                        $line = "# {$v[3]}:\n{$rule}\n";
                    }
                }
            }

            else
            {
                if(strpos($file, "{$v[0]}\n") === false)
                {
                    $line = "# {$v[2]}:\n{$v[0]}\n";
                }
            }

            if($line)
            {
                $lines[] = $line;
                $line = '';
            }
        }

        // Special case: search.php workaround must be the first rewrite rule.
        $workaround = 'RewriteRule ^([^&]*)&(.*)$ '.$mybb->settings['bburl'].'/$1?$2 [L,QSA,R=301]';
        $pos = strpos($file, "{$workaround}\n");

        if($rewrite && ($pos === false || $pos != strpos($file, "RewriteRule")))
        {
            array_unshift($lines, "# {$lang->googleseo_plugin_htaccess_search}\n# {$lang->googleseo_plugin_htaccess_search_first}\n{$workaround}\n");
        }

        $pos = strpos($file, "RewriteBase {$base}/\n");

        if($rewrite && ($pos === false || $pos > strpos($file, "RewriteRule")))
        {
            array_unshift($lines, "# {$lang->googleseo_plugin_htaccess_rewritebase}\nRewriteBase {$base}/\n");
        }

        if($rewrite && strpos($file, "RewriteEngine on\n") === false)
        {
            array_unshift($lines, "RewriteEngine on\n");
        }

        if(count($lines))
        {
            $warning[] = $lang->googleseo_plugin_warn_htaccess
                ."<pre dir=\"ltr\" style=\"background-color: #ffffff; margin: 2px; padding: 2px;\">"
                .htmlspecialchars(implode($lines, "\n"))
                ."</pre>";
        }

        // Special case: remove ErrorDocument if 404 is disabled
        if(!$settings['google_seo_404']
           && strpos($file, "google_seo_error=404"))
        {
            $warning[] = $lang->googleseo_plugin_warn_errordocument;
        }
    }

    // Check if mbstring is available:
    if($rewrite && !function_exists("mb_internal_encoding"))
    {
        $warning[] = $lang->googleseo_plugin_warn_mbstring;
    }

    // Check edits to core files.
    if(google_seo_plugin_apply() !== true)
    {
        if($settings['google_seo_url'])
        {
            $warning[] = $lang->googleseo_plugin_warn_url_apply;

            if($settings['google_seo_redirect'])
            {
                $warning[] = $lang->googleseo_plugin_warn_url_redirect;
            }
        }

        $apply = $PL->url_append('index.php',
                                 array(
                                     'module' => 'config-plugins',
                                     'google_seo' => 'apply',
                                     'my_post_key' => $mybb->post_code,
                                     ));
        $edits[] = "<a href=\"{$apply}\">{$lang->googleseo_plugin_edit_apply}</a>";
    }

    if(google_seo_plugin_revert() !== true)
    {
        if(!$settings['google_seo_url'])
        {
            $warning[] = $lang->googleseo_plugin_warn_url_revert;
        }

        $revert = $PL->url_append('index.php',
                                  array(
                                      'module' => 'config-plugins',
                                      'google_seo' => 'revert',
                                      'my_post_key' => $mybb->post_code,
                                      ));
        $edits[] = "<a href=\"{$revert}\">{$lang->googleseo_plugin_edit_revert}</a>";
    }

    // Configure URL
    $query = $db->simple_select("settinggroups", "gid", "name='google_seo'");
    $gid = $db->fetch_field($query, 'gid');

    if($gid)
    {
        $configure = $PL->url_append('index.php', array(
                                         'module' => 'config',
                                         'action' => 'change',
                                         'gid' => $gid,
                                         ));
        $configure = $lang->sprintf($lang->googleseo_plugin_configure, $configure);
    }

    else
    {
        $warning[] = $lang->googleseo_plugin_warn_setting;
    }

    // Build a list with success, warnings, errors:
    if(count($error))
    {
        $list = google_seo_plugin_list($error);

        if(count($error) > 1)
        {
            $e = $lang->sprintf($lang->googleseo_plugin_error_plural, $list);
        }

        else
        {
            $e = $lang->sprintf($lang->googleseo_plugin_error, $list);
        }

        $status .= "  <li style=\"list-style-image: url(styles/default/images/icons/error.gif)\">"
            .$e
            ." {$configure}</li>\n";
    }

    foreach($warning as $w)
    {
        $status .= "  <li style=\"list-style-image: url(styles/default/images/icons/warning.gif)\">"
            .$w
            ."</li>\n";
    }

    if(count($success))
    {
        $list = google_seo_plugin_list($success);

        if(count($success) > 1)
        {
            $s = $lang->sprintf($lang->googleseo_plugin_success_plural, $list);
        }

        else
        {
            $s = $lang->sprintf($lang->googleseo_plugin_success, $list);
        }

        $status .= "  <li style=\"list-style-image: url(styles/default/images/icons/success.gif)\">"
            .$s
            ." {$configure}</li>\n";
    }

    if(count($edits))
    {
        $list = google_seo_plugin_list($edits);

        $e = $lang->sprintf($lang->googleseo_plugin_edit, $list);

        $status .= "  <li style=\"list-style-image: url(styles/default/images/icons/custom.gif)\">"
            .$e
            ."</li>\n";
    }

    return "\n<ul>\n$status</ul>\n";
}

/* --- Plugin Helpers: --- */

/**
 * Check that dependencies are installed.
 */
function google_seo_plugin_dependency()
{
    global $mybb, $lang;

    if($mybb->version_code < 1604)
    {
        flash_message($lang->googleseo_plugin_mybb_old, "error");
        admin_redirect("index.php?module=config-plugins");
    }

    if(!file_exists(PLUGINLIBRARY))
    {
        flash_message($lang->googleseo_plugin_pl_missing, "error");
        admin_redirect("index.php?module=config-plugins");
    }

    global $PL;
    $PL or require_once(PLUGINLIBRARY);

    if($PL->version < 11)
    {
        flash_message($lang->googleseo_plugin_pl_old, "error");
        admin_redirect("index.php?module=config-plugins");
    }
}

/**
 * Make a human readable list out of a string array.
 * Used by plugin status.
 *
 * @param array List of strings
 * @return string Human readable list
 */
function google_seo_plugin_list($strarr)
{
    global $lang;

    // Don't do anything if it's empty.
    if(!count($strarr))
    {
        return;
    }

    // y
    $result = array_pop($strarr);

    // x and y
    if(count($strarr))
    {
        $result = $lang->sprintf($lang->googleseo_plugin_list_final, array_pop($strarr), $result);
    }

    // a, b, c, x and y
    while(count($strarr))
    {
        $result = $lang->sprintf($lang->googleseo_plugin_list, array_pop($strarr), $result);
    }

    return $result;
}

/* --- Plugin Installation: --- */

/**
 * Checks if the module is already installed.
 *
 * @return True if the plugin is installed, else false.
 */
function google_seo_plugin_is_installed()
{
    global $db;

    return $db->table_exists("google_seo");
}

/**
 * Installs the plugin.
 */
function google_seo_plugin_install()
{
    google_seo_plugin_dependency();

    global $db;

    // Create the Google SEO table.
    $collation = $db->build_create_table_collation();

    if(!$db->table_exists("google_seo"))
    {
        $db->write_query("CREATE TABLE ".TABLE_PREFIX."google_seo(
                              active TINYINT UNSIGNED,
                              idtype TINYINT UNSIGNED NOT NULL,
                              id INT UNSIGNED NOT NULL,
                              url VARCHAR(120) NOT NULL,
                              UNIQUE KEY (idtype, url),
                              UNIQUE KEY (active, idtype, id)
                          ) ENGINE=MyISAM{$collation};");
    }
}

/**
 * Uninstalls the plugin.
 */
function google_seo_plugin_uninstall()
{
    google_seo_plugin_dependency();

    global $mybb, $db, $lang;
    global $PL;
    $PL or require_once PLUGINLIBRARY;

    // Confirmation step.
    if(!$mybb->input['confirm'])
    {
        $link = $PL->url_append('index.php', array(
                                    'module' => 'config-plugins',
                                    'action' => 'deactivate',
                                    'uninstall' => '1',
                                    'plugin' => 'google_seo',
                                    'my_post_key' => $mybb->post_code,
                                    'confirm' => '1',
                                    ));

        flash_message("{$lang->googleseo_plugin_uninstall} <a href=\"{$link}\">{$lang->googleseo_plugin_uninstall_confirm}</a>", "error");
        admin_redirect("index.php?module=config-plugins");
    }

    // Delete cache
    $PL->cache_delete('google_seo_url');

    // Revert edits.
    if(google_seo_plugin_revert(true) !== true)
    {
        flash_message($lang->googleseo_plugin_revert_error, 'error');
        admin_redirect('index.php?module=config-plugins');
    }

    // Drop the Google SEO table.
    $db->drop_table("google_seo");

    // Remove the Google SEO setting groups.
    $PL->settings_delete("google_seo", true);
}

/* --- Plugin Activation: --- */

/**
 * Activate the plugin
 */

function google_seo_plugin_activate()
{
    google_seo_plugin_dependency();

    global $db;
    global $PL;
    $PL or require_once PLUGINLIBRARY;

    /* Bugfix: Empty URLs */
    $db->delete_query("google_seo", "url=''");

    /* Settings for Google SEO */
    $PL->settings(
        "google_seo",
        "Google SEO",
        "Enable or disable the main features of Google SEO.",
        array(
            '404' => array(
                'title' => "Enable Google SEO 404",
                'description' => "This module replaces the <i>HTTP 200 OK</i> response with <i>HTTP 404 Not Found</i> for invalid thread / forum / etc error pages and provides additional functionality for 404 error pages. You can also do custom 404 error pages by adding an ErrorPage directive to your .htaccess. Please see the <a href=\"../inc/plugins/google_seo.html\">documentation</a> for details.<br /><br />Set to YES to enable Google SEO 404. Setting this to NO also disables all other settings in the Google SEO 404 settings group.",
                ),
            'meta' => array(
                'title' => 'Enable Google SEO Meta',
                'description' => "This module generates meta tags for the current page. Please see the <a href=\"../inc/plugins/google_seo.html\">documentation</a> for details.<br /><br />Set to YES to enable Google SEO Meta. Setting this to NO also disables all other settings in the Google SEO Meta settings group."
                ),
            'redirect' => array(
                'title' => "Enable Google SEO Redirect",
                'description' => "This module redirects old and invalid URLs to their current proper names. This can be used for all sorts of redirections: redirect to the main site if your forum is available under several domain names, redirect stock MyBB URLs to Google SEO URLs (or the other way around). This prevents your users and Google from seeing the same page under several different names. Please see the <a href=\"../inc/plugins/google_seo.html\">documentation</a> for details.<br /><br />Set to YES to enable Google SEO Redirect. Setting this to NO also disables all other settings in the Google SEO Redirect settings group.",
                ),
            'sitemap' => array(
                'title' => "Enable Google SEO Sitemap",
                'description' => "This module provides <a href=\"http://sitemaps.org/\">XML Sitemap</a> for your forum. This makes it easier for Google to discover pages on your site. Please see the <a href=\"../inc/plugins/google_seo.html\">documentation</a> for details.<br /><br />Set to YES to enable Google SEO Sitemap. Setting this to NO also disables all other settings in the Google SEO Sitemap settings group.",
                ),
            'url' => array(
                'title' => "Enable Google SEO URL",
                'description' => "This module replaces the stock MyBB URLs with descriptive URLs that use words (thread subject, forum title, user name, etc) instead of random numeric IDs. Please see the <a href=\"../inc/plugins/google_seo.html\">documentation</a> for details.<br /><br />Set to YES to enable Google SEO URL. Setting this to NO also disables all other settings in the Google SEO URL settings group.",
                ),
            ),
        false // true to generate language file
        );

    /* Settings for Google SEO 404 */
    $PL->settings(
        "google_seo_404",
        "Google SEO 404",
        "(Advanced Users) 404 error page settings for the Google Search Engine Optimization plugin.",
        array(
            'widget' => array(
                'title' => "404 widget",
                'description' => "Add the Google 404 widget for 404/403 error pages.",
                'value' => 1,
                ),
            'wol_show' => array(
                'title' => "Show 404 errors in Who's Online",
                'description' => "Specify if you want to show that users are seeing the 404 error page in the Who's Online list. This is not recommended. Enabling this can cause problems such as spambots showing up as guests, or users showing up as seeing error pages if your forum e.g. tries to include an image that does not exist.",
                'optionscode' => "radio\n0=No\n1=Yes\n2=Yes, including URI",
                ),
            'status' => array(
                'title' => "Customize HTTP status codes",
                'description' => 'Specify which <a href="http://en.wikipedia.org/wiki/List_of_HTTP_status_codes" target="_blank">HTTP status code</a> should be returned for specific error pages. You can specify one status code per line followed by = and a comma-separated list of error labels, which may include wildcards. By default, the returned status code is 404 Not Found.',
                'optionscode' => "php\n<textarea name=\\\"upsetting[{\$setting['name']}]\\\" rows=\\\"5\\\" cols=\\\"80\\\" wrap=\\\"off\\\">\".htmlspecialchars(\$setting['value']).\"</textarea>",
                'value' => "404 Not Found=*\n403 Forbidden=no_permission\n503 Service Unavailable=boardclosed\n200 OK=nosearchresults,redirect_*",
                ),
            'debug' => array(
                'title' => "Debug 404 error labels",
                'description' => "Setting this to Yes will show an error label on error pages. The labels can then be used to configure custom error codes for that page.",
                ),
            ),
        false // true to generate language file
        );

    /* Settings for Google SEO Meta */
    $PL->settings(
        "google_seo_meta",
        "Google SEO Meta",
        "(Advanced Users) Meta tag settings for the Google Search Engine Optimization plugin.",
        array(
            'length' => array(
                'title' => 'Meta description',
                'description' => "Generate Meta description tags based on the contents of the current page (description of a forum, first posting of a thread, ...). Set to the maximum description length you want to allow or to 0 to disable.",
                'optionscode' => "text",
                'value' => "200",
                ),
            'canonical' => array(
                'title' => "Canonical Page",
                'description' => "Specify a canonical page. This helps avoid Google indexing the same page under several different names. Please see <a href=\"http://www.google.com/support/webmasters/bin/answer.py?hl=en&amp;answer=139394\">About rel=\"canonical\"</a> for details.",
                'value' => 1,
                ),
            'archive' => array(
                'title' => "Meta for Archive Mode",
                'description' => "Enable this option if you want tags to be added to MyBB's Lite (Archive) Mode pages. If this setting is disabled, Google SEO Meta will ignore the Archive mode altogether.",
                'value' => 1,
                ),
            'page' => array(
                'title' => "Provide page number for forum and thread titles",
                'description' => "If set, initialize a variable for forum and thread pages &gt; 1. {page} stands for Page (translatable in googleseo.lang.php), {number} for the actual page number. Edit your <i>forumdisplay</i> and <i>showthread</i> templates to include <i>{\$google_seo_page}</i> in the &lt;title&gt; tag.",
                'optionscode' => 'text',
                'value' => ' - {page} {number}',
                ),
            'nofollow' => array(
                'title' => "Nofollow links",
                'description' => "Give recently posted links the <a href=\"http://en.wikipedia.org/wiki/Nofollow\" target=\"_blank\">Nofollow</a> attribute. If your forum gets spammed, this will give you time to moderate, while still giving reputation to outgoing links in the long run. The default value is 7.0 days (one week). Set to 0 to disable, or -1 for infinite duration (not recommended).",
                'optionscode' => 'text',
                'value' => '7.0',
                ),
            'noindex_fids' => array(
                'title' => "Noindex forums",
                'description' => "If you want to prevent one or more forums and its threads from being indexed by search engines altogether, enter a comma separated list of forum IDs here. This is not recommended but might be useful for trashcan or duplicate content forums.",
                'optionscode' => 'text',
                'value' => '',
                ),
            ),
        false // true to generate language file
        );

    /* Settings for Google SEO Redirect */
    $PL->settings(
        "google_seo_redirect",
        "Google SEO Redirect",
        "(Advanced Users) Redirection settings for the Google Search Engine Optimization plugin.",
        array(
            'permission' => array(
                'title' => "Permission Checks",
                'description' => "Should Redirect let permission checks run first? Enabling this option will prevent Redirect from redirecting URLs for items that the user is not allowed to access anyway. This is probably only necessary if you're also using SEO URLs and you're concerned about users getting redirected to the SEO URL of a forum / thread they're not allowed to read, which would give away the subject in the SEO URL.",
                ),
            'posts' => array(
                'title' => "Redirect Post Links",
                'description' => "MyBB allows linking to specific posts by specifying the post ID (pid) in the URL. Redirect can trust the thread ID (tid) it tid was given and query it if it was not (default MyBB behaviour), or it can verify the tid (by making a query) in order to redirect links to posts that were moved to another thread, or it can ignore these links completely (avoiding the tid query altogether).",
                'optionscode' => "radio\ndefault=Default (query on demand)\nverify=Verify (always query)\nignore=Ignore (never query)",
                'value' => 'default',
                ),
            'litespeed' => array(
                'title' => "LiteSpeed Bug workaround",
                'description' => "If your server is running LiteSpeed &lt;= 4.0.10 instead of Apache, and you see redirection loops on member profile / send mail or calendar event / edit event pages, you are suffering from a bug in LiteSpeed's mod_rewrite replacement. Set to YES to work around this bug - Google SEO Redirect will then leave the problematic pages alone. Apache / Nginx / lighttpd users can leave this at NO.",
                ),
            'debug' => array(
                'title' => "Debug Redirect",
                'description' => "If you experience infinite redirection loops due to Google SEO Redirect, please enable this option to obtain more information about what is going wrong with your redirect and then report a bug to the plugin author. The debug information is ugly and therefore shown only to board admins.",
                ),
            ),
        false // true to generate language file
        );

    /* Settings for Google SEO Sitemap */
    $PL->settings(
        "google_seo_sitemap",
        "Google SEO Sitemap",
        "(Advanced Users) Sitemap settings for the Google Search Engine Optimization plugin.",
        array(
            'url' => array(
                'title' => "Sitemap URL scheme",
                'description' => "This is the URL scheme used for the XML Sitemap pages. By default, this is <i>sitemap-{url}.xml</i> and your sitemap will be called <i>sitemap-index.xml</i>. Please note that if you change this, you will also need to add a new rewrite rule to your .htaccess. If your host does not support mod_rewrite, leave this empty. Your sitemap will then be called <i>misc.php?google_seo_sitemap=index</i>.",
                'optionscode' => "text",
                'value' => "sitemap-{url}.xml",
                ),
            'forums' => array(
                'title' => "Forums",
                'description' => "Include Forums in the XML Sitemap.",
                'optionscode' => "radio\n0=No\n1=Yes\n2=Yes, including forum pages",
                'value' => 1,
                ),
            'threads' => array(
                'title' => "Threads",
                'description' => "Include Threads in the XML Sitemap.",
                'optionscode' => "radio\n0=No\n1=Yes\n2=Yes, including thread pages",
                'value' => 1,
                ),
            'users' => array(
                'title' => "Users",
                'description' => "Include Users in the XML Sitemap.",
                'value' => 1,
                ),
            'announcements' => array(
                'title' => "Announcements",
                'description' => "Include Announcements in the XML Sitemap.",
                'value' => 1,
                ),
            'calendars' => array(
                'title' => "Calendars",
                'description' => "Include Calendars in the XML Sitemap.",
                'value' => 1,
                ),
            'events' => array(
                'title' => "Events",
                'description' => "Include Events in the XML Sitemap.",
                'value' => 1,
                ),
            'additional' => array(
                'title' => "Additional Pages",
                'description' => "List of additional URLs relative to your site that should be included in the XML Sitemap. If you have any custom pages you can include them here, one page per line. Entries must be relative to your site, i.e. they must not contain http://, and must not start with .. or /.",
                'optionscode' => "textarea",
                'value' => "index.php\nportal.php",
                ),
            'pagination' => array(
                'title' => "Sitemap Pagination",
                'description' => "Set the maximum number of items that may appear in a single XML Sitemap before it is split (not counting optional forum/thread pages). Setting it too low will result in too many sitemaps, setting it too high may cause server load every time the sitemap is generated. If unsure, leave at 1000.",
                'optionscode' => "text",
                'value' => "1000",
                ),
            ),
        false // true to generate language file
        );

    /* Settings for Google SEO URLs */
    $PL->settings(
        "google_seo_url",
        "Google SEO URL",
        "(Advanced Users) URL settings for the Google Search Engine Optimization plugin.",
        array(
            'query_limit' => array(
                'title' => 'Query Limit',
                'description' => "Google SEO uses the database to store, and subsequently query, unique SEO URLs for every forum, thread, etc. While these queries are fast and usually low in number, in some cases the total number of queries per request may exceed sane values. Possible causes for this are new installs in large forums when lots of SEO URLs have to be created for the first time, as well as plugins that add lots of unexpected links on a page. Limiting the total number of queries per request helps to avoid load spikes. Stock URLs will appear for the links that couldn't be queried due to this limit.<p>Set the total number of queries URL is allowed to use in a single request. Default is 50. Set to 0 for no limit.</p>",
                'optionscode' => "text",
                'value' => '50',
                ),
            'mode' => array(
                'title' => "Evaluation Mode",
                'description' => "In Full Mode (which is the default), Google SEO will query and return SEO URLs directly at the time they are requested. This is the most reliable method but it will probably use more than just one database query, depending on how well Google SEO can predict which links will show up on a page. In Lazy Mode, Google SEO will first collect all links created on a page, and only at the very end obtain all SEO URLs in a single query and replace their stock URL counterparts in the output. This reduces queries to a minimum, at the cost of PHP processing time and reliability.",
                'optionscode' => "radio\n0=Full Mode (Default)\nlazy=Lazy Mode",
                ),
            'cache' => array(
                'title' => "Use MyBB's Cache system for SEO URLs",
                'description' => "If set, the cache will be populated with the URLs used on the specified pages (by default, index.php and portal.php). Under ideal conditions this will reduce the need for additional queries as the required URLs will already be available from the cache. If the setting starts with a number, the cache will be flushed every x minutes (otherwise it defaults to 15 minutes). Set to empty to disable.<p>Warning: Do not include pages which contain unlimited/all URLs like forumdisplay/showthread. They would make the cache grow too large to be useful.</p>",
                'optionscode' => 'text',
                'value' => '15,index.php,portal.php',
                ),
            'punctuation' => array(
                'title' => "Punctuation characters",
                'description' => "Punctuation and other special characters are filtered from the URL string and replaced by the separator. By default, this string contains all special ASCII characters including space. If you are running an international forum with non-ASCII script, you might want to add unwanted punctuation characters of those scripts here.",
                'optionscode' => "text",
                'value' => "!\"#$%&'( )*+,-./:;<=>?@[\\]^_`{|}~",
                ),
            'separator' => array(
                'title' => "URL separator",
                'description' => "Enter the separator that should be used to separate words in the URLs. By default this is - which is a good choice as it is easy to type in most keyboard layouts (single keypress without shift/alt modifier). If you want some other character or string as a separator, you can enter it here. Please note that special characters like :&amp;?@/ or space could render your URLs unuseable or hard to work with.",
                'optionscode' => "text",
                'value' => "-",
                ),
            'uniquifier' => array(
                'title' => "URL uniquifier",
                'description' => "In case of URL collisions (for example two threads with the same title), the uniquifier is applied to the URL of the newer thread. To guarantee uniqueness, the uniquifier must incorporate the ID and use punctuation other than a single separator. Please see the <a href=\"../inc/plugins/google_seo.html\">documentation</a> for examples of good and bad uniquifiers.",
                'optionscode' => "text",
                'value' => '{url}{separator}{separator}{id}',
                ),
            'uniquifier_force' => array(
                'title' => "URL uniquifier enforcer",
                'description' => "This option is NOT recommended. If you set this to yes, Google SEO will be forced to use the uniquifier for all URLs without exception, even if it's not necessary. Use this only if you absolutely want every URL to contain the ID. If you enable this, a single separator will be sufficient for the uniquifier.",
                ),
            'translation' => array(
                'title' => "Character Translation",
                'description' => "If you want to replace some characters (German umlaut example: Übergrößenträger -&gt; Uebergroessentraeger) or words in your URLs, please add your translations here. You can specify one character per line followed by = and the replacement character. Please see the <a href=\"../inc/plugins/google_seo.html\">documentation</a> for details.",
                'optionscode' => "php\n<textarea name=\\\"upsetting[{\$setting['name']}]\\\" rows=\\\"5\\\" cols=\\\"80\\\" wrap=\\\"off\\\">\".htmlspecialchars(\$setting['value']).\"</textarea>",
                'value' => '',
                ),
            'lowercase' => array(
                'title' => "lowercase words",
                'description' => "If you prefer lower case URLs, you can set this to YES. This will not affect the way URLs are stored in the database so you can go back to the original case letters any time. Please note that if you set this to YES, you will also have to make sure that your forum URL, as well as scheme and uniqufier are all lowercase too for the URL to be completely in lower case.",
                ),
            'length_soft' => array(
                'title' => "URL length soft limit",
                'description' => "URLs can be shortened after a soft limit by truncating it after a word (punctuation separator). Set to 0 to disable.",
                'optionscode' => "text",
                'value' => '0',
                ),
            'length_hard' => array(
                'title' => "URL length hard limit",
                'description' => "URLs can be shortened after a hard limit by truncating it regardless of word separators. Set to 0 to disable.",
                'optionscode' => "text",
                'value' => '0',
                ),
            'posts' => array(
                'title' => "Handle Post Links",
                'description' => "MyBB allows linking to specific posts by specifying the post ID (pid) in the URL. URL can trust the thread ID (tid) it tid was given and query it if it was not (default), or it can verify the tid (by making a query) in order to show correct links to posts at all times, or it can ignore these links completely (avoiding the tid query altogether by showing a stock post URL).",
                'optionscode' => "radio\ndefault=Default (query on demand)\nverify=Verify (always query)\nignore=Ignore (never query)",
                'value' => 'default',
                ),
            'multipage' => array(
                'title' => 'Handle multipage links',
                'description' => "MyBB uses a special multipage() function to produce pagination links for all sorts of things, including forum and thread pages. Set this to Yes to allow Google SEO to attempt replacing these with their SEO URL counterparts, otherwise no.",
                'value' => 1,
                ),
            'forums' => array(
                'title' => "Forum URL scheme",
                'description' => "Enter the Forum URL scheme. By default this is <i>Forum-{url}</i>. Please note that if you change this, you will also need to add a new rewrite rule in your .htaccess file. Leave empty to disable Google SEO URLs for Forums.",
                'optionscode' => "text",
                'value' => 'Forum-{url}',
                ),
            'threads' => array(
                'title' => "Thread URL scheme",
                'description' => "Enter the Thread URL scheme. By default this is <i>Thread-{url}</i>. Please note that if you change this, you will also need to add a new rewrite rule in your .htaccess file. Leave empty to disable Google SEO URLs for Threads.",
                'optionscode' => "text",
                'value' => 'Thread-{url}',
                ),
            'threadprefix' => array(
                'title' => 'Thread Prefixes',
                'description' => "Include thread prefixes in thread URLs?",
                'optionscode' => "text",
                'value' => "{prefix}{separator}{url}",
                ),
            'announcements' => array(
                'title' => "Announcement URL scheme",
                'description' => "Enter the Announcement URL scheme. By default this is <i>Announcement-{url}</i>. Please note that if you change this, you will also need to add a new rewrite rule in your .htaccess file. Leave empty to disable Google SEO URLs for Announcements.",
                'optionscode' => "text",
                'value' => 'Announcement-{url}',
                ),
            'users' => array(
                'title' => "User URL scheme",
                'description' => "Enter the User URL scheme. By default this is <i>User-{url}</i>. Please note that if you change this, you will also need to add a new rewrite rule in your .htaccess file. Leave empty to disable Google SEO URLs for Users.",
                'optionscode' => "text",
                'value' => 'User-{url}',
                ),
            'calendars' => array(
                'title' => "Calendar URL scheme",
                'description' => "Enter the Calendar URL scheme. By default this is <i>Calendar-{url}</i>. Please note that if you change this, you will also need to add a new rewrite rule in your .htaccess file. Leave empty to disable Google SEO URLs for Calendars.",
                'optionscode' => "text",
                'value' => 'Calendar-{url}',
                ),
            'events' => array(
                'title' => "Event URL scheme",
                'description' => "Enter the Event URL scheme. By default this is <i>Event-{url}</i>. Please note that if you change this, you will also need to add a new rewrite rule in your .htaccess file. Leave empty to disable Google SEO URLs for Events.",
                'optionscode' => "text",
                'value' => 'Event-{url}',
                ),
            'parent_forum' => array(
                'title' => "Include parent forum in forum URLs?",
                'description' => "Set the scheme that should be used to include the parent forum in the forum URL. {parent} is the Google SEO URL of the parent forum, {url} the URL of the forum itself. Enabling this option is not recommended and costs an extra query in the URL creation / verification step for forum URLs.",
                'optionscode' => "text",
                'value' => '',
                ),
            'parent_thread' => array(
                'title' => "Include parent forum in thread URLs?",
                'description' => "Set the scheme that should be used to include the parent forum in the thread URL. {parent} is the Google SEO URL of the parent forum, {url} the URL of the thread itself. Enabling this option is not recommended and costs an extra query in the URL creation / verification step for thread URLs.",
                'optionscode' => "text",
                'value' => '',
                ),
            'parent_announcement' => array(
                'title' => "Include parent forum in announcement URLs?",
                'description' => "Set the scheme that should be used to include parent forum in the announcement URL. {parent} is the Google SEO URL of the parent forum, {url} the URL of the announcement itself. Enabling this option is not recommended and costs an extra query in the URL creation / verification step for announcement URLs.",
                'optionscode' => "text",
                'value' => '',
                ),
            'parent_event' => array(
                'title' => "Include parent calendar in event URLs?",
                'description' => "Set the scheme that should be used to include parent calendar in the event URL. {parent} is the Google SEO URL of the parent calendar, {url} the URL of the event itself. Enabling this option is not recommended and costs an extra query in the URL creation / verification step for event URLs.",
                'optionscode' => "text",
                'value' => '',
                ),
            ),
        false // true to generate language file
        );
}

/**
 * Deactivates the plugin.
 */
// This function runs when the plugin is deactivated.
function google_seo_plugin_deactivate()
{
    // Keep settings and database in case Google SEO gets activated again.
    // Use uninstall if you want to get rid of Google SEO completely.
}

/* --- Core file edits: --- */

/**
 * Check for edit action
 *
 */
function google_seo_plugin_edit()
{
    global $mybb, $lang;

    // Check for core file edit action
    if($mybb->input['my_post_key'] == $mybb->post_code)
    {
        if($mybb->input['google_seo'] == 'apply')
        {
            if(google_seo_plugin_apply(true) === true)
            {
                flash_message($lang->googleseo_plugin_apply_success, 'success');
                admin_redirect('index.php?module=config-plugins');
            }

            else
            {
                flash_message($lang->googleseo_plugin_apply_error, 'error');
                admin_redirect('index.php?module=config-plugins');
            }
        }

        if($mybb->input['google_seo'] == 'revert')
        {
            if(google_seo_plugin_revert(true) === true)
            {
                flash_message($lang->googleseo_plugin_revert_success, 'success');
                admin_redirect('index.php?module=config-plugins');
            }

            else
            {
                flash_message($lang->googleseo_plugin_revert_error, 'error');
                admin_redirect('index.php?module=config-plugins');
            }
        }
    }
}

/**
 * Apply changes to MyBB core files using PluginLibrary::edit_core().
 *
 */
function google_seo_plugin_apply($apply=false)
{
    global $settings;
    global $PL;
    $PL or require_once PLUGINLIBRARY;

    $edits = array();

    if($settings['google_seo_url_multipage']
       && ($settings['google_seo_url_forums']
           || $settings['google_seo_url_threads']))
    {
        // multipage
        $edits[] = array(
            'search' => array('function multipage(', '{'),
            'after' => array(
                'if(function_exists("google_seo_url_multipage"))',
                '{',
                '    $newurl = google_seo_url_multipage($url);',
                '',
                '    if($newurl)',
                '    {',
                '        $url = $newurl;',
                '    }',
                '}',
                ),
            );
    }

    if($settings['google_seo_url_users'])
    {
        // users
        $edits[] = array(
            'search' => array('function get_profile_link(', '{'),
            'after' => array(
                'if(function_exists("google_seo_url_profile"))',
                '{',
                '    $link = google_seo_url_profile($uid);',
                '',
                '    if($link)',
                '    {',
                '        return $link;',
                '    }',
                '}',
                ),
            );
    }

    if($settings['google_seo_url_announcements'])
    {
        // announcements
        $edits[] = array(
            'search' => array('function get_announcement_link(', '{'),
            'after' => array(
                'if(function_exists("google_seo_url_announcement"))',
                '{',
                '    $link = google_seo_url_announcement($aid);',
                '',
                '    if($link)',
                '    {',
                '        return $link;',
                '    }',
                '}',
                ),
            );
    }

    if($settings['google_seo_url_forums'])
    {
        // forums
        $edits[] = array(
            'search' => array('function get_forum_link(', '{'),
            'after' => array(
                'if(function_exists("google_seo_url_forum"))',
                '{',
                '    $link = google_seo_url_forum($fid, $page);',
                '',
                '    if($link)',
                '    {',
                '        return $link;',
                '    }',
                '}',
                ),
            );
    }

    if($settings['google_seo_url_threads'])
    {
        // threads
        $edits[] = array(
            'search' => array('function get_thread_link(', '{'),
            'after' => array(
                'if(function_exists("google_seo_url_thread"))',
                '{',
                '    $link = google_seo_url_thread($tid, $page, $action);',
                '',
                '    if($link)',
                '    {',
                '        return $link;',
                '    }',
                '}',
                ),
            );
    }

    if($settings['google_seo_url_threads']
       && $settings['google_seo_url_posts'])
    {
        // posts
        $edits[] = array(
            'search' => array('function get_post_link(', '{'),
            'after' => array(
                'if(function_exists("google_seo_url_post"))',
                '{',
                '    $link = google_seo_url_post($pid, $tid);',
                '',
                '    if($link)',
                '    {',
                '        return $link;',
                '    }',
                '}',
                ),
            );
    }

    if($settings['google_seo_url_events'])
    {
        // events
        $edits[] = array(
            'search' => array('function get_event_link(', '{'),
            'after' => array(
                'if(function_exists("google_seo_url_event"))',
                '{',
                '    $link = google_seo_url_event($eid);',
                '',
                '    if($link)',
                '    {',
                '        return $link;',
                '    }',
                '}',
                ),
            );
    }

    if($settings['google_seo_url_calendars'])
    {
        // calendars
        $edits[] = array(
            'search' => array('function get_calendar_link(', '{'),
            'after' => array(
                'if(function_exists("google_seo_url_calendar"))',
                '{',
                '    $link = google_seo_url_calendar($calendar, $year, $month, $day);',
                '',
                '    if($link)',
                '    {',
                '        return $link;',
                '    }',
                '}',
                ),
            );
    }

    if($settings['google_seo_url_weeks'])
    {
        // calendards (week)
        $edits[] = array(
            'search' => array('function get_calendar_week_link(', '{'),
            'after' => array(
                'if(function_exists("google_seo_url_calendar_week"))',
                '{',
                '    $link = google_seo_url_calendar_week($calendar, $week);',
                '',
                '    if($link)',
                '    {',
                '        return $link;',
                '    }',
                '}',
                ),
            );
    }

    if(!$settings['google_seo_url'])
    {
        // ... not. ;)
        $edits = array();
    }

    // Location workaround, needed by 404 as well.
    if(count($edits) || $settings['google_seo_404_wol_show'])
    {
        $edits[] = array(
            'search' => array('function get_current_location(', '{'),
            'after' => array(
                'global $mybb, $google_seo_location;',
                '',
                'if($google_seo_location && !$fields)',
                '{',
                '    return $google_seo_location;',
                '}',
                ),
            );
    }

    return $PL->edit_core('google_seo', 'inc/functions.php', $edits, $apply);
}

/**
 * Revert changes to MyBB core files using PluginLibrary::edit_core().
 *
 */
function google_seo_plugin_revert($apply=false)
{
    global $PL;
    $PL or require_once PLUGINLIBRARY;

    return $PL->edit_core('google_seo', 'inc/functions.php', array(), $apply);
}

/* --- End of file. --- */
?>
