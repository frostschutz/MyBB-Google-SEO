<?php
/**
 * Google SEO plugin for MyBB.
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
    global $settings;

    $info = array(
        "name"          => "Google SEO <b>BETA</b>",
        "description"   => "Google Search Engine Optimization as described in the official <a href=\"http://www.google.com/webmasters/docs/search-engine-optimization-starter-guide.pdf\">Google's SEO starter guide</a>. Please see the <a href=\"{$settings['bburl']}/inc/plugins/google_seo.txt\">documentation</a> for details.",
        "author"        => "Andreas Klauer",
        "authorsite"    => "mailto:Andreas.Klauer@metamorpher.de",
        "version"       => "0.5",
    );

/*
 *   if(google_seo_plugin_is_installed())
 *   {
 *       // Provide some additional status information.
 *       $info['description'] .= "\n<ul>\n  <li>"
 *           .implode(google_seo_plugin_status(), "</li>\n  <li>")
 *           ."</li>\n</ul>\n";
 *   }
 */
    return $info;
}

/* --- Plugin Helpers: --- */

/**
 * Additional status information about the plugin.
 *
 * @return array of additional status strings that will be shown in a bullet list
 */
function google_seo_plugin_status()
{
    return array("1","2","3");
}

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

    return $db->table_exists("google_seo_forums")
        && $db->table_exists("google_seo_threads")
        && $db->table_exists("google_seo_announcements")
        && $db->table_exists("google_seo_users")
        && $db->table_exists("google_seo_calendars")
        && $db->table_exists("google_seo_events");

}

/**
 * Installs the plugin.
 */
function google_seo_plugin_install()
{
    global $db;

    // Create the Google SEO tables.
    $collation = $db->build_create_table_collation();

    if(!$db->table_exists("google_seo_forums"))
    {
        $db->write_query("CREATE TABLE ".TABLE_PREFIX."google_seo_forums(
                              rowid int unsigned NOT NULL auto_increment,
                              id int unsigned NOT NULL,
                              url varchar(120) UNIQUE NOT NULL,
                              PRIMARY KEY(rowid)
                          ) TYPE=MyISAM{$collation};");
    }

    if(!$db->table_exists("google_seo_threads"))
    {
        $db->write_query("CREATE TABLE ".TABLE_PREFIX."google_seo_threads(
                              rowid int unsigned NOT NULL auto_increment,
                              id int unsigned NOT NULL,
                              url varchar(120) UNIQUE NOT NULL,
                              PRIMARY KEY(rowid)
                          ) TYPE=MyISAM{$collation};");
    }

    if(!$db->table_exists("google_seo_announcements"))
    {
        $db->write_query("CREATE TABLE ".TABLE_PREFIX."google_seo_announcements(
                              rowid int unsigned NOT NULL auto_increment,
                              id int unsigned NOT NULL,
                              url varchar(120) UNIQUE NOT NULL,
                              PRIMARY KEY(rowid)
                          ) TYPE=MyISAM{$collation};");
    }

    if(!$db->table_exists("google_seo_users"))
    {
        $db->write_query("CREATE TABLE ".TABLE_PREFIX."google_seo_users(
                              rowid int unsigned NOT NULL auto_increment,
                              id int unsigned NOT NULL,
                              url varchar(120) UNIQUE NOT NULL,
                              PRIMARY KEY(rowid)
                          ) TYPE=MyISAM{$collation};");
    }

    if(!$db->table_exists("google_seo_calendars"))
    {
        $db->write_query("CREATE TABLE ".TABLE_PREFIX."google_seo_calendars(
                              rowid int unsigned NOT NULL auto_increment,
                              id int unsigned NOT NULL,
                              url varchar(120) UNIQUE NOT NULL,
                              PRIMARY KEY(rowid)
                          ) TYPE=MyISAM{$collation};");
    }

    if(!$db->table_exists("google_seo_events"))
    {
        $db->write_query("CREATE TABLE ".TABLE_PREFIX."google_seo_events(
                              rowid int unsigned NOT NULL auto_increment,
                              id int unsigned NOT NULL,
                              url varchar(120) UNIQUE NOT NULL,
                              PRIMARY KEY(rowid)
                          ) TYPE=MyISAM{$collation};");
    }
}

