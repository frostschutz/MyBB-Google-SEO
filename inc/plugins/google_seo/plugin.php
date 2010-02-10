<?php
/**
 * This file is part of Google SEO plugin for MyBB.
 * Copyright (C) 2008, 2009 Andreas Klauer <Andreas.Klauer@metamorpher.de>
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

/* --- Plugin Info: --- */

/**
 * Basic information about the plugin.
 *
 * @return Plugin info array.
 */
function google_seo_plugin_info()
{
    global $settings, $plugins_cache;

    $info = array(
        "name"          => "Google SEO",
        "description"   => "Google Search Engine Optimization as described in the official <a href=\"http://www.google.com/webmasters/docs/search-engine-optimization-starter-guide.pdf\">Google's SEO starter guide</a>. Please see the <a href=\"{$settings['bburl']}/inc/plugins/google_seo.txt\">documentation</a> for details.",
        "author"        => "Andreas Klauer",
        "authorsite"    => "mailto:Andreas.Klauer@metamorpher.de",
        "version"       => "1.0.4",
        "guid"          => "8d12371391e1c95392dd567617e40f7f",
        "compatibility" => "14*",
    );


    // Provide some additional status information, if the plugin is enabled.
    if(google_seo_plugin_is_installed() &&
       $plugins_cache['active']['google_seo'])
    {
        $info['description'] .= @google_seo_plugin_status();
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
    global $mybb, $settings, $db;

    $success = array();
    $warning = array();
    $error = array();
    $htaccess = array();
    $lines = array();
    $headerinclude = array();

    // UTF-8 is required:
    if($mybb->config['database']['encoding'] != 'utf8')
    {
        $warning[] = "Your database encoding is '".$mybb->config['database']['encoding']."', should be 'utf8'. Please update your MyBB to use UTF-8 everywhere.";
    }

    // Google SEO 404:
    if($settings['google_seo_404'])
    {
        $success[] = '404 is enabled.';
    }

    else
    {
        $error[] = '404 is disabled.';
    }

    // Google SEO Meta:
    if($settings['google_seo_meta'])
    {
        $success[] = 'Meta is enabled.';
        $headerinclude['{$google_seo_meta}'] = array();
    }

    else
    {
        $error[] = 'Meta is disabled.';
    }

    // Google SEO Redirect:
    if($settings['google_seo_redirect'])
    {
        $success[] = 'Redirect is enabled.';

        if(!$settings['google_seo_url'])
        {
            $warning[] = "Redirect enabled, but URL disabled. This is fine for redirecting stock MyBB URLs (showthread.php?tid=x) to MyBB search engine friendly URLs (thread-x.html) or vice versa. If you want to redirect stock MyBB URLs to Google SEO URLs or vice versa, please enable URL as well.";
        }
    }

    else
    {
        $error[] = 'Redirect is disabled.';
    }

    // Google SEO Sitemap:
    if($settings['google_seo_sitemap'])
    {
        $success[] = 'Sitemap is enabled.';
        $htaccess[] = array($settings['google_seo_sitemap_url'],
                            'misc.php?google_seo_sitemap=$1 [L,QSA,NC]',
                            'Google SEO Sitemap');
    }

    else
    {
        $error[] = 'Sitemap is disabled.';

    }

    // Google SEO URL:
    if($settings['google_seo_url'])
    {
        $success[] = 'URL is enabled.';

        $file = @file_get_contents(MYBB_ROOT."inc/functions.php");

        if(strstr($file, "google_seo_url") === false)
        {
            $warning[] = "Modifications to inc/functions.php are required for URL support. Please see the <a href=\"../inc/plugins/google_seo.txt\">documentation</a> for details.";
        }

        if($settings['google_seo_url_forums'])
        {
            $htaccess[] = array($settings['google_seo_url_forums'],
                                'forumdisplay.php?google_seo_forum=$1 [L,QSA,NC]',
                                'Google SEO URL Forums');
        }

        if($settings['google_seo_url_threads'])
        {
            $htaccess[] = array($settings['google_seo_url_threads'],
                                'showthread.php?google_seo_thread=$1 [L,QSA,NC]',
                                'Google SEO URL Threads');
        }

        if($settings['google_seo_url_announcements'])
        {
            $htaccess[] = array($settings['google_seo_url_announcements'],
                                'announcements.php?google_seo_announcement=$1 [L,QSA,NC]',
                                'Google SEO URL Announcements');
        }

        if($settings['google_seo_url_users'])
        {
            $htaccess[] = array($settings['google_seo_url_users'],
                                'member.php?action=profile&google_seo_user=$1 [L,QSA,NC]',
                                'Google SEO URL Users');
        }

        if($settings['google_seo_url_calendars'])
        {
            $htaccess[] = array($settings['google_seo_url_calendars'],
                                'calendar.php?google_seo_calendar=$1 [L,QSA,NC]',
                                'Google SEO URL Calendars');
        }

        if($settings['google_seo_url_events'])
        {
            $htaccess[] = array($settings['google_seo_url_events'],
                                'calendar.php?action=event&google_seo_event=$1 [L,QSA,NC]',
                                'Google SEO URL Events');
        }
    }

    else
    {
        $error[] = 'URL is disabled.';
    }

    // Check headerinclude.
    $query = $db->query("SELECT a.title,
                                b.template
                         FROM ".TABLE_PREFIX."templatesets a
                         LEFT JOIN
                             (SELECT * FROM ".TABLE_PREFIX."templates
                              WHERE title='headerinclude') b
                              ON a.sid = b.sid");

    while($row = $db->fetch_array($query))
    {
        foreach(array_keys($headerinclude) as $k)
        {
            if(strstr($row['template'], $k) === false)
            {
                $headerinclude[$k][] = $row['title'];
            }
        }
    }

    foreach(array_keys($headerinclude) as $k)
    {
        if(count($headerinclude[$k]))
        {
            $warning[] = "Add ".htmlentities($k)." to headerinclude template"
                ." for these template sets: ".join($headerinclude[$k], ", ");
        }
    }

    // Check htaccess.
    if($settings['google_seo_404'])
    {
        $url = $settings['bburl'];
        $url = preg_replace('#^[^/]*://[^/]*#', '', $url);
        $htaccess[] = array("ErrorDocument 404 $url/misc.php?google_seo_error=404",
                            0,
                            'Google SEO 404');
    }

    if(count($htaccess))
    {
        $file = @file_get_contents(MYBB_ROOT.".htaccess");

        if($file)
        {
            $file = preg_replace('/^[\s\t]*#.*$/m', '', $file);
        }

        foreach($htaccess as $v)
        {
            if($v[1])
            {
                $rewrite = 1;
                $rule = preg_quote($v[0]);
                $rule = preg_replace('/\\\\{\\\\\\$url\\\\}/', '{$url}', $rule);
                $url = "([^./]+)";
                eval("\$rule = \"^{$rule}$\";");


                $rule = "RewriteRule $rule {$v[1]}";

                if(strstr($file, $rule) === false)
                {
                    $line = "# {$v[2]}:\n{$rule}\n";
                }
            }

            else
            {
                if(strstr($file, $v[0]) === false)
                {
                    $line = "# {$v[2]}:\n{$v[0]}\n";
                }
            }

            if($line)
            {
                $lines[] = htmlentities($line);
                $line = '';
            }
        }

        // Special case: search.php workaround must be the first rewrite rule.
        $workaround = 'RewriteRule ^([^&]*)&(.*)$ '.$mybb->settings['bburl'].'/$1?$2 [L,QSA,R=301]';
        $pos = strstr($file, $workaround);

        if($rewrite && ($pos === false || $pos != strstr($file, "RewriteRule")))
        {
            array_unshift($lines,
                          "# As first rewrite rule, Google SEO workaround for search.php highlights:\n"
                          .$workaround."\n");
        }

        if($rewrite && strstr($file, "RewriteEngine on") === false)
        {
            array_unshift($lines, "RewriteEngine on\n");
        }

        // Check if mbstring is available:
        if($rewrite && !function_exists("mb_internal_encoding"))
        {
            $warning[] = "Your host does not seem to support mbstring. This may cause problems with UTF-8 in URLs.";
        }

        if(count($lines))
        {
            $warning[] = "Add to .htaccess:"
                ."<pre style=\"background-color: #ffffff; margin: 2px; padding: 2px;\">"
                .implode($lines, "\n")
                ."</pre>";
        }
    }

    // Build a list with success, warnings, errors:
    foreach($error as $e)
    {
        $status .= "  <li style=\"list-style-image: url(styles/default/images/icons/error.gif)\">"
            .$e
            ."</li>\n";
    }

    foreach($warning as $w)
    {
        $status .= "  <li style=\"list-style-image: url(styles/default/images/icons/warning.gif)\">"
            .$w
            ."</li>\n";
    }

    foreach($success as $s)
    {
        $status .= "  <li style=\"list-style-image: url(styles/default/images/icons/success.gif)\">"
            .$s
            ."</li>\n";
    }

    return "\n<ul>\n$status</ul>\n";
}

/* --- Plugin Helpers: --- */

/**
 * Take care of inserting / updating settings.
 * Names and settings must be unique (i.e. use the google_seo_ prefix).
 *
 * @param string Internal group name.
 * @param string Group title that will be shown to the admin.
 * @param string Group description that will show up in the group overview.
 * @param array The list of settings to be added to that group.
 */
function google_seo_plugin_settings($name, $title, $description, $list)
{
    global $db;

    $query = $db->query("SELECT MAX(disporder) as disporder
                         FROM ".TABLE_PREFIX."settinggroups");
    $row = $db->fetch_array($query);

    $group = array('name' => "$name",
                   'title' => $title,
                   'description' => $description,
                   'disporder' => $row['disporder']+1);

    // Create settings group if it does not exist.
    $query = $db->query("SELECT gid
                         FROM ".TABLE_PREFIX."settinggroups
                         WHERE name='$name'");

    if($row = $db->fetch_array($query))
    {
        // It exists, get the gid.
        $gid = $row['gid'];

        // Update title and description.
        $db->update_query("settinggroups",
                          $group,
                          "gid='$gid'");
    }

    else
    {
        // It does not exist, create it and get the gid.
        $db->insert_query("settinggroups",
                          $group);

        $gid = $db->insert_id();
    }

    // Deprecate all the old entries.
    $db->update_query("settings",
                      array("description" => "DELETEMARKER"),
                      "gid='$gid'");

    // Create and/or update settings.
    foreach($list as $key => $value)
    {
        // Set default values for value:
        foreach($value as $a => $b)
        {
            $value[$a] = $db->escape_string($b);
        }

        $disporder += 1;

        $value = array_merge(
            array('optionscode' => 'yesno',
                  'value' => '0',
                  'disporder' => $disporder),
            $value);

        $value['name'] = "$key";
        $value['gid'] = $gid;

        $query = $db->query("SELECT sid FROM ".TABLE_PREFIX."settings
                             WHERE gid='$gid'
                             AND name='{$value['name']}'");

        if($row = $db->fetch_array($query))
        {
            // It exists, update it, but keep value intact.
            unset($value['value']);
            $db->update_query("settings",
                              $value,
                              "gid='$gid' AND name='{$value['name']}'");
        }

        else
        {
            // It doesn't exist, create it.
            $db->insert_query("settings", $value);
        }
    }

    // Delete deprecated entries.
    $db->delete_query("settings",
                      "gid='$gid' AND description='DELETEMARKER'");

    // Rebuild the settings file.
    rebuild_settings();
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
    global $db;

    // Create the Google SEO table.
    $collation = $db->build_create_table_collation();

    if(!$db->table_exists("google_seo_id"))
    {
        $db->write_query("CREATE TABLE ".TABLE_PREFIX."google_seo(
                              active TINYINT UNSIGNED,
                              idtype TINYINT UNSIGNED NOT NULL,
                              id INT UNSIGNED NOT NULL,
                              url VARCHAR(120) NOT NULL,
                              UNIQUE KEY (idtype, url),
                              UNIQUE KEY (active, idtype, id)
                          ) TYPE=MyISAM{$collation};");
    }
}

/**
 * Uninstalls the plugin.
 */
function google_seo_plugin_uninstall()
{
    global $db;

    // Drop the Google SEO table.
    $db->drop_table("google_seo");

    // Remove the Google SEO setting groups.
    $query = $db->query("SELECT name,gid FROM ".TABLE_PREFIX."settinggroups WHERE name LIKE 'google_seo_%'");

    while($gid = $db->fetch_field($query, 'gid'))
    {
        $db->delete_query("settinggroups", "gid='$gid'");
        $db->delete_query("settings", "gid='$gid'");
    }

    rebuild_settings();
}

/* --- Plugin Activation: --- */

/**
 * Activate the plugin
 */

function google_seo_plugin_activate()
{
    global $db;

    /* Bugfix: Empty URLs */
    $db->delete_query("google_seo", "url=''");

    /* Settings for Google SEO 404 */
    google_seo_plugin_settings(
        "google_seo_404",
        "Google SEO 404",
        "404 error page settings for the Google Search Engine Optimization plugin.",
        array(
            'google_seo_404' => array(
                'title' => "Google SEO 404",
                'description' => "This module replaces the <i>HTTP 200 OK</i> response with <i>HTTP 404 Not Found</i> for invalid thread / forum / etc error pages and provides additional functionality for 404 error pages. You can also do custom 404 error pages by adding an ErrorPage directive to your .htaccess. Please see the <a href=\"../inc/plugins/google_seo.txt\">documentation</a> for details.<br /><br />Set to YES to enable Google SEO 404. Setting this to NO also disables all other settings in this group.",
                ),
            'google_seo_404_widget' => array(
                'title' => "404 widget",
                'description' => "Add the Google 404 widget for invalid thread / forum / etc error pages.",
                'value' => 1,
                ),
            'google_seo_404_lang' => array(
                'title' => "404 widget language",
                'description' => 'Set the language of the Google 404 widget. See <a href="http://www.google.com/support/webmasters/bin/answer.py?answer=93644">Enhance your custom 404 page</a> for details.',
                'optionscode' => "text",
                'value' => "en",
                ),
            'google_seo_404_wol' => array(
                'title' => "404 Who's Online",
                'description' => "Define here how you want users on the 404 page to show up in the Who Is Online user list. Use [location]link[/location] to make a link to the location of the user.",
                'optionscode' => "text",
                'value' => 'Seeing an [location]Error Page[/location]'
                ),
            'google_seo_404_wol_hide' => array(
                'title' => "Hide guests in 404 Who's Online",
                'description' => "Say yes if you want to hide guests who are currently seeing a 404 page from the detailed Who's Online list. This is useful if you have a lot of guests (usually spambots) hitting 404 error pages on your site.",
                ),
            )
        );

    /* Settings for Google SEO Meta */
    google_seo_plugin_settings(
        "google_seo_meta",
        "Google SEO Meta",
        "Meta tag settings for the Google Search Engine Optimization plugin.",
        array(
            'google_seo_meta' => array(
                'title' => 'Google SEO Meta',
                'description' => "This module generates an unique description meta tag based on the content of the current page. For forums, it uses the description of the forum, for threads it uses the contents of the first posting. For this to work, you have to add {\$google_seo_meta} to your <i>headerinclude</i> template. Please see the <a href=\"../inc/plugins/google_seo.txt\">documentation</a> for details.<br /><br />Set to YES to enable Google SEO Meta. Setting this to NO also disables all other settings in this group."
                ),
            'google_seo_meta_length' => array(
                'title' => 'Meta tag length',
                'description' => "Maximum length of a meta description. This is to prevent very long posts to be copied into the description tag as a whole. Keep it as long as necessary and as short as possible.",
                'optionscode' => "text",
                'value' => "150",
                ),
            )
        );

    /* Settings for Google SEO Redirect */
    google_seo_plugin_settings(
        "google_seo_redirect",
        "Google SEO Redirect",
        "Redirection settings for the Google Search Engine Optimization plugin.",
        array(
            'google_seo_redirect' => array(
                'title' => "Google SEO Redirect",
                'description' => "This module redirects old and invalid URLs to their current proper names. This can be used for all sorts of redirections: redirect to the main site if your forum is available under several domain names, redirect stock MyBB URLs to Google SEO URLs (or the other way around). This prevents your users and Google from seeing the same page under several different names. Please see the <a href=\"../inc/plugins/google_seo.txt\">documentation</a> for details.<br /><br />Set to YES to enable Google SEO Redirect. Setting this to NO also disables all other settings in this group.",
                ),
            'google_seo_redirect_debug' => array(
                'title' => "Google SEO Redirect Debug",
                'description' => "If you experience infinite redirection loops due to Google SEO Redirect, please enable this option to obtain more information about what is going wrong with your redirect and then report a bug to the plugin author. The debug information is ugly and therefore shown only to board admins.",
                ),
            )
        );

    /* Settings for Google SEO Sitemap */
    google_seo_plugin_settings(
        "google_seo_sitemap",
        "Google SEO Sitemap",
        "Sitemap settings for the Google Search Engine Optimization plugin.",
        array(
            'google_seo_sitemap' => array(
                'title' => "Google SEO Sitemap",
                'description' => "This module provides <a href=\"http://sitemaps.org/\">XML Sitemap</a> for your forum. This makes it easier for Google to discover pages on your site. Please see the <a href=\"../inc/plugins/google_seo.txt\">documentation</a> for details.<br /><br />Set to YES to enable Google SEO Sitemap. Setting this to NO also disables all other settings in this group.",
                ),
            'google_seo_sitemap_url' => array(
                'title' => "XML Sitemap URL scheme",
                'description' => "This is the URL scheme used for the XML Sitemap pages. By default, this is <i>sitemap-{\$url}.xml</i> and your sitemap will be called <i>sitemap-index.xml</i>. Please note that if you change this, you will also need to add a new rewrite rule to your .htaccess. If your host does not support mod_rewrite, leave this empty. Your sitemap will then be called <i>misc.php?google_seo_sitemap=index</i>.",
                'optionscode' => "text",
                'value' => "sitemap-{\$url}.xml",
                ),
            'google_seo_sitemap_forums' => array(
                'title' => "XML Sitemap Forums",
                'description' => "Include Forums in the XML Sitemap.",
                'value' => 1,
                ),
            'google_seo_sitemap_threads' => array(
                'title' => "XML Sitemap Threads",
                'description' => "Include Threads in the XML Sitemap.",
                'value' => 1,
                ),
            'google_seo_sitemap_users' => array(
                'title' => "XML Sitemap Users",
                'description' => "Include Users in the XML Sitemap.",
                'value' => 1,
                ),
            'google_seo_sitemap_announcements' => array(
                'title' => "XML Sitemap Announcements",
                'description' => "Include Announcements in the XML Sitemap.",
                'value' => 1,
                ),
            'google_seo_sitemap_calendars' => array(
                'title' => "XML Sitemap Calendars",
                'description' => "Include Calendars in the XML Sitemap.",
                'value' => 1,
                ),
            'google_seo_sitemap_events' => array(
                'title' => "XML Sitemap Events",
                'description' => "Include Events in the XML Sitemap.",
                'value' => 1,
                ),
            'google_seo_sitemap_additional' => array(
                'title' => "XML Sitemap additional pages",
                'description' => "List of additional URLs relative to your site that should be included in the XML Sitemap. If you have any custom pages you can include them here, one page per line. Entries must be relative to your site, i.e. they must not contain http://, and must not start with .. or /.",
                'optionscode' => "textarea",
                'value' => "index.php\nportal.php",
                ),
            'google_seo_sitemap_pagination' => array(
                'title' => "XML Sitemap pagination",
                'description' => "Set the maximum number of links that may appear in a single XML Sitemap before it is split. Setting it too low will result in too many sitemaps, setting it too high may cause server load every time the sitemap is generated. If unsure, leave at 1000.",
                'optionscode' => "text",
                'value' => "1000",
                ),
            'google_seo_sitemap_wol' => array(
                'title' => "XML Sitemap Who's Online",
                'description' => "Define here how you want users (usually search engines) on the XML Sitemap to show up in the Who's Online list. Use [location]link[/location] to make a link to the location of the user.",
                'optionscode' => 'text',
                'value' => 'Fetching the [location]XML Sitemap[/location]'
                ),
            )
        );

    /* Settings for Google SEO URLs */
    google_seo_plugin_settings(
        "google_seo_url",
        "Google SEO URL",
        "URL settings for the Google Search Engine Optimization plugin.",
        array(
            'google_seo_url' => array(
                'title' => "Enable Google SEO URLs",
                'description' => "This module replaces the stock MyBB URLs with descriptive URLs that use words (thread subject, forum title, user name, etc) instead of random numeric IDs. Please see the <a href=\"../inc/plugins/google_seo.txt\">documentation</a> for details.<br /><br />Set to YES to enable Google SEO URL. Setting this to NO also disables all other settings in this group.",
                ),
            'google_seo_url_punctuation' => array(
                'title' => "Punctuation characters",
                'description' => "Punctuation and other special characters are filtered from the URL string and replaced by the separator. By default, this string contains all special ASCII characters including space. If you are running an international forum with non-ASCII script, you might want to add unwanted punctuation characters of those scripts here.",
                'optionscode' => "text",
                'value' => "!\"#$%&'( )*+,-./:;<=>?@[\\]^_`{|}~",
                ),
            'google_seo_url_separator' => array(
                'title' => "URL separator",
                'description' => "Enter the separator that should be used to separate words in the URLs. By default this is - which is a good choice as it is easy to type in most keyboard layouts (single keypress without shift/alt modifier). If you want some other character or string as a separator, you can enter it here. Please note that special characters like :&amp;?@/ or space could render your URLs unuseable or hard to work with.",
                'optionscode' => "text",
                'value' => "-",
                ),
            'google_seo_url_uniquifier' => array(
                'title' => "URL uniquifier",
                'description' => "In case of URL collisions (for example two threads with the same title), the uniquifier is applied to the URL of the newer thread. To guarantee uniqueness, the uniquifier must incorporate the ID and use punctuation other than a single separator. Please see the <a href=\"../inc/plugins/google_seo.txt\">documentation</a> for examples of good and bad uniquifiers.",
                'optionscode' => "text",
                'value' => '{$url}{$separator}{$separator}{$id}',
                ),
            'google_seo_url_translate' => array(
                'title' => "Character Translation",
                'description' => "If you want to replace some characters (German umlaut example: Übergrößenträger =&gt; Uebergroessentraeger) or words in your URLs, please add your translations to <i>inc/plugins/translate.php</i> and then enable this option.",
                ),
            'google_seo_url_lowercase' => array(
                'title' => "lowercase words",
                'description' => "If you prefer lower case URLs, you can set this to YES. This will not affect the way URLs are stored in the database so you can go back to the original case letters any time. Please note that if you set this to YES, you will also have to make sure that your forum URL, as well as scheme and uniqufier are all lowercase too for the URL to be completely in lower case.",
                ),
            'google_seo_url_length_soft' => array(
                'title' => "URL length soft limit",
                'description' => "URLs can be shortened after a soft limit by truncating it after a word (punctuation separator). Set to 0 to disable.",
                'optionscode' => "text",
                'value' => '0',
                ),
            'google_seo_url_length_hard' => array(
                'title' => "URL length hard limit",
                'description' => "URLs can be shortened after a hard limit by truncating it regardless of word separators. Set to 0 to disable.",
                'optionscode' => "text",
                'value' => '0',
                ),
            'google_seo_url_forums' => array(
                'title' => "Forum URL scheme",
                'description' => "Enter the Forum URL scheme. By default this is <i>Forum-{\$url}</i>. Please note that if you change this, you will also need to add a new rewrite rule in your .htaccess file. Leave empty to disable Google SEO URLs for Forums.",
                'optionscode' => "text",
                'value' => 'Forum-{$url}',
                ),
            'google_seo_url_threads' => array(
                'title' => "Thread URL scheme",
                'description' => "Enter the Thread URL scheme. By default this is <i>Thread-{\$url}</i>. Please note that if you change this, you will also need to add a new rewrite rule in your .htaccess file. Leave empty to disable Google SEO URLs for Threads.",
                'optionscode' => "text",
                'value' => 'Thread-{$url}',
                ),
            'google_seo_url_announcements' => array(
                'title' => "Announcement URL scheme",
                'description' => "Enter the Announcement URL scheme. By default this is <i>Announcement-{\$url}</i>. Please note that if you change this, you will also need to add a new rewrite rule in your .htaccess file. Leave empty to disable Google SEO URLs for Announcements.",
                'optionscode' => "text",
                'value' => 'Announcement-{$url}',
                ),
            'google_seo_url_users' => array(
                'title' => "User URL scheme",
                'description' => "Enter the User URL scheme. By default this is <i>User-{\$url}</i>. Please note that if you change this, you will also need to add a new rewrite rule in your .htaccess file. Leave empty to disable Google SEO URLs for Users.",
                'optionscode' => "text",
                'value' => 'User-{$url}',
                ),
            'google_seo_url_calendars' => array(
                'title' => "Calendar URL scheme",
                'description' => "Enter the Calendar URL scheme. By default this is <i>Calendar-{\$url}</i>. Please note that if you change this, you will also need to add a new rewrite rule in your .htaccess file. Leave empty to disable Google SEO URLs for Calendars.",
                'optionscode' => "text",
                'value' => 'Calendar-{$url}',
                ),
            'google_seo_url_events' => array(
                'title' => "Event URL scheme",
                'description' => "Enter the Event URL scheme. By default this is <i>Event-{\$url}</i>. Please note that if you change this, you will also need to add a new rewrite rule in your .htaccess file. Leave empty to disable Google SEO URLs for Events.",
                'optionscode' => "text",
                'value' => 'Event-{$url}',
                ),
            )
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

/* --- End of file. --- */
?>
