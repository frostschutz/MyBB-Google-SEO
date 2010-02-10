<?php
/**
 * Google Search Engine Optimization plugin for MyBB
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

/*
 * THIS PLUGIN IS BETA SOFTWARE AND NOT RECOMMENDED FOR PRODUCTION USE!
 */

// Disallow direct access to this file for security reasons
if(!defined("IN_MYBB"))
{
    die("Direct initialization of this file is not allowed.<br /><br />
         Please make sure IN_MYBB is defined.");
}

/* --- Module initialization: --- */

// The information that shows up on the plugin manager
function google_seo_info()
{
    return array(
        "name"          => "Google Search Engine Optimization",
        "description"   => "Google Search Engine Optimization, following the guidelines of the official <a href=\"http://www.google.com/webmasters/docs/search-engine-optimization-starter-guide.pdf\">Google's SEO starter guide</a>.",
        "author"        => "Andreas Klauer",
        "authorsite"    => "mailto:Andreas.Klauer@metamorpher.de",
        "version"       => "0.4",
    );
}

// Settings
function google_seo_settings()
{
    return array(
        '__group__' => array(
            'name' => "google_seo",
            'title' => "Google SEO",
            'description' => "Google Search Engine Optimization plugin settings",
            'disporder' => 42,
            ),
        'url' => array(
            'title' => "Enable Google SEO URLs",
            'description' => "When set to YES, Google SEO URLs will be used instead of the stock MyBB URLs. For this to work, modifications to .htaccess and inc/functions.php are necessary (see installation instructions).<br /><br />When set to NO, MyBB stock URLs will be used, but old links to Google SEO URLs will still be understood unless you disable the plugin completely.",
            ),
        'redirect' => array(
            'title' => "URL redirection",
            'description' => "When set to YES, redirect to the current valid URL. This is used to redirect stock MyBB URLs to Google SEO URLs, or the other way around when Google SEO URLs are disabled, as well as redirect users who use wrong upper or lower case letters. Do not turn this off unless it causes problems (e.g. when something else redirects too), as it prevents Google from seeing the same page under several different names.",
            ),
        'verify' => array(
            'title' => "Always verify URLs",
            'description' => "When set to YES, the Google SEO URLs are verified every time a link is made to them, so it catches name / title changes of forums, threads, users etc. as early as possible. When you set this to NO, lazy verification will be used instead (the URL will be updated once the page gets actually accessed). Verification will cost you several additional SQL queries per page view, and it's not really necessary as the next time Google crawls your page it will verify everything anyway. However if you're not concerned about load or your users complain about not up to date links, set this to YES.<br /><br />If unsure, leave at NO.",
            ),
        'lowercase' => array(
            'title' => "lowercase words",
            'description' => "Google SEO URLs are case insensitive (the user gets redirected to the correct page when he confuses upper and lower case), so it's fine to keep the original uppercase letters (as they make a difference in many languages). If however for some reason you prefer lower case URLs, you can set this to YES. This will not affect the way URLs are stored in the database so you can go back to the original case letters any time. Please note that if you set this to YES, you will also have to make sure that your forum URL, as well as Google SEO prefixes, postfixes, and uniqufier are all lowercase too.",
            ),
        'separator' => array(
            'title' => "URL separator",
            'description' => "Enter the separator that should be used to separate words in the URLs. By default this is - which is a good choice as it is easy to type in most keyboard layouts (single keypress without shift/alt modifier). If you want some other character or string as a separator, you can enter it here. Please note that special characters like : or @ or / or space could render your URLs unuseable or hard to work with.",
            'optionscode' => "text",
            'value' => "-",
            ),
        'punctuation' => array(
            'title' => "Punctuation characters",
            'description' => "Punctuation and other special characters are filtered from the URL string and replaced by the separator. By default, this string contains all special ASCII characters including space. If you are running an international forum with non-ascii script, you might want to add unwanted punctuation characters of those scripts here.",
            'optionscode' => "text",
            'value' => "!\"#$%&'( )*+,-./:;<=>?@[\\]^_`{|}~",
            ),
        'prefix_forum' => array(
            'title' => "Forum URL prefix",
            'description' => "Enter the prefix that should be used for Forum URLs. By default this is <i>Forum-</i>. Please note that if you change this, you will also need to add a new rewrite rule in your .htaccess file.",
            'optionscode' => "text",
            'value' => "Forum-",
            ),
        'prefix_thread' => array(
            'title' => "Thread URL prefix",
            'description' => "Enter the prefix that should be used for Thread URLs. By default this is <i>Thread-</i>. Please note that if you change this, you will also need to add a new rewrite rule in your .htaccess file.",
            'optionscode' => "text",
            'value' => "Thread-",
            ),
        'prefix_announcement' => array(
            'title' => "Announcement URL prefix",
            'description' => "Enter the prefix that should be used for Announcement URLs. By default this is <i>Announcement-</i>. Please note that if you change this, you will also need to add a new rewrite rule in your .htaccess file.",
            'optionscode' => "text",
            'value' => "Announcement-",
            ),
        'prefix_user' => array(
            'title' => "User URL prefix",
            'description' => "Enter the prefix that should be used for User URLs. By default this is <i>User-</i>. Please note that if you change this, you will also need to add a new rewrite rule in your .htaccess file.",
            'optionscode' => "text",
            'value' => "User-",
            ),
        'prefix_calendar' => array(
            'title' => "Calendar URL prefix",
            'description' => "Enter the prefix that should be used for Calendar URLs. By default this is <i>Calendar-</i>. Please note that if you change this, you will also need to add a new rewrite rule in your .htaccess file.",
            'optionscode' => "text",
            'value' => "Calendar-",
            ),
        'prefix_event' => array(
            'title' => "Event URL prefix",
            'description' => "Enter the prefix that should be used for Event URLs. By default this is <i>Event-</i>. Please note that if you change this, you will also need to add a new rewrite rule in your .htaccess file.",
            'optionscode' => "text",
            'value' => "Event-",
            ),
        'uniquifier' => array(
            'title' => "URL uniquifier",
            'description' => "Google SEO tries to make URLs that do not contain hard to remember ID numbers. However at the same time, URLs <i>must be unique</i>. For the case where the URL cannot be unique (such as two forum threads with the same title) or would be empty (user name that is made up of punctuation only), the URL has to be forced unique. The uniquifier setting determines how that is done, by default it appends the ID of the item to the URL. You can put in any PHP code you like here, as long as it gives a unique string that does not break your .htacess rewrite rules.",
            'optionscode' => "text",
            'value' => '"{$url}-{$id}"',
            ),
        'postfix' => array(
            'title' => "URL postfix",
            'description' => "Enter the postfix that should be used for all URLs. By default this is empty. If you absolutely want your URLs to end with .html, you could put <i>.html</i> here. However, this will clash with stock MyBB SEO URLs and you will also need to add new rewrite rules in your .htaccess file.",
            'optionscode' => "text",
            'value' => "",
            ),
        '404error' => array(
            'title' => "404 error",
            'description' => "When set to YES, send a HTTP 404 error response header for invalid thread / forum / etc error pages. When set to NO, stick with MyBB default behaviour.",
            ),
        '404widget' => array(
            'title' => "404 widget",
            'description' => "When set to YES, add the Google 404 widget for invalid thread / forum / etc error pages.",
            ),
        '404lang' => array(
            'title' => "404 widget language",
            'description' => 'Set the language of the Google 404 widget. See <a href="http://www.google.com/support/webmasters/bin/answer.py?answer=93644">Enhance your custom 404 page</a> for details.',
            'optionscode' => "text",
            'value' => "en",
            ),
        'sitemap' => array(
            'title' => "XML Sitemap",
            'description' => 'When set to YES, the Google SEO plugin will provide <a href="http://sitemaps.org/">XML Sitemap</a> for your forum. This makes it easier for Google to discover pages on your site. See Google SEO installation instructions, <i>Advanced Usage</i> for details on how to make your XML Sitemap available to Google and other search engines. If you say YES here, please also say YES to at least one of the following settings as well, otherwise your XML Sitemap will be empty.',
            ),
        'sitemap_prefix' => array(
            'title' => "XML Sitemap Prefix",
            'description' => "This is the URL prefix used for the XML Sitemap pages. By default, this is 'Sitemap-'. The main sitemap will be called Index, so the complete URL to your sitemap would be http://yoursite/MyBB/Sitemap-Index. Please note that you have to enable the appropriate rewrite rule in your .htaccess for this to work. If you can't use mod_rewrite on your host, you can still use XML Sitemap functionality by setting this prefix to 'index.php?google_seo_sitemap='.",
            'optionscode' => "text",
            'value' => "Sitemap-",
            ),
        'sitemap_Forums' => array(
            'title' => "XML Sitemap Forums",
            'description' => "Include Forums in the XML Sitemap.",
            ),
        'sitemap_Threads' => array(
            'title' => "XML Sitemap Threads",
            'description' => "Include Threads in the XML Sitemap.",
            ),
        'sitemap_Users' => array(
            'title' => "XML Sitemap Users",
            'description' => "Include Users in the XML Sitemap.",
            ),
        'sitemap_Announcements' => array(
            'title' => "XML Sitemap Announcements",
            'description' => "Include Announcements in the XML Sitemap.",
            ),
        'sitemap_Calendars' => array(
            'title' => "XML Sitemap Calendards",
            'description' => "Include Calendards in the XML Sitemap.",
            ),
        'sitemap_Events' => array(
            'title' => "XML Sitemap Events",
            'description' => "Include Events in the XML Sitemap.",
            ),
        'sitemap_additional' => array(
            'title' => "XML Sitemap additional pages",
            'description' => "List of additional URLs relative to your site that should be included in the XML Sitemap. If you have any custom pages you can include them here, one page per line. Entries must be relative to your site, i.e. they must not contain http://, and must not start with .. or /.",
            'optionscode' => "textarea",
            'value' => "index.php
portal.php",
            ),
        'sitemap_pagination' => array(
            'title' => "XML Sitemap pagination",
            'description' => "Set the maximum number of links that may appear in a single XML Sitemap before it is split. Setting this too low will create too many XML Sitemaps, setting it too high can cause too high server load on big forums or low-end servers. This setting only affects server load when a XML Sitemap is generated, it does not affect your ranking in any way. 1000 is a good value that you should not change unless you can handle the load and want fewer sitemaps (however stay below 50000) or can't handle the load and want more smaller sitemaps (however do not go below 100, there is no point in having 50000 sitemaps with one URL in it each).",
            'optionscode' => "text",
            'value' => "1000",
            ),
        'sitemap_debug' => array(
            'title' => "XML Sitemap debug",
            'description' => "This adds a &lt;debug&gt; tag at the end of each sitemap, which contains debug information such as the total time in seconds it took to generate the sitemap. Technically this makes a XML Sitemap invalid, so do not enable this option unless you suspect Sitemap generation to be the cause of high server load (which is unlikely as Sitemaps do not get requested that often, especially when their timestamp did not change). Generation time should stay below 1 second for each Sitemap page, if you get values much higher than that consider reducing the Sitemap pagination value.",
            ),
        'meta' => array(
            'title' => 'Meta tags',
            'description' => "When set to YES, Google SEO will attempt to generate an unique description meta tag based on the content of the current page. For forums, it uses the description of the forum, for threads it uses the contents of the first posting. For this to work, you have to add {\$google_seo_meta} to your <i>headerinclude</i> template."
            ),
        'meta_length' => array(
            'title' => 'Meta tag length',
            'description' => "Maximum length of a meta description. This is to prevent very long posts to be copied into the description tag as a whole. Keep it as long as necessary and as short as possible.",
            'optionscode' => "text",
            'value' => "300",
            ),
        );
}


// This function runs when the plugin is activated.
function google_seo_activate()
{
    global $db;

    $parse = google_seo_settings();

    // Create settings group if it does not exist.
    $query = $db->query("SELECT gid FROM ".TABLE_PREFIX."settinggroups
                         WHERE name='{$parse['__group__']['name']}'");

    if($db->num_rows($query))
    {
        // It exists, get the gid.
        $gid = $db->fetch_field($query, "gid");

        // Update title and description.
        $db->update_query("settinggroups",
                          $parse['__group__'],
                          "gid='$gid'");
    }

    else
    {
        // It does not exist, create it and get the gid.
        $db->insert_query("settinggroups",
                          $parse['__group__']);
        $gid = $db->insert_id();
    }

    // Deprecate all the old entries.
    $db->update_query("settings",
                      array("description" => "DELETEMARKER"),
                      "gid='$gid'");

    $prefix = $parse['__group__']['name']."_";
    unset($parse['__group__']);

    // Create and/or update settings.
    foreach($parse as $key => $value)
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

        $value['name'] = "$prefix$key";
        $value['gid'] = "$gid";

        $query = $db->query("SELECT sid FROM ".TABLE_PREFIX."settings
                             WHERE gid='$gid'
                             AND name='{$value['name']}'");

        if($db->num_rows($query))
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

// This function runs when the plugin is deactivated.
function google_seo_deactivate()
{
    // Keep settings and database in case Google SEO gets activated again.
    // Use uninstall if you want to get rid of Google SEO completely.
}

// This function is called when the plugin is installed.
function google_seo_install()
{
    global $db;

    // Create the Google SEO tables.
    $collation = $db->build_create_table_collation();

    if(!$db->table_exists("google_seo_forums"))
    {
        $db->write_query("CREATE TABLE ".TABLE_PREFIX."google_seo_forums(
                              rowid int unsigned NOT NULL auto_increment,
                              fid int unsigned NOT NULL,
                              url varchar(120) UNIQUE NOT NULL,
                              PRIMARY KEY(rowid)
                          ) TYPE=MyISAM{$collation};");
    }

    if(!$db->table_exists("google_seo_threads"))
    {
        $db->write_query("CREATE TABLE ".TABLE_PREFIX."google_seo_threads(
                              rowid int unsigned NOT NULL auto_increment,
                              tid int unsigned NOT NULL,
                              url varchar(120) UNIQUE NOT NULL,
                              PRIMARY KEY(rowid)
                          ) TYPE=MyISAM{$collation};");
    }

    if(!$db->table_exists("google_seo_announcements"))
    {
        $db->write_query("CREATE TABLE ".TABLE_PREFIX."google_seo_announcements(
                              rowid int unsigned NOT NULL auto_increment,
                              aid int unsigned NOT NULL,
                              url varchar(120) UNIQUE NOT NULL,
                              PRIMARY KEY(rowid)
                          ) TYPE=MyISAM{$collation};");
    }

    if(!$db->table_exists("google_seo_users"))
    {
        $db->write_query("CREATE TABLE ".TABLE_PREFIX."google_seo_users(
                              rowid int unsigned NOT NULL auto_increment,
                              uid int unsigned NOT NULL,
                              url varchar(120) UNIQUE NOT NULL,
                              PRIMARY KEY(rowid)
                          ) TYPE=MyISAM{$collation};");
    }

    if(!$db->table_exists("google_seo_calendars"))
    {
        $db->write_query("CREATE TABLE ".TABLE_PREFIX."google_seo_calendars(
                              rowid int unsigned NOT NULL auto_increment,
                              cid int unsigned NOT NULL,
                              url varchar(120) UNIQUE NOT NULL,
                              PRIMARY KEY(rowid)
                          ) TYPE=MyISAM{$collation};");
    }

    if(!$db->table_exists("google_seo_events"))
    {
        $db->write_query("CREATE TABLE ".TABLE_PREFIX."google_seo_events(
                              rowid int unsigned NOT NULL auto_increment,
                              eid int unsigned NOT NULL,
                              url varchar(120) UNIQUE NOT NULL,
                              PRIMARY KEY(rowid)
                          ) TYPE=MyISAM{$collation};");
    }
}

// This function checks if the plugin already is installed.
function google_seo_is_installed()
{
    global $db;

    return $db->table_exists("google_seo_forums")
        && $db->table_exists("google_seo_threads")
        && $db->table_exists("google_seo_announcements")
        && $db->table_exists("google_seo_users")
        && $db->table_exists("google_seo_calendars")
        && $db->table_exists("google_seo_events");

}

// Uninstall all traces of the plugin.
function google_seo_uninstall()
{
    global $db;

    // Drop the Google SEO tables.
    $db->drop_table("google_seo_forums");
    $db->drop_table("google_seo_threads");
    $db->drop_table("google_seo_announcements");
    $db->drop_table("google_seo_users");
    $db->drop_table("google_seo_calendars");
    $db->drop_table("google_seo_events");

    // Remove the Google SEO setting group.
    $parse = google_seo_settings();
    $query = $db->query("SELECT gid FROM ".TABLE_PREFIX."settinggroups WHERE name='{$parse['__group__']['name']}'");

    if($db->num_rows($query))
    {
        $gid = $db->fetch_field($query, "gid");

        $db->delete_query("settinggroups", "gid='$gid'");
        $db->delete_query("settings", "gid='$gid'");
    }

    rebuild_settings();
}

/* --- Cache: --- */

// Non-persistant cache greatly reduces number of database queries.
global $google_seo_cache;

if(!$google_seo_cache)
{
    $google_seo_cache = array(
        // For URLs:
        "forums" => array(),
        "threads" => array(),
        "announcements" => array(),
        "users" => array(),
        "calendars" => array(),
        "events" => array(),
    );
}

/* --- Update/Create URLs: --- */

// Separate a string by punctuation for use in URLs.
function google_seo_separate($str)
{
    global $settings;

    $pattern = $settings['google_seo_punctuation'];

    if($pattern)
    {
        // Escape the pattern.
        $pattern = preg_replace("/[\\\\\\^\\-\\[\\]\\/]/u",
                                "\\\\\\0",
                                $pattern);

        // Cut off punctuation at beginning and end.
        $str = preg_replace("/^[$pattern]+|[$pattern]+$/u",
                            "",
                            $str);

        // Replace middle punctuation with one separator.
        $str = preg_replace("/[$pattern]+/u",
                            $settings['google_seo_separator'],
                            $str);
    }

    return $str;
}

// Update an URL database entry in a unique way.
// This returns the new unique url.
function google_seo_unique($tablename, $idname, $id, $oldurl, $url)
{
    global $db, $settings, $google_seo_cache;

    if(!$id)
    {
        // Invalid id. This can happen when a user enters random URLs.
        return '';
    }

    if($oldurl && $oldurl == $url)
    {
        // No update required.
        return $url;
    }

    // Update required. Unique check against older articles.
    $query = $db->query("SELECT rowid,$idname
                         FROM ".TABLE_PREFIX."google_seo_$tablename
                         WHERE url='".$db->escape_string($url)."'
                         AND $idname<$id
                         LIMIT 1");
    $collision = $db->fetch_array($query);
    $collid = $collision[$idname];

    if($collid && $collid != $id)
    {
        // There is someone else who uses the same URL as we do.

        // Check if that's actually the latest entry.
        $query = $db->query("SELECT rowid
                             FROM ".TABLE_PREFIX."google_seo_$tablename
                             WHERE $idname='$collid'
                             ORDER BY rowid DESC
                             LIMIT 1");
        $rowid = $db->fetch_field($query, "rowid");

        if(!$rowid || $collision['rowid'] != $rowid)
        {
            $collision = 0;
        }

        else
        {
            // Check if the someone else still exists?
            $query = $db->query("SELECT $idname
                                 FROM ".TABLE_PREFIX."$tablename
                                 WHERE $idname='$collid'
                                 LIMIT 1");

            if(!$db->num_rows($query))
            {
                // Old thread / user / whatever was deleted.
                $collision = 0;
            }
        }
    }

    // Unresolved collision calls for some uniquifier action.
    if(!$url || $collision)
    {
        eval("\$url=".$settings['google_seo_uniquifier'].";");

        // Special case: the old URL had an uniquifier too.
        if($oldurl == $url)
        {
            // No update required after all.
            return $url;
        }
    }

    // Delete old entry in case it's still not unique.
    // (renamed and renamed back, other side needs uniquifier, etc).
    $db->delete_query("google_seo_$tablename",
                      "url='".$db->escape_string($url)."'");

    // Insert the URL into the database.
    $db->write_query("INSERT INTO ".TABLE_PREFIX."google_seo_$tablename
                      ($idname,url)
                      VALUES('$id','".$db->escape_string($url)."')");

    return $url;
}

// Fetch or update a Google SEO URL
function google_seo_update($tablename, $idname, $titlename, $id, $verify=0)
{
    global $google_seo_cache, $db, $settings;

    // If it's already in the cache, just use that.
    $url = $google_seo_cache[$tablename][$id];

    if($url)
    {
        return $url;
    }

    // Obtain the string for this uid.
    $query = $db->query("SELECT url FROM ".TABLE_PREFIX."google_seo_$tablename
                         WHERE $idname='$id'
                         ORDER BY rowid DESC
                         LIMIT 1");
    $url = $db->fetch_field($query, "url");

    if(!$url || $verify)
    {
        // Make or verify the URL.
        $query = $db->query("SELECT $titlename FROM ".TABLE_PREFIX."$tablename
                             WHERE $idname='$id'
                             LIMIT 1");

        $title = $db->fetch_field($query, $titlename);

        if(!$title)
        {
            // Invalid id. This can happen when a user enters random URLs.
            return;
        }

        $title = google_seo_separate($title);
        $url = google_seo_unique($tablename, $idname, $id, $url, $title);
    }

    // Force lowercase if requested.
    if($settings['google_seo_lowercase'])
    {
        if(function_exists("mb_strtolower"))
        {

            $url = mb_strtolower($url, "UTF-8");
        }

        else
        {
            $url = strtolower($url);
        }
    }

    $google_seo_cache[$tablename][$id] = $url;

    return $url;
}

/* --- Query/Get URLs: --- */

// Get the profile link.
// Replacement for inc/functions.php::get_profile_link().
if(!function_exists("get_profile_link"))
{
    function get_profile_link($uid=0)
    {
        global $settings;

        $link = ($settings['google_seo_url']
                 ? google_seo_get_profile_link($uid)
                 : mybb_get_profile_link($uid));

        return $link;
    }
}

function google_seo_get_profile_link($uid=0)
{
    global $settings;

    $url = google_seo_update("users", "uid", "username",
                             $uid, $settings['google_seo_verify']);

    if(!$url)
    {
        // Invalid id. This can happen when a user enters random URLs.
        return;
    }

    $link = $settings['google_seo_prefix_user']
        . $url
        . $settings['google_seo_postfix'];

    return htmlspecialchars_uni($link);
}

// Get the announcement link.
// Replacement for inc/functions.php::get_announcement_link().
if(!function_exists("get_announcement_link"))
{
    function get_announcement_link($aid=0)
    {
        global $settings;

        $link = ($settings['google_seo_url']
                 ? google_seo_get_announcement_link($aid)
                 : mybb_get_announcement_link($aid));

        return $link;
    }
}

function google_seo_get_announcement_link($aid=0)
{
    global $settings;

    $url = google_seo_update("announcements", "aid", "subject",
                             $aid, $settings['google_seo_verify']);

    if(!$url)
    {
        // Invalid id. This can happen when a user enters random URLs.
        return;
    }

    $link = $settings['google_seo_prefix_announcement']
        . $url
        . $settings['google_seo_postfix'];

    return htmlspecialchars_uni($link);
}


// Build the forum link.
// Replacement for inc/functions.php::get_forum_link().
if(!function_exists("get_forum_link"))
{
    function get_forum_link($fid, $page=0)
    {
        global $settings;

        $link = ($settings['google_seo_url']
                 ? google_seo_get_forum_link($fid, $page)
                 : mybb_get_forum_link($fid, $page));

        return $link;
    }
}

function google_seo_get_forum_link($fid, $page=0)
{
    global $settings;

    $url = google_seo_update("forums", "fid", "name",
                             $fid, $settings['google_seo_verify']);

    if(!$url)
    {
        // Invalid id. This can happen when a user enters random URLs.
        return;
    }

    $link = $settings['google_seo_prefix_forum']
        . $url
        . $settings['google_seo_postfix'];

    if($page)
    {
        $link .= "?page=$page";
    }

    return htmlspecialchars_uni($link);
}

// Build the thread link.
// Replacement for inc/functions.php::get_thread_link().
if(!function_exists("get_thread_link"))
{
    function get_thread_link($tid, $page=0, $action='')
    {
        global $settings;

        $link = ($settings['google_seo_url']
                 ? google_seo_get_thread_link($tid, $page, $action)
                 : mybb_get_thread_link($tid, $page, $action));

        return $link;
    }
}

function google_seo_get_thread_link($tid, $page=0, $action='')
{
    global $settings;

    $url = google_seo_update("threads", "tid", "subject",
                             $tid, $settings['google_seo_verify']);

    if(!$url)
    {
        // Invalid id. This can happen when a user enters random URLs.
        return;
    }

    $link = $settings['google_seo_prefix_thread']
        . $url
        . $settings['google_seo_postfix'];

    if($page && $action)
    {
        $link .= "?page=$page&action=$action";
    }

    else if($page)
    {
        $link .= "?page=$page";
    }

    else if($action)
    {
        $link .= "?action=$action";
    }

    return htmlspecialchars_uni($link);
}

// Build the post link.
// Replacement for inc/functions.php::get_post_link().
if(!function_exists("get_post_link"))
{
    function get_post_link($pid, $tid=0)
    {
        global $settings;

        $link = ($settings['google_seo_url']
                 ? google_seo_get_post_link($pid, $tid)
                 : mybb_get_post_link($pid, $tid));

        return $link;
    }
}

function google_seo_get_post_link($pid, $tid=0)
{
    global $settings, $db;

    if(!$tid)
    {
        // We didn't get a tid so we have to fetch it. Ugly.
        // Code based on showthread.php:

        global $style;

        if(isset($style) && $style['pid'] == $pid && $style['tid'])
        {
            $tid = $style['tid'];
        }

        else
        {
            $options = array(
                "limit" => 1
                );
            $query = $db->simple_select("posts", "tid", "pid=".$pid, $options);
            $tid = $db->fetch_field($query, "tid");
        }
    }

    $url = google_seo_update("threads", "tid", "subject",
                             $tid, $settings['google_seo_verify']);

    if(!$url)
    {
        // Invalid id. This can happen when a user enters random URLs.
        return;
    }

    $link = $settings['google_seo_prefix_thread']
        . $url
        . $settings['google_seo_postfix_thread']
        . "?pid=$pid";

    return htmlspecialchars_uni($link);
}

// Build the event link.
// Replacement for inc/functions.php::get_event_link().
if(!function_exists("get_event_link"))
{
    function get_event_link($eid)
    {
        global $settings;

        $link = ($settings['google_seo_url']
                 ? google_seo_get_event_link($eid)
                 : mybb_get_event_link($eid));

        return $link;
    }
}

function google_seo_get_event_link($eid)
{
    global $settings;

    $url = google_seo_update("events", "eid", "name",
                             $eid, $settings['google_seo_verify']);

    if(!$url)
    {
        // Invalid id. This can happen when a user enters random URLs.
        return;
    }

    $link = $settings['google_seo_prefix_event']
        . $url
        . $settings['google_seo_postfix'];

    return htmlspecialchars_uni($link);
}

// Build the link to a specified date on the calendar.
// Replacement for inc/functions.php::get_calendar_link().
if(!function_exists("get_calendar_link"))
{
    function get_calendar_link($calendar, $year=0, $month=0, $day=0)
    {
        global $settings;

        $link = ($settings['google_seo_url']
                 ? google_seo_get_calendar_link($calendar, $year, $month, $day)
                 : mybb_get_calendar_link($calendar, $year, $month, $day));

        return $link;
    }
}

function google_seo_get_calendar_link($cid, $year=0, $month=0, $day=0)
{
    global $settings;

    $url = google_seo_update("calendars", "cid", "name",
                             $cid, $settings['google_seo_verify']);

    if(!$url)
    {
        // Invalid id. This can happen when a user enters random URLs.
        return;
    }

    $link = $settings['google_seo_prefix_calendar']
        . $url
        . $settings['google_seo_postfix'];

    if($year)
    {
        $link .= "?year=$year";

        if($month)
        {
            $link .= "&month=$month";

            if($day)
            {
                $link .= "&day=$day&action=dayview";
            }
        }
    }

    return htmlspecialchars_uni($link);
}

// Build the link to a specified week on the calendar
// Replacement for inc/functions.php::get_calendar_week_link().
if(!function_exists("get_calendar_week_link"))
{
    function get_calendar_week_link($calendar, $week)
    {
        global $settings;

        $link = ($settings['google_seo_url']
                 ? google_seo_get_calendar_week_link($calendar, $week)
                 : mybb_get_calendar_week_link($calendar, $week));

        return $link;
    }
}

function google_seo_get_calendar_week_link($cid, $week)
{
    global $settings;

    $url = google_seo_update("calendars", "cid", "name",
                             $cid, $settings['google_seo_verify']);

    if(!$url)
    {
        // Invalid id. This can happen when a user enters random URLs.
        return;
    }

    $link = $settings['google_seo_prefix_calendar']
        . $url
        . $settings['google_seo_postfix']
        . "?action=weekview&week=$week";

    return htmlspecialchars_uni($link);
}

/* --- Page lookup / URL redirects: --- */

// Convert URL to ID.
function google_seo_url_id($tablename, $idname, $url)
{
    global $db;

    $query = $db->query("SELECT $idname
                         FROM ".TABLE_PREFIX."google_seo_$tablename
                         WHERE url='".$db->escape_string($url)."'
                         ORDER BY rowid DESC
                         LIMIT 1");

    $id = $db->fetch_field($query, $idname);

    if(!$id)
    {
        // Something went wrong. Maybe user added some punctuation?
        $url = google_seo_separate($url);

        $query = $db->query("SELECT $idname
                             FROM ".TABLE_PREFIX."google_seo_$tablename
                             WHERE url='".$db->escape_string($url)."'
                             ORDER BY rowid DESC
                             LIMIT 1");

        $id = $db->fetch_field($query, $idname);
    }

    return $id;
}

// Obtain the current URL.
function google_seo_current_url()
{
    // Determine the current page URL.
    if($_SERVER["HTTPS"] == "on")
    {
        $page_url = "https://".$_SERVER["SERVER_NAME"];

        if($_SERVER["SERVER_PORT"] != "443")
        {
            $page_url .= ":".$_SERVER["SERVER_PORT"];
        }
    }

    else
    {
        $page_url = "http://".$_SERVER["SERVER_NAME"];

        if($_SERVER["SERVER_PORT"] != "80")
        {
            $page_url .= ":".$_SERVER["SERVER_PORT"];
        }
    }

    $page_url .= $_SERVER["REQUEST_URI"];

    return urldecode($page_url);
}

// Look up pages, verify, redirect if necessary.
// Use a high priority since we may have to redirect the page.
$plugins->add_hook("global_start", "google_seo_global_start", 5);

function google_seo_global_start()
{
    global $db, $settings, $mybb;

    // Translate URL name to ID and verify.
    switch(THIS_SCRIPT)
    {
        case 'forumdisplay.php':
            // Translation.
            $url = $mybb->input['google_seo_forum'];

            if($url && !array_key_exists('fid', $mybb->input))
            {
                $fid = google_seo_url_id("forums", "fid", $url);
                $mybb->input['fid'] = $fid;
            }

            // Verification.
            $fid = $mybb->input['fid'];

            if($fid && $settings['google_seo_url'])
            {
                google_seo_update("forums", "fid", "name", $fid, 1);
            }

            break;

        case 'showthread.php':
            // Translation.
            $url = $mybb->input['google_seo_thread'];

            if($url && !array_key_exists('tid', $mybb->input))
            {
                $tid = google_seo_url_id("threads", "tid", $url);
                $mybb->input['tid'] = $tid;
            }

            // Verification.
            $tid = $mybb->input['tid'];

            if($tid && $settings['google_seo_url'])
            {
                google_seo_update("threads", "tid", "subject",
                                  $tid, 1);
            }

            $pid = $mybb->input['pid'];

            break;

        case 'announcement.php':
            // Translation.
            $url = $mybb->input['google_seo_announcement'];

            if($url && !array_key_exists('aid', $mybb->input))
            {
                $aid = google_seo_url_id("announcements", "aid", $url);
                $mybb->input['aid'] = $aid;
            }

            // Verification.
            $aid = $mybb->input['aid'];

            if($aid && $settings['google_seo_url'])
            {
                google_seo_update("announcements", "aid", "subject",
                                  $aid, 1);
            }

            break;

        case 'member.php':
            // Translation.
            $url = $mybb->input['google_seo_user'];

            if($url && !array_key_exists('uid', $mybb->input))
            {
                $uid = google_seo_url_id("users", "uid", $url);
                $mybb->input['uid'] = $uid;
            }

            // Verification.
            $uid = $mybb->input['uid'];

            if($uid && $mybb->input['action'] == 'profile'
               && $settings['google_seo_url'])
            {
                google_seo_update("users", "uid", "username",
                                  $uid, 1);
            }

            break;

        case 'calendar.php':
            // Translation.
            $url = $mybb->input['google_seo_event'];

            if($url && !array_key_exists('eid', $mybb->input))
            {
                $eid = google_seo_url_id("events", "eid", $url);
                $mybb->input['eid'] = $eid;
            }

            // Verification.
            $eid = $mybb->input['eid'];

            if($eid)
            {
                if($settings['google_seo_url'])
                {
                    google_seo_update("events", "eid", "name",
                                      $eid, 1);
                }
            }

            else if(!$url)
            {
                $url = $mybb->input['google_seo_calendar'];

                if($url && !array_key_exists('calendar', $mybb->input))
                {
                    $cid = google_seo_url_id("calendars", "cid", $url);
                    $mybb->input['calendar'] = $cid;
                }

                // Verification.
                $cid = $mybb->input['calendar'];

                if($cid)
                {
                    if($settings['google_seo_url'])
                    {
                        google_seo_update("calendars", "cid",
                                          "name", $cid, 1);
                    }
                }
            }

            break;

        default:
            return;
    }

    // Redirection.
    if($settings['google_seo_redirect'])
    {
        // Build URL we should be at:
        if($fid)
        {
            $target = get_forum_link($fid, $mybb->input['page']);
        }

        else if($pid)
        {
            // Don't pass $tid here to verify that the thread was not split.
            // Rare case and costs us a query but it's cool to be redirected
            // from Thread-oldthreadname?pid=x to Thread-newthreadname?pid=x
            $target = get_post_link($pid);
        }

        else if($tid)
        {
            $target = get_thread_link($tid, $mybb->input['page'], $mybb->input['action']);
        }

        else if($aid)
        {
            $target = get_announcement_link($aid);
        }

        else if($uid && $mybb->input['action'] == 'profile')
        {
            $target = get_profile_link($uid);
        }

        else if($eid)
        {
            $target = get_event_link($eid);
        }

        else if($cid)
        {
            if($mybb->input['action'] == "weekview")
            {
                $target = get_calendar_week_link($cid, $mybb->input['week']);
            }

            else
            {
                $target = get_calendar_link($cid, $mybb->input['year'], $mybb->input['month'], $mybb->input['day']);
            }
        }
    }

    if($target)
    {
        $target_location = $settings['bburl'].'/'.$target;
        $target = urldecode($target_location);
        $current = google_seo_current_url();

        // Not identical (although it may only be the query string).
        if($target != $current)
        {
            // parse_url unfortunately can make a mess out of UTF-8 on some hosts.
            // So we split the query string by ourselves.
            if(function_exists('mb_split'))
            {
                // MyBB unfortunately does not set up the encoding for us.
                mb_internal_encoding('UTF-8');
                mb_regex_encoding('UTF-8');

                $target_location = mb_split("\\?", $target_location, 2);
                $target_location = $target_location[0];
                $target_parse = mb_split("\\?", $target, 2);
                $current_parse = mb_split("\\?", $current, 2);
            }

            else
            {
                // No multibyte support in this day and age? Good luck.
                $target_location = split("\\?", $target_location, 2);
                $target_location = $target_location[0];
                $target_parse = split("\\?", $target, 2);
                $current_parse = split("\\?", $current, 2);
            }

            // Definitely not identical?
            if($target_parse[0] != $current_parse[0])
            {
                // Redirect but retain query.
                parse_str($target_parse[1], &$query_target);
                parse_str($current_parse[1], &$query_current);
                $query = array_merge($query_target, $query_current);

                foreach($query as $k=>$v)
                {
                    $querystr[] = "$k=".urlencode($v);
                }

                if(sizeof($querystr))
                {
                    $target_location .= "?" . implode("&", $querystr);
                }

                header("Location: $target_location", true, 301);
                exit;
            }
        }
    }

    // All done, no more redirects, add meta now.
    if($settings['google_seo_meta'])
    {
        if($fid)
        {
            google_seo_meta_forum($fid);
        }

        if($tid)
        {
            google_seo_meta_thread($tid);
        }

        if($uid)
        {
            google_seo_meta_user($uid);
        }

        if($aid)
        {
            google_seo_meta_announcement($aid);
        }

        if($eid)
        {
            google_seo_meta_event($eid);
        }

        if($cid)
        {
            google_seo_meta_calendar($cid);
        }
    }
}

/* --- 404 error handling: --- */

// 404 error handling if the user wants this.
$plugins->add_hook("error", "google_seo_error");

function google_seo_error($error)
{
    global $settings, $mybb;

    if($settings['google_seo_404error'] && !$mybb->input['ajax'])
    {
        // Technically, this is incorrect, as it also hits error messages
        // that are intended to occur. But there is no good way of detecting
        // all cases that should be 404 (error due to bad link) and the user
        // gets to see the same page either way.

        // As a side effect, 404 erroring all error pages gives you a list
        // in Google's Webmaster tools of pages that Google shouldn't access
        // and therefore should be disallowed in robots.txt.

        @header("HTTP/1.1 404 Not Found");

        if($settings['google_seo_404widget'])
        {
            $error .= "\n <script type=\"text/javascript\">\n"
                ." <!--\n"
                ." var GOOG_FIXURL_LANG='{$settings['google_seo_404lang']}';\n"
                ." var GOOG_FIXURL_SITE='{$settings['bburl']}';\n"
                ." -->\n"
                ." </script>\n"
                ." <script type=\"text/javascript\" src=\""
                ."http://linkhelp.clients.google.com/tbproxy/lh/wm/fixurl.js"
                ."\"></script>\n";
        }
    }
}

// Custom 404 error pages:
$plugins->add_hook("misc_start", "google_seo_error_404page");

function google_seo_error_404page()
{
    global $mybb;

    if($mybb->input['google_seo_error'] == 404)
    {
        error("404 Not Found");
    }
}

/* --- Sitemap: --- */

// Build and output a Sitemap
function google_seo_sitemap($tag, $items)
{
    global $settings;

    $bbsite = $settings['bburl'] . '/';

    if($tag == "sitemap")
    {
        $output[] = '<?xml version="1.0" encoding="UTF-8"?>
<sitemapindex xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';
    }

    else if($tag == "url")
    {
        $output[] = '<?xml version="1.0" encoding="UTF-8"?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';
    }

    foreach($items as $item)
    {
        // loc
        $output[] = "  <$tag>";
        $loc = htmlspecialchars($item['loc'], ENT_QUOTES, "UTF-8");
        $output[] = "    <loc>$bbsite$loc</loc>";

        // lastmod
        // Hack: set earliest possible date to april 1970,
        //       takes care of cid showing up as date.
        if($item['lastmod'] > 10000000)
        {
            $lastmod = gmdate('Y-m-d\TH:i\Z', $item['lastmod']);
            $output[] = "    <lastmod>$lastmod</lastmod>";
        }

        // changefreq
        if($item['changefreq'])
        {
            $output[] = "    <changefreq>{$item['changefreq']}</changefreq>";
        }

        // priority
        if($item['priority'])
        {
            $output[] = "    <priority>{$item['priority']}</priority>";
        }

        $output[] = "  </$tag>";
    }

    if($settings['google_seo_sitemap_debug'])
    {
        global $maintimer;
        $totaltime = $maintimer->stop();
        $output[] = "<debug><totaltime>$totaltime</totaltime></debug>";
    }

    if($tag == "sitemap")
    {
        $output[] = "</sitemapindex>";
    }

    else if($tag == "url")
    {
        $output[] = "</urlset>";
    }

    @header('Content-type: text/xml; charset=utf-8');
    echo implode("\n", $output);
}

// Generate the sitemap.
function google_seo_sitemap_gen($prefix, $type, $page, $pagination)
{
    global $db, $mybb, $settings;

    if(!$settings["google_seo_sitemap_$type"])
    {
        return;
    }

    switch($type)
    {
        case "Forums":
            $table = 'forums';
            $idname = 'fid';
            $datename = 'lastpost';
            $getlink = 'get_forum_link';
            break;

        case "Threads":
            $table = 'threads';
            $idname = 'tid';
            $datename = 'dateline';
            $getlink = 'get_thread_link';
            $condition = "WHERE visible>0 AND closed NOT LIKE 'moved|%'";
            break;

        case "Users":
            $table = 'users';
            $idname = 'uid';
            $datename = 'regdate';
            $getlink = 'get_profile_link';
            break;

        case "Announcements":
            $table = 'announcements';
            $idname = 'aid';
            $datename = 'startdate';
            $getlink = 'get_announcement_link';
            break;

        case "Calendars":
            if($mybb->settings['enablecalendar'] == 0
               || $mybb->usergroup['canviewcalendar'] == 0)
            {
                return;
            }

            $table = 'calendars';
            $idname = 'cid';
            $datename = 'disporder';
            $getlink = 'get_calendar_link';
            break;

        case "Events":
            if($mybb->settings['enablecalendar'] == 0
               || $mybb->usergroup['canviewcalendar'] == 0)
            {
                return;
            }

            $table = 'events';
            $idname ='eid';
            $datename = 'dateline';
            $getlink = 'get_event_link';
            break;
    }

    if(!$page)
    {
        // Do a pagination index.
        $query = $db->query("SELECT COUNT(*) as count
                             FROM ".TABLE_PREFIX."$table
                             $condition");
        $count = $db->fetch_field($query, "count");
        $offset = 0;

        while($offset < $count)
        {
            $page++;
            $item = array();
            $item["loc"] = "{$prefix}{$type}?page={$page}";

            // find the last (newest) of the oldest posts
            $query = $db->query("SELECT $datename FROM
                                   (SELECT $datename FROM ".TABLE_PREFIX."$table
                                    $condition
                                    ORDER BY $datename ASC
                                    LIMIT $offset, $pagination) AS foobar
                                 ORDER BY $datename DESC LIMIT 1");

            $lastmod = $db->fetch_field($query, $datename);

            if($lastmod)
            {
                $item["lastmod"] = $lastmod;
            }

            $items[] = $item;

            $offset += $pagination;
       }

        return $items;
    }

    // Build the sitemap for this page.
    $offset = ($page - 1) * $pagination;

    $query = $db->query("SELECT $idname,$datename FROM ".TABLE_PREFIX."$table
                         $condition
                         ORDER BY $datename ASC
                         LIMIT $offset, $pagination");

    while($row = $db->fetch_array($query))
    {
        $item = array();
        $item['loc'] = call_user_func($getlink, $row[$idname]);

        if($row[$datename])
        {
            $item['lastmod'] = $row[$datename];
        }

        $items[] = $item;
    }

    google_seo_sitemap("url", $items);
}

// Build the main Index sitemap.
function google_seo_sitemap_index($prefix, $page, $pagination)
{
    global $settings;

    if($page)
    {
        // Additional pages.
        $locs = explode("\n",$settings['google_seo_sitemap_additional']);

        foreach($locs as $loc)
        {
            $loc = trim($loc);

            if($loc)
            {
                $items[] = array('loc' => $loc);
            }
        }

        google_seo_sitemap("url", $items);
        return;
    }

    $items = array();

    foreach(array("Forums", "Threads", "Users", "Announcements",
                  "Calendars", "Events") as $type)
    {
        $gen = google_seo_sitemap_gen($prefix, $type, $page, $pagination);

        if(sizeof($gen))
        {
            $items = array_merge($items, $gen);
        }
    }

    if($settings['google_seo_sitemap_additional'])
    {
        $items[] = array('loc' => "{$prefix}Index?page=1");
    }

    google_seo_sitemap("sitemap", $items);
}

// Hijack index.php for XML Sitemap creation.
$plugins->add_hook("index_start", "google_seo_index_start");

function google_seo_index_start()
{
    global $mybb, $settings;

    if(!isset($mybb->input['google_seo_sitemap']))
    {
        // This does not mean us. Do nothing.
        return;
    }

    if(!$settings['google_seo_sitemap'])
    {
        // Sitemap is not enabled.
        error("Sitemap disabled");
    }

    $type = $mybb->input['google_seo_sitemap'];

    if($type != "Index" && !$settings["google_seo_sitemap_$type"])
    {
        // This type of sitemap is not enabled.
        error("Sitemap disabled or invalid");
    }

    // Set pagination to something between 100 and 50000.
    $pagination = (int)$settings['google_seo_sitemap_pagination'];
    $pagination = min(max($pagination, 100), 50000);
    $prefix = $settings['google_seo_sitemap_prefix'];

    // Set page to something between 0 and 50000.
    $page = (int)$mybb->input['page'];
    $page = min(max($page, 0), 50000);

    // Temporarily turn off 'always verify'.
    // It's expensive and not necessary for a Sitemap.
    $settings['google_seo_verify'] = 0;

    if($type == "Index")
    {
        google_seo_sitemap_index($prefix, $page, $pagination);
    }

    else
    {
        google_seo_sitemap_gen($prefix, $type, $page, $pagination);
    }

    exit;
}

/* --- Meta description: --- */

global $google_seo_meta;

function google_seo_meta($description)
{
    global $google_seo_meta, $settings;

    if($description)
    {
        $description = preg_replace("/\[[^\]]+]/u", "", $description);
        $description = preg_replace("/[ \n\t\r]+/u", " ", $description);
        $description = trim($description);
        $description = my_substr($description, 0, $settings['google_seo_meta_length'], true);
        $description = trim($description);

        if(description)
        {
            $google_seo_meta .= "<meta name=\"description\""
                ." content=\"$description\" />";
        }
    }
}

function google_seo_meta_forum($fid)
{
    global $db;

    $query = $db->simple_select("forums", "description", "fid=$fid");
    $description = $db->fetch_field($query, "description");
    google_seo_meta($description);
}

function google_seo_meta_thread($tid)
{
    global $db;

    $query = $db->simple_select("threads", "firstpost", "tid=$tid");
    $firstpost = $db->fetch_field($query, "firstpost");
    google_seo_meta_post($firstpost);
}

function google_seo_meta_post($pid)
{
    global $db;

    $query = $db->simple_select("posts", "message", "pid=$pid");
    $message = $db->fetch_field($query, "message");
    google_seo_meta($message);
}

function google_seo_meta_user($uid)
{
/*
 * Hmmm, what do put here?
 *
 * Maybe like in the profile:
 * User admin, joined 12-29-2008, last visit 01-01-2009, total posts 7.
 *
 * But that string has to be localized in a setting.
 *
 */
}

function google_seo_meta_announcement($aid)
{
    global $db;

    $query = $db->simple_select("announcements", "message", "aid=$aid");
    $message = $db->fetch_field($query, "message");
    google_seo_meta($message);
}

function google_seo_meta_event($eid)
{
    global $db;

    $query = $db->simple_select("events", "description", "eid=$eid");
    $description = $db->fetch_field($query, "description");
    google_seo_meta($description);
}

function google_seo_meta_calendar($cid)
{
/*
 * Hmmm, what to put here?
 *
 * Calendars don't have descriptions.
 *
 * Maybe the number of events? :-/
 *
 */
}

/* --- End of file. --- */
?>
