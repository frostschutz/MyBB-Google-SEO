<?php
/**
 * Copyright (C) 2008 Andreas Klauer <Andreas.Klauer@metamorpher.de>
 *
 * This file is Google SEO, a MyBB plugin.
 *
 * Google SEO is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * Google SEO is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Google SEO.  If not, see <http://www.gnu.org/licenses/>.
 *
 */

/*
 * BETA SOFTWARE
 *
 * This plugin is still in beta stage. This means it can cause all sorts
 * of problems like high server load, stability problems, and data loss.
 *
 * You are welcome to test this plugin and report any bugs and issues.
 * It is NOT RECOMMENDED to use this plugin in a production forum.
 *
 * INSTALLATION INSTRUCTIONS
 *
 * This plugin requires Apache server with mod_rewrite and mod_env.
 *
 * Upload this file to inc/plugins/google_seo.php and activate the plugin.
 * Do this before making any of the following changes.
 *
 * You will then have to modify some files for the plugin to do any work:
 *
 * .htaccess:
 *
 *
 *   If you haven't done so, rename the MyBB 'htaccess.txt' to '.htaccess'.
 *   This is required for any kind of SEO URL to work (MyBB built in SEO too).
 *
 *   Add the following to the mod_rewrite section:
 *
 *     # Google SEO start
 *     RewriteRule ^Forum-([^./]+)$ forumdisplay.php?google_seo_forum=$1 [L,QSA,NC]
 *     RewriteRule ^Thread-([^./]+)$ showthread.php?google_seo_thread=$1 [L,QSA,NC]
 *     RewriteRule ^Announcement-([^./]+)$ announcements.php?google_seo_announcement=$1 [L,QSA,NC]
 *     RewriteRule ^User-([^./]+)$ member.php?google_seo_user=$1 [L,QSA,NC]
 *     RewriteRule ^Calendar-([^./]+)$ calendar.php?google_seo_calendar=$1 [L,QSA,NC]
 *     RewriteRule ^Event-([^./]+)$ calendar.php?google_seo_event=$1 [L,QSA,NC]
 *     # If you need additional rules due to custom settings, use this as a model:
 *     # RewriteRule ^{$prefix}([^./]+){$postfix}$ page.php?google_seo_type=$1 [L,QSA,NC]
 *     # Google SEO end
 *
 * inc/functions.php:
 *
 *   Add a mybb_ prefix to the following function declarations:
 *
 *     function get_profile_link($uid=0)
 *     function get_announcement_link($aid=0)
 *     function get_forum_link($fid, $page=0)
 *     function get_thread_link($tid, $page=0, $action='')
 *     function get_post_link($pid, $tid=0)
 *     function get_event_link($eid)
 *     function get_calendar_link($calendar, $year=0, $month=0, $day=0)
 *     function get_calendar_week_link($calendar, $week)
 *
 *   Afterwards the function declarations should look like this:
 *
 *     function mybb_get_profile_link($uid=0)
 *     function mybb_get_announcement_link($aid=0)
 *     function mybb_get_forum_link($fid, $page=0)
 *     function mybb_get_thread_link($tid, $page=0, $action='')
 *     function mybb_get_post_link($pid, $tid=0)
 *     function mybb_get_event_link($eid)
 *     function mybb_get_calendar_link($calendar, $year=0, $month=0, $day=0)
 *     function mybb_get_calendar_week_link($calendar, $week)
 *
 *   On a Linux system, you can use this command to change the file:
 *
 *     sed -i -r -e 's/function get_(.*)_link/function mybb_get_\1_link/' inc/functions.php
 *
 * UNINSTALL INSTRUCTIONS
 *
 * There are two ways to uninstall Google SEO.
 *
 * 1) Keep the changes, only deactivate Google SEO URLs in the settings.
 *    This way you will be back to MyBB stock URLs and Google SEO will
 *    redirect old Google SEO URLs back to stock URLs.
 *
 *    While technically the plugin stays installed, this is recommended
 *    as it gives users and search engines time to adapt to the change.
 *
 * 2) Undo the changes by reuploading .htaccess and inc/functions.php.
 *    Then go to Admin CP -> Plugins and click 'Deactivate'.
 *    This way the Google SEO URLs will end up in 404 error land.
 *
 *    If you click 'Uninstall' all data regarding Google SEO URLs will
 *    be lost, so you don't get (all of) your old Google SEO URLs back
 *    when you reinstall.
 *
 */