/**
 * Uninstalls the plugin.
 */
function google_seo_plugin_uninstall()
{
    global $db;

    // Drop the Google SEO tables.
    $db->drop_table("google_seo_forums");
    $db->drop_table("google_seo_threads");
    $db->drop_table("google_seo_announcements");
    $db->drop_table("google_seo_users");
    $db->drop_table("google_seo_calendars");
    $db->drop_table("google_seo_events");

    // Remove the Google SEO setting groups.
    $query = $db->query("SELECT name,gid FROM ".TABLE_PREFIX."settinggroups WHERE name LIKE 'google_seo_%'");

    while($group = $db->fetch_array($query))
    {
        $gid = $group['gid'];

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
            'google_seo_sitemap_debug' => array(
                'title' => "XML Sitemap debug",
                'description' => "This adds a &lt;debug&gt; tag at the end of each sitemap, which contains debug information such as the total time in seconds it took to generate the sitemap. Technically this makes a XML Sitemap invalid, so do not enable this option unless you suspect Sitemap generation to be the cause of high server load (which is unlikely as Sitemaps do not get requested that often, especially when their timestamp did not change). Generation time should stay below 1 second for each Sitemap page, if you get values much higher than that consider reducing the Sitemap pagination value.",
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
                'description' => "Punctuation and other special characters are filtered from the URL string and replaced by the separator. By default, this string contains all special ASCII characters including space. If you are running an international forum with non-ascii script, you might want to add unwanted punctuation characters of those scripts here.",
                'optionscode' => "text",
                'value' => "!\"#$%&'( )*+,-./:;<=>?@[\\]^_`{|}~",
                ),
            'google_seo_url_separator' => array(
                'title' => "URL separator",
                'description' => "Enter the separator that should be used to separate words in the URLs. By default this is - which is a good choice as it is easy to type in most keyboard layouts (single keypress without shift/alt modifier). If you want some other character or string as a separator, you can enter it here. Please note that special characters like : or @ or / or space could render your URLs unuseable or hard to work with.",
                'optionscode' => "text",
                'value' => "-",
                ),
            'google_seo_url_uniquifier' => array(
                'title' => "URL uniquifier",
                'description' => "Google SEO tries to make URLs that do not contain ID numbers. However at the same time, URLs <i>must be unique</i>. For the case where the URL cannot be unique (such as two forum threads with the same title) or would be empty (user name that is made up of punctuation only), the URL must be made unique by incorporating the ID. This setting determines how that is done, by default it appends the ID of the item to the end of the URL.",
                'optionscode' => "text",
                'value' => '{$url}-{$id}',
                ),
            'google_seo_url_lowercase' => array(
                'title' => "lowercase words",
                'description' => "If you prefer lower case URLs, you can set this to YES. This will not affect the way URLs are stored in the database so you can go back to the original case letters any time. Please note that if you set this to YES, you will also have to make sure that your forum URL, as well as Google SEO prefixes, postfixes, and uniqufier are all lowercase too for the URL to be completely in lower case.",
                ),
            'google_seo_url_length_soft' => array(
                'title' => "URL length soft limit",
                'description' => "If an URL becomes too long, it can be shortened after a soft limit by truncating it after a word (punctuation separator). Set to 0 to disable.",
                'optionscode' => "text",
                'value' => '0',
                ),
            'google_seo_url_length_hard' => array(
                'title' => "URL length hard limit",
                'description' => "If an URL becomes too long, it can be shortened after a hard limit by truncating it regardless of word separators. Set to 0 to disable.",
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
