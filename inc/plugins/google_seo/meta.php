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

/* --- Hooks: --- */

global $settings;

$plugins->add_hook("forumdisplay_end", "google_seo_meta_forum");
$plugins->add_hook("postbit", "google_seo_meta_thread");
$plugins->add_hook("member_profile_end", "google_seo_meta_user");
$plugins->add_hook("postbit_announcement", "google_seo_meta_announcement");
$plugins->add_hook("calendar_event_end", "google_seo_meta_event");
$plugins->add_hook("calendar_end", "google_seo_meta_calendar");

if(defined("IN_ARCHIVE") && $settings['google_seo_meta_archive'])
{
    $plugins->add_hook("archive_start", "google_seo_meta_archive_start");
    $plugins->add_hook("archive_end", "google_seo_meta_archive_end");
    $plugins->add_hook("archive_announcement_end", "google_seo_meta_archive");
    $plugins->add_hook("archive_thread_post", "google_seo_meta_archive");
    $plugins->add_hook("archive_forum_end", "google_seo_meta_archive");
}

/* --- Functions: --- */

/**
 * Clean up a description and append it to headerinclude.
 *
 * @param string The unfiltered description that should be used.
 */
function google_seo_meta_description($description)
{
    global $settings, $headerinclude;

    if($settings['google_seo_meta_length'] > 0)
    {
        $description = strip_tags($description);
        $description = str_replace("&nbsp;", " ", $description);
        $description = preg_replace("/\\[[^\\]]+\\]/u", "", $description);
        $description = preg_replace("/\\s+/u", " ", $description);
        $description = trim($description);
        $description = my_substr($description, 0, $settings['google_seo_meta_length'], true);
        $description = trim($description);

        if($description)
        {
            $headerinclude = "<meta name=\"description\" content=\"{$description}\" />\n{$headerinclude}";
        }
    }
}

/**
 * Append a canonical link to headerinclude.
 *
 * @param string The link that is canonical for this page.
 */
function google_seo_meta_canonical($link)
{
    global $settings, $headerinclude;

    if($link)
    {
        $headerinclude = "<link rel=\"canonical\" href=\"{$settings['bburl']}/$link\" />\n{$headerinclude}";
    }
}

/**
 * Generate meta tags for a forum.
 *
 * @param int Forum-ID
 */
function google_seo_meta_forum()
{
    global $settings, $foruminfo, $fid, $page;

    // Canonical:
    if($settings['google_seo_meta_canonical'] && $fid > 0)
    {
        if($page > 1)
        {
            google_seo_meta_canonical(get_forum_link($fid, $page));
        }

        else
        {
            google_seo_meta_canonical(get_forum_link($fid));
        }
    }

    // Description:
    if($foruminfo)
    {
        google_seo_meta_description($foruminfo['description']);
    }
}

/**
 * Generate meta tags for a thread.
 *
 * @param post
 */
function google_seo_meta_thread($post)
{
    global $settings, $plugins, $tid, $page;

    // We're only interested in the first post of a page.
    $plugins->remove_hook("postbit", "google_seo_meta_thread");

    // Canonical:
    if($settings['google_seo_meta_canonical'] && $tid > 0)
    {
        if($page > 1)
        {
            google_seo_meta_canonical(get_thread_link($tid, $page));
        }

        else
        {
            google_seo_meta_canonical(get_thread_link($tid));
        }
    }

    // Description:
    if($post)
    {
        google_seo_meta_description($post['message']);
    }
}

/**
 * Generate meta tags for a user.
 *
 * @param int User-ID
 */
function google_seo_meta_user()
{
    global $settings, $uid;

    // Canonical:
    if($settings['google_seo_meta_canonical'] && $uid > 0)
    {
        google_seo_meta_canonical(get_profile_link($uid));
    }

    // Description:
    // not implemented yet
}

/**
 * Generate meta tags for an announcement.
 *
 * @param int Announcement-ID
 */
function google_seo_meta_announcement($post)
{
    global $settings, $aid;

    // Canonical:
    if($settings['google_seo_meta_canonical'] && $aid > 0)
    {
        google_seo_meta_canonical(get_announcement_link($aid));
    }

    // Description:
    if($post)
    {
        google_seo_meta_description($post['message']);
    }
}

/**
 * Generate meta tags for an event.
 *
 * @param int Event-ID
 */
function google_seo_meta_event($eid)
{
    global $settings, $event;

    // Canonical:
    if($settings['google_seo_meta_canonical'] && $event['eid'] > 0)
    {
        google_seo_meta_canonical(get_event_link($event['eid']));
    }

    // Description:
    google_seo_meta_description($event['description']);
}

/**
 * Generate meta tags for a calendar
 *
 * @param int Calendar-ID
 */
function google_seo_meta_calendar()
{
    global $settings, $calendar;

    // Canonical:
    if($settings['google_seo_meta_canonical'] && $calendar['cid'] > 0)
    {
        google_seo_meta_canonical(get_calendar_link($calendar['cid']));
    }

    // Description:
    // not implemented yet
}

/* --- Lite (Archive) Mode --- */

/**
 * Build description and canonical for the archive pages.
 *
 */
function google_seo_meta_archive()
{
    global $plugins, $action;

    switch($action)
    {
        case "announcement":
            global $announcement, $aid;
            $aid = $announcement['aid'];
            google_seo_meta_announcement($announcement);
            break;

        case "thread":
            global $post, $tid;
            $plugins->remove_hook("archive_thread_post",
                                  "google_seo_meta_archive");
            $tid = $post['tid'];
            google_seo_meta_thread($post);
            break;

        case "forum":
            global $forum, $foruminfo, $fid;
            $fid = $forum['fid'];
            $foruminfo = $forum;
            google_seo_meta_forum();
            break;
    }
}

/**
 * Catch the hard coded output using PHP output buffer control.
 * This hack is necessary because MyBB's Lite (Archive) Mode is hard coded.
 * Thanks to Michael S. for this idea.
 */
function google_seo_meta_archive_start()
{
    ob_start(NULL, 0);
}

/**
 * Add the tags to the output we caught and output it.
 */
function google_seo_meta_archive_end()
{
    global $headerinclude;

    $output = ob_get_contents();
    ob_end_clean();
    $output = str_replace("</head>", "{$headerinclude}</head>", $output);
    echo $output;
}

/* --- End of file. --- */
?>
