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

/* --- Globals: --- */

global $settings, $google_seo_nofollow, $google_seo_meta;

// Nofollow datetime
if($settings['google_seo_meta_nofollow'])
{
    $google_seo_nofollow = (float)$settings['google_seo_meta_nofollow'];

    if($google_seo_nofollow > 0)
    {
        $google_seo_nofollow = TIME_NOW - $google_seo_nofollow*24*60*60;
    }
}

/* --- Hooks: --- */

// Generic Hooks
$plugins->add_hook("forumdisplay_end", "google_seo_meta_forum");
$plugins->add_hook("postbit", "google_seo_meta_thread");
$plugins->add_hook("member_profile_end", "google_seo_meta_user");
$plugins->add_hook("postbit_announcement", "google_seo_meta_announcement");
$plugins->add_hook("calendar_event_end", "google_seo_meta_event");
$plugins->add_hook("calendar_end", "google_seo_meta_calendar");
$plugins->add_hook("printthread_post", "google_seo_meta_printthread");

// Archive Hooks
if(defined("IN_ARCHIVE") && $settings['google_seo_meta_archive'])
{
    $plugins->add_hook("archive_start", "google_seo_meta_archive_start");
    $plugins->add_hook("archive_announcement_end", "google_seo_meta_archive");
    $plugins->add_hook("archive_thread_post", "google_seo_meta_archive");
    $plugins->add_hook("archive_forum_end", "google_seo_meta_archive");
}

/* --- Functions: --- */

/**
 * Clean up a description and append it to google_seo_meta.
 *
 * @param string The unfiltered description that should be used.
 */
function google_seo_meta_description($description)
{
    global $settings, $plugins, $google_seo_meta;

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
            $plugins->add_hook('pre_output_page', 'google_seo_meta_output');
            $google_seo_meta = "<meta name=\"description\" content=\"{$description}\" />\n{$google_seo_meta}";
        }
    }
}

/**
 * Append a canonical link to google_seo_meta.
 *
 * @param string The link that is canonical for this page.
 */
function google_seo_meta_canonical($link)
{
    global $settings, $plugins, $google_seo_meta;

    if($link)
    {
        $plugins->add_hook('pre_output_page', 'google_seo_meta_output');
        $google_seo_meta = "<link rel=\"canonical\" href=\"{$settings['bburl']}/$link\" />\n{$google_seo_meta}";
    }
}

/**
 * Initialize a page variable
 */
function google_seo_meta_page($page)
{
    global $settings, $lang;
    global $google_seo_page;

    if($page > 1 && $settings['google_seo_meta_page'])
    {
        $google_seo_page = google_seo_expand(
            $settings['google_seo_meta_page'],
            array(
                'page' => $lang->googleseo_meta_page,
                'number' => (int)$page,
                )
            );
    }
}

/**
 * Make a page noindex.
 */
function google_seo_meta_noindex($fid)
{
    global $settings, $plugins, $google_seo_meta;

    if(strpos(",{$settings['google_seo_meta_noindex_fids']},", ",{$fid},"))
    {
        $plugins->add_hook('pre_output_page', 'google_seo_meta_output');
        $google_seo_meta = "<meta name=\"robots\" content=\"noindex\" />\n{$google_seo_meta}";
    }
}

/**
 * Make links in a string nofollow.
 */
function google_seo_meta_nofollow($text)
{
    if(is_string($text))
    {
        $text = str_replace('<a ',
                            '<a rel="nofollow" ',
                            $text);
    }

    return $text;
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

    // Page:
    google_seo_meta_page((int)$page);

    // Noindex:
    google_seo_meta_noindex($fid);
}

/**
 * Generate meta tags for a thread.
 *
 * @param post
 */
function google_seo_meta_thread(&$post)
{
    global $settings, $lang, $plugins, $tid, $page, $thread;
    global $google_seo_page, $google_seo_nofollow;

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

    // Page:
    google_seo_meta_page((int)$page);

    // Noindex:
    google_seo_meta_noindex($post['fid']);

    // Nofollow:
    if($google_seo_nofollow && $thread['lastpost'] > $google_seo_nofollow)
    {
        // We need to grab the remaining posts as well.
        $plugins->add_hook("postbit", "google_seo_meta_posts");

        if(defined("IN_ARCHIVE") && $settings['google_seo_meta_archive'])
        {
            $plugins->add_hook("archive_thread_post", "google_seo_meta_posts");
        }

        google_seo_meta_posts($post);
    }
}

/**
 * Nofollow links in posts.
 */
function google_seo_meta_posts(&$mypost)
{
    global $post;
    global $google_seo_nofollow;

    // Archive Post hook doesn't supply us with the post so we have to cheat.
    if(defined("IN_ARCHIVE") && !$mypost && $post)
    {
        $mypost = &$post;
    }

    if($mypost['dateline'] > $google_seo_nofollow)
    {
        $mypost['message'] = google_seo_meta_nofollow($mypost['message']);
        $mypost['button_www'] = google_seo_meta_nofollow($mypost['button_www']);
        $mypost['signature'] = google_seo_meta_nofollow($mypost['signature']);
    }
}

/**
 * Meta for printthread
 */
function google_seo_meta_printthread()
{
    global $plugins, $postrow, $thread;
    global $google_seo_nofollow;

    $plugins->remove_hook("printthread_post", "google_seo_meta_printthread");

    google_seo_meta_thread($postrow);

    // Nofollow:
    if($google_seo_nofollow && $thread['lastpost'] > $google_seo_nofollow)
    {
        $plugins->add_hook("printthread_post", "google_seo_meta_printthread_post");
        // google_seo_meta_posts already called by google_seo_meta_thread()
    }
}

function google_seo_meta_printthread_post()
{
    global $postrow;
    google_seo_meta_posts($postrow);
}

/**
 * Generate meta tags for a user.
 *
 * @param int User-ID
 */
function google_seo_meta_user()
{
    global $settings, $uid, $memprofile, $signature, $website;
    global $google_seo_nofollow;

    // Canonical:
    if($settings['google_seo_meta_canonical'] && $uid > 0)
    {
        google_seo_meta_canonical(get_profile_link($uid));
    }

    // Nofollow:
    if($memprofile['regdate'] > $google_seo_nofollow)
    {
        $signature = google_seo_meta_nofollow($signature);
        $website = google_seo_meta_nofollow($website);
    }
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
    ob_start('google_seo_meta_output');
}

/**
 * Add the tags to the output.
 */
function google_seo_meta_output($string)
{
    global $google_seo_meta;

    if($google_seo_meta)
    {
        $pos = strpos($string, '</head>');

        if($pos)
        {
            // Insert google_seo_meta at pos.
            $string = substr_replace($string, $google_seo_meta, $pos, 0);

            // Just in case.
            $google_seo_meta = '';
        }
    }

    return $string;
}

/* --- End of file. --- */
?>