// Disallow direct access to this file for security reasons
if(!defined("IN_MYBB"))
{
    die("Direct initialization of this file is not allowed.<br /><br />
         Please make sure IN_MYBB is defined.");
}

/* --- Hooks: --- */

// We have to use global_start, because not every subpage
// has a start hook we can use (member.php, calendar.php, ...).
// And anyway, better to set parameters correctly or redirect asap.
$plugins->add_hook("global_start", "mybb_seo_global_start");

/* --- Cache: --- */

// Non-persistant cache greatly reduces number of database queries.
global $google_seo_cache;

if(!$google_seo_cache)
{
    $google_seo_cache = array(
        "forums" => array(),
        "threads" => array(),
        "announcements" => array(),
        "users" => array(),
        "calendars" => array(),
        "events" => array()
    );
}

/* --- Module initialization: --- */

// The information that shows up on the plugin manager
function google_seo_info()
{
    return array(
        "name"          => "Google SEO",
        "description"   => "Search engine optimization as described in Google's SEO starter guide.",
        "author"        => "Andreas Klauer",
        "authorsite"    => "mailto:Andreas.Klauer@metamorpher.de",
        "version"       => "0.1",
    );
}

// This function runs when the plugin is activated.
function google_seo_activate()
{
    global $db;

    // Create settings group if it does not exist.
    $query = $db->query("SELECT gid FROM ".TABLE_PREFIX."settinggroups
                         WHERE name='google_seo'");

    if($db->num_rows($query))
    {
        // It exists, get the gid.
        // There ought to be a way to do this in one line...
        $gid = $db->fetch_array($query);
        $gid = $gid['gid'];
    }

    else
    {
        // It does not exist, create it and get the gid.
        $db->insert_query("settinggroups",
                          array("gid" => "NULL",
                                "name" => "google_seo",
                                "title" => "Google SEO",
                                "description" => "Google Search Engine Optimization plugin settings",
                                "disporder" => "100",
                                "isdefault" => "no"));
        $gid = $db->insert_id();
    }

    // Create URL setting if it does not exist.
    $query = $db->query("SELECT sid FROM ".TABLE_PREFIX."settings
                         WHERE gid='$gid'
                         AND name='google_seo_url'");

    if($db->num_rows($query) == 0)
    {
        // It does not exist, create it.
        $db->insert_query("settings",
                          array("sid" => "NULL",
                                "name" => "google_seo_url",
                                "title" => "Enable Google SEO URLs",
                                "description" => "When set to YES, Google SEO URLs will be used instead of the stock MyBB URLs. When set to NO, MyBB stock URLs will be used, but old links to Google SEO URLs will still be understood unless you disable the plugin completely.",
                                "optionscode" => "yesno",
                                "value" => 0,
                                "disporder" => 1,
                                "gid" => $gid));
    }

    // Create redirect setting if it does not exist.
    $query = $db->query("SELECT sid FROM ".TABLE_PREFIX."settings
                         WHERE gid='$gid'
                         AND name='google_seo_redirect'");

    if($db->num_rows($query) == 0)
    {
        // It does not exist, create it.
        $db->insert_query("settings",
                          array("sid" => "NULL",
                                "name" => "google_seo_redirect",
                                "title" => "URL redirection",
                                "description" => "When set to YES, redirect to the current valid URL. This is used to redirect stock MyBB URLs to Google SEO URLs, or the other way around when Google SEO URLs are disabled, as well as redirect users that use wrong upper or lower case letters. Do not turn this off unless it causes problems (which can only happen if something else redirects too, which is something you should fix), as it prevents Google from seeing the same page under several different names.",
                                "optionscode" => "yesno",
                                "value" => 1,
                                "disporder" => 2,
                                "gid" => $gid));
    }

    // Create verify setting if it does not exist.
    $query = $db->query("SELECT sid FROM ".TABLE_PREFIX."settings
                         WHERE gid='$gid'
                         AND name='google_seo_verify'");

    if($db->num_rows($query) == 0)
    {
        // It does not exist, create it.
        $db->insert_query("settings",
                          array("sid" => "NULL",
                                "name" => "google_seo_verify",
                                "title" => "Always verify URLs",
                                "description" => "When set to YES, the Google SEO URLs are verified every time a link is made to them, so it catches name / title changes of forums, threads, users etc. as early as possible. When you set this to NO, lazy verification will be used instead (the URL will be updated once the page gets actually accessed). Verification will cost you several additional SQL queries per page view, and it\'s not really necessary as the next time Google crawls your page it will verify everything anyway. However if you\'re not concerned about load or your users complain about not up to date links, set this to YES.",
                                "optionscode" => "yesno",
                                "value" => 0,
                                "disporder" => 2,
                                "gid" => $gid));
    }

    // Create lowercase setting if it does not exist.
    $query = $db->query("SELECT sid FROM ".TABLE_PREFIX."settings
                         WHERE gid='$gid'
                         AND name='google_seo_lowercase'");

    if($db->num_rows($query) == 0)
    {
        // It does not exist, create it.
        $db->insert_query("settings",
                          array("sid" => "NULL",
                                "name" => "google_seo_lowercase",
                                "title" => "lowercase words",
                                "description" => "Google SEO URLs are case insensitive (the user gets redirected to the correct page when he confuses upper and lower case), so it\'s fine to keep the original uppercase letters (as they make a difference in many languages). If however for some reason you prefer lower case URLs, you can set this to YES. This will not affect the way URLs are stored in the database so you can go back to the original case letters any time. Please note that if you set this to YES, you will also have to make sure that your forum URL, as well as Google SEO prefixes, postfixes, and stinky fish are all lowercase too.",
                                "optionscode" => "yesno",
                                "value" => 0,
                                "disporder" => 3,
                                "gid" => $gid));
    }

    // Create separator setting.
    $query = $db->query("SELECT sid FROM ".TABLE_PREFIX."settings
                         WHERE gid='$gid'
                         AND name='google_seo_separator'");

    if($db->num_rows($query) == 0)
    {
        // It does not exist, create it.
        $db->insert_query("settings",
                          array("sid" => "NULL",
                                "name" => "google_seo_separator",
                                "title" => "URL separator",
                                "description" => "Enter the separator that should be used to separate words in the URLs. By default this is <i>-</i> which is a good choice as it is easy to type in most keyboard layouts (single keypress without shift/alt modifier). If you want some other character or string as a separator, you can enter it here. Please note that special characters like : or @ or / or space could render your URLs unuseable or hard to work with.",
                                "optionscode" => "text",
                                "value" => "-",
                                "disporder" => 4,
                                "gid" => $gid));
    }

    // Create punctuation setting.
    $query = $db->query("SELECT sid FROM ".TABLE_PREFIX."settings
                         WHERE gid=$gid AND name='google_seo_punctuation'");

    if($db->num_rows($query) == 0)
    {
        // It does not exist, create it.
        $db->insert_query("settings",
                          array("sid" => "NULL",
                                "name" => "google_seo_punctuation",
                                "title" => "Punctuation characters",
                                "description" => "Punctuation and other special characters are filtered from the URL string and replaced by the separator. By default, this string contains all special ASCII characters <i>!&quot;#\$%&amp;\\'(&nbsp;)*+,-./:;&lt;=&gt;?@[\\\\]^_\\`{|}~</i> (including space). If you are running an international forum with non-ascii script, you should may want to add characters of those scripts here, for example Japanese <i>。、「　」：？！</i>.",
                                "optionscode" => "text",
                                "value" => "!\"#\$%&\\'( )*+,-./:;<=>?@[\\\\]^_\\`{|}~",
                                "disporder" => 5,
                                "gid" => $gid));
    }


    // Create forum prefix setting if it does not exist.
    $query = $db->query("SELECT sid FROM ".TABLE_PREFIX."settings
                         WHERE gid='$gid'
                         AND name='google_seo_prefix_forum'");

    if($db->num_rows($query) == 0)
    {
        // It does not exist, create it.
        $db->insert_query("settings",
                          array("sid" => "NULL",
                                "name" => "google_seo_prefix_forum",
                                "title" => "Forum URL prefix",
                                "description" => "Enter the prefix that should be used for Forum URLs. By default this is <i>Forum-</i>. Please note that if you change this, you will also need to add a new rewrite rule in your .htaccess file.",
                                "optionscode" => "text",
                                "value" => "Forum-",
                                "disporder" => 6,
                                "gid" => $gid));
    }

    // Create thread prefix setting if it does not exist.
    $query = $db->query("SELECT sid FROM ".TABLE_PREFIX."settings
                         WHERE gid='$gid'
                         AND name='google_seo_prefix_thread'");

    if($db->num_rows($query) == 0)
    {
        // It does not exist, create it.
        $db->insert_query("settings",
                          array("sid" => "NULL",
                                "name" => "google_seo_prefix_thread",
                                "title" => "Thread URL prefix",
                                "description" => "Enter the prefix that should be used for Thread URLs. By default this is <i>Thread-</i>. Please note that if you change this, you will also need to add a new rewrite rule in your .htaccess file.",
                                "optionscode" => "text",
                                "value" => "Thread-",
                                "disporder" => 7,
                                "gid" => $gid));
    }

    // Create announcement prefix setting if it does not exist.
    $query = $db->query("SELECT sid FROM ".TABLE_PREFIX."settings
                         WHERE gid='$gid'
                         AND name='google_seo_prefix_announcement'");

    if($db->num_rows($query) == 0)
    {
        // It does not exist, create it.
        $db->insert_query("settings",
                          array("sid" => "NULL",
                                "name" => "google_seo_prefix_announcement",
                                "title" => "Announcement URL prefix",
                                "description" => "Enter the prefix that should be used for Announcement URLs. By default this is <i>Announcement-</i>. Please note that if you change this, you will also need to add a new rewrite rule in your .htaccess file.",
                                "optionscode" => "text",
                                "value" => "Announcement-",
                                "disporder" => 8,
                                "gid" => $gid));
    }

    // Create user prefix setting if it does not exist.
    $query = $db->query("SELECT sid FROM ".TABLE_PREFIX."settings WHERE gid=$gid AND name='google_seo_prefix_user'");

    if($db->num_rows($query) == 0)
    {
        // It does not exist, create it.
        $db->insert_query("settings",
                          array("sid" => "NULL",
                                "name" => "google_seo_prefix_user",
                                "title" => "User URL prefix",
                                "description" => "Enter the prefix that should be used for User URLs. By default this is <i>User-</i>. Please note that if you change this, you will also need to add a new rewrite rule in your .htaccess file.",
                                "optionscode" => "text",
                                "value" => "User-",
                                "disporder" => 9,
                                "gid" => $gid));
    }

    // Create calendar prefix setting if it does not exist.
    $query = $db->query("SELECT sid FROM ".TABLE_PREFIX."settings WHERE gid=$gid AND name='google_seo_prefix_calendar'");

    if($db->num_rows($query) == 0)
    {
        // It does not exist, create it.
        $db->insert_query("settings",
                          array("sid" => "NULL",
                                "name" => "google_seo_prefix_calendar",
                                "title" => "Calendar URL prefix",
                                "description" => "Enter the prefix that should be used for Calendar URLs. By default this is <i>Calendar-</i>. Please note that if you change this, you will also need to add a new rewrite rule in your .htaccess file.",
                                "optionscode" => "text",
                                "value" => "Calendar-",
                                "disporder" => 10,
                                "gid" => $gid));
    }

    // Create event prefix setting if it does not exist.
    $query = $db->query("SELECT sid FROM ".TABLE_PREFIX."settings
                         WHERE gid='$gid'
                         AND name='google_seo_prefix_event'");

    if($db->num_rows($query) == 0)
    {
        // It does not exist, create it.
        $db->insert_query("settings",
                          array("sid" => "NULL",
                                "name" => "google_seo_prefix_event",
                                "title" => "Event URL prefix",
                                "description" => "Enter the prefix that should be used for Event URLs. By default this is <i>Event-</i>. Please note that if you change this, you will also need to add a new rewrite rule in your .htaccess file.",
                                "optionscode" => "text",
                                "value" => "Event-",
                                "disporder" => 11,
                                "gid" => $gid));
    }

    // Create postfix setting.
    $query = $db->query("SELECT sid FROM ".TABLE_PREFIX."settings
                         WHERE gid='$gid'
                         AND name='google_seo_postfix'");

    if($db->num_rows($query) == 0)
    {
        // It does not exist, create it.
        $db->insert_query("settings",
                          array("sid" => "NULL",
                                "name" => "google_seo_postfix",
                                "title" => "URL postfix",
                                "description" => "Enter the postfix that should be used for all URLs. By default this is empty. If you absolutely want your URLs to end with .html, you could put <i>.html</i> here. However, this will clash with stock MyBB SEO URLs and you will also need to add new rewrite rules in your .htaccess file.",
                                "optionscode" => "text",
                                "value" => "",
                                "disporder" => 12,
                                "gid" => $gid));
    }

    // Create stinky fish setting.
    $query = $db->query("SELECT sid FROM ".TABLE_PREFIX."settings
                         WHERE gid='$gid'
                         AND name='google_seo_stinky_fish'");

    if($db->num_rows($query) == 0)
    {
        // It does not exist, create it.
        $db->insert_query("settings",
                          array("sid" => "NULL",
                                "name" => "google_seo_stinky_fish",
                                "title" => "Stinky Fish",
                                "description" => "Google SEO tries to make URLs that do not contain hard to remember ID numbers. However at the same time, URLs <i>must be unique</i>. For the case where the URL cannot be unique (such as two forum threads with the same title) or would be empty (user name that is made up of punctuation only), the URL has to be forced unique. In that case, a <i>stinky fish</i> string is appended to make the URL unique. By default this is url-(id). The string has to contain a punctuation character that could otherwise not be in the URL (to differentiate between \'Hello\' with id 1234 and a thread that is actually named \'Hello-1234\'), as well as the id itself. You can put in any PHP code you like here, as long as it gives a unique string that does not break your .htacess rewrite rules.",
                                "optionscode" => "text",
                                "value" => '"{$url}-({$id})"',
                                "disporder" => 13,
                                "gid" => $gid));
    }

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
    if(!$db->table_exists("google_seo_forums"))
    {
        $db->write_query("CREATE TABLE ".TABLE_PREFIX."google_seo_forums(
                              rowid int unsigned NOT NULL auto_increment,
                              fid int unsigned NOT NULL,
                              url varchar(120) UNIQUE NOT NULL,
                              PRIMARY KEY(rowid)
                          )");
    }

    if(!$db->table_exists("google_seo_threads"))
    {
        $db->write_query("CREATE TABLE ".TABLE_PREFIX."google_seo_threads(
                              rowid int unsigned NOT NULL auto_increment,
                              tid int unsigned NOT NULL,
                              url varchar(120) UNIQUE NOT NULL,
                              PRIMARY KEY(rowid)
                          )");
    }

    if(!$db->table_exists("google_seo_announcements"))
    {
        $db->write_query("CREATE TABLE ".TABLE_PREFIX."google_seo_announcements(
                              rowid int unsigned NOT NULL auto_increment,
                              aid int unsigned NOT NULL,
                              url varchar(120) UNIQUE NOT NULL,
                              PRIMARY KEY(rowid)
                          )");
    }

    if(!$db->table_exists("google_seo_users"))
    {
        $db->write_query("CREATE TABLE ".TABLE_PREFIX."google_seo_users(
                              rowid int unsigned NOT NULL auto_increment,
                              uid int unsigned NOT NULL,
                              url varchar(120) UNIQUE NOT NULL,
                              PRIMARY KEY(rowid)
                          )");
    }

    if(!$db->table_exists("google_seo_calendars"))
    {
        $db->write_query("CREATE TABLE ".TABLE_PREFIX."google_seo_calendars(
                              rowid int unsigned NOT NULL auto_increment,
                              cid int unsigned NOT NULL,
                              url varchar(120) UNIQUE NOT NULL,
                              PRIMARY KEY(rowid)
                          )");
    }

    if(!$db->table_exists("google_seo_events"))
    {
        $db->write_query("CREATE TABLE ".TABLE_PREFIX."google_seo_events(
                              rowid int unsigned NOT NULL auto_increment,
                              eid int unsigned NOT NULL,
                              url varchar(120) UNIQUE NOT NULL,
                              PRIMARY KEY(rowid)
                          )");
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
    if($db->table_exists("google_seo_forums"))
    {
        $db->write_query("DROP TABLE ".TABLE_PREFIX."google_seo_forums");
    }

    if($db->table_exists("google_seo_threads"))
    {
        $db->write_query("DROP TABLE ".TABLE_PREFIX."google_seo_threads");
    }

    if($db->table_exists("google_seo_announcements"))
    {
        $db->write_query("DROP TABLE ".TABLE_PREFIX."google_seo_announcements");
    }

    if($db->table_exists("google_seo_users"))
    {
        $db->write_query("DROP TABLE ".TABLE_PREFIX."google_seo_users");
    }

    if($db->table_exists("google_seo_calendars"))
    {
        $db->write_query("DROP TABLE ".TABLE_PREFIX."google_seo_calendars");
    }

    if($db->table_exists("google_seo_events"))
    {
        $db->write_query("DROP TABLE ".TABLE_PREFIX."google_seo_events");
    }

    // Remove the Google SEO setting group.
    $query = $db->query("SELECT gid FROM ".TABLE_PREFIX."settinggroups WHERE name='google_seo'");

    if($db->num_rows($query))
    {
        $gid = $db->fetch_array($query);
        $gid = $gid['gid'];

        $db->delete_query("settinggroups", "gid='$gid'");
        $db->delete_query("settings", "gid='$gid'");

        rebuild_settings();
    }
}

/* --- Update/Create URLs: --- */

// Separate a string by punctuation for use in URLs.
function google_seo_separate($str)
{
    global $settings;

    $pattern = $settings['google_seo_punctuation'];

    if($pattern)
    {
        $pattern = preg_replace("/[\\\\\\^\\-\\[\\]\\/]/u",
                                "\\\\\\0",
                                $pattern);
    }

    // Cut off punctuation at beginning and end.
    $str = preg_replace("/^[".$pattern."]+|[".$pattern."]+$/u",
                        "",
                        $str);

    // Replace middle punctuation with one separator.
    $str = preg_replace("/[".$pattern."]+/u",
                        $settings['google_seo_separator'],
                        $str);

    return $str;
}

// Update an URL database entry in a unique way.
// This returns the new unique url.
function google_seo_unique($tablename, $idname, $id, $oldurl, $url)
{
    global $db, $settings, $google_seo_cache;

    if($oldurl && $oldurl == $url)
    {
        // No update required.
        return $url;
    }

    // Update required. Unique check.
    $query = $db->query("SELECT rowid, $idname
                         FROM ".TABLE_PREFIX."google_seo_$tablename
                         WHERE url='".$db->escape_string($url)."'
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
        $rowid = $db->fetch_array($query);
        $rowid = $rowid['rowid'];

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
                // Flush his URLs out.
                $db->delete_query("google_seo_$tablename
                                   WHERE $idname='$collid'");
                $collision = 0;
            }
        }
    }

    // Unresolved collision calls for some stinky fish action.
    if(!$url || $collision)
    {
        eval("\$url=".$settings['google_seo_stinky_fish'].";");

        // Special case: the old URL had a stinky fish too.
        if($oldurl == $url)
        {
            // It stiiinks! No update required after all.
            return $url;
        }
    }

    // Delete is necessary in case something was renamed and renamed back.
    $db->delete_query("google_seo_$tablename
                       WHERE url='".$db->escape_string($url)."'");

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
    $url = $db->fetch_array($query);
    $url = $url['url'];

    if(!$url || $verify)
    {
        // Make or verify the URL.
        $query = $db->query("SELECT $titlename FROM ".TABLE_PREFIX."$tablename
                             WHERE $idname='$id'
                             LIMIT 1");
        $title = $db->fetch_array($query);
        $title = $title[$titlename];
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
            $post = $db->fetch_array($query);
            $tid = $post['tid'];
        }
    }

    $url = google_seo_update("threads", "tid", "subject",
                             $tid, $settings['google_seo_verify']);

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

    $id = $db->fetch_array($query);
    $id = $id[$idname];

    if(!$id)
    {
        // Something went wrong. Maybe user added some punctuation?
        $url = google_seo_separate($url);

        $query = $db->query("SELECT $idname
                             FROM ".TABLE_PREFIX."google_seo_$tablename
                             WHERE url='".$db->escape_string($url)."'
                             ORDER BY rowid DESC
                             LIMIT 1");

        $id = $db->fetch_array($query);
        $id = $id[$idname];
    }

    return $id;
}

// Look up pages, verify and redirect if necessary.
function mybb_seo_global_start()
{
    global $db, $settings, $mybb;

    // Translate URL name to ID and verify.
    switch(THIS_SCRIPT)
    {
        case 'forumdisplay.php':
            $url = $mybb->input['google_seo_forum'];

            if($url)
            {
                $fid = google_seo_url_id("forums", "fid", $url);
                $mybb->input['fid'] = $fid;
            }

            // Verification.
            $fid = $mybb->input['fid'];

            if($fid && $settings['google_seo_url'])
            {
                google_seo_update("forums", "fid", "name",
                                  $fid, 1);
            }

            break;

        case 'showthread.php':
            $url = $mybb->input['google_seo_thread'];

            if($url)
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

            break;

        case 'announcement.php':
            $url = $mybb->input['google_seo_announcement'];

            if($url)
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
            $url = $mybb->input['google_seo_user'];

            if($url)
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
            $url = $mybb->input['google_seo_event'];

            if($url)
            {
                $eid = google_seo_url_id("events", "eid", $url);
                $mybb->input['eid'] = $eid;
            }

            // Verification.
            $eid = $mybb->input['eid'];

            if($eid && $settings['google_seo_url'])
            {
                google_seo_update("events", "eid", "name",
                                  $eid, 1);
            }

            if(!$url && !$eid)
            {
                $url = $mybb->input['google_seo_calendar'];

                if($url)
                {
                    $cid = google_seo_url_id("calendars", "cid", $url);
                    $mybb->input['calendar'] = $cid;
                }

                // Verification.
                $cid = $mybb->input['calendar'];

                if($cid && $settings['google_seo_url'])
                {
                    google_seo_update("calendars", "cid",
                                      "name", $cid, 1);
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

        else if($tid)
        {
            $target = get_thread_link($tid, $mybb->input['page'], $mybb->input['action']);
        }

        else if($aid)
        {
            $target = get_announcement_link($aid);
        }

        else if($uid)
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
        $target = $settings['bburl'].'/'.$target;

        $parse = parse_url(urldecode($target));

        if(!$parse['port'])
        {
            $parse['port'] = ($parse['scheme'] == "https" ? 443 : 80);
        }

        // PHP is stupid.
        $server_request_uri = split('\\?', $_SERVER['REQUEST_URI'], 2);

        $server = array(
            "scheme" => ($_SERVER['HTTPS'] == "on" ? "https" : "http"),
            "host" => urldecode($_SERVER['HTTP_HOST']
                                ? $_SERVER['HTTP_HOST']
                                : $_SERVER['SERVER_NAME']),
            "port" => $_SERVER['SERVER_PORT'],
            "path" => urldecode($server_request_uri[0])
        );

        // Redirect when the URLs don't match.
        // This is dangerous so we prefer to be extra careful.
        // Don't redirect if not quite sure about the location on both sides.
        if(($server['scheme'] && $parse['scheme'] && $server['scheme'] != $parse['scheme'])
           || ($server['host'] && $parse['host'] && $server['host'] != $parse['host'])
           || ($server['port'] && $parse['port'] && $server['port'] != $parse['port'])
           || ($server['path'] && $parse['path'] && $server['path'] != $parse['path']))
        {
            // Redirect but retain query.
            $query = array();
            $querystr = array();

            if($server_request_uri[1])
            {
                parse_str($server_request_uri[1], &$query);
            }

            if($parse['query'])
            {
                parse_str($parse['query'], &$query);
            }

            foreach($query as $k=>$v)
            {
                $querystr[] = "$k=$v";
            }

            if(sizeof($querystr))
            {
                $request_uri = split('\\?', $target, 2);
                $target = $request_uri[0] . '?' . implode("&", $querystr);
            }

            header("Location: $target");
            exit;
        }
    }
}

/* --- End of file. --- */
?>