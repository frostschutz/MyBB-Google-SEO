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

// generating and setting meta data:
$plugins->add_hook("global_start", "google_seo_meta_global_start");

/* --- Meta description: --- */

/**
 * Clean up a descriptiona nd set the global google_seo_meta variable,
 * which will then (hopefully) be included by the headertemplate.
 *
 * @param string The unfiltered description that should be used.
 */
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

        if($description)
        {
            $google_seo_meta .= "<meta name=\"description\""
                ." content=\"$description\" />";
        }
    }
}

/**
 * Generate a meta description for a forum.
 *
 * @param int Forum-ID
 */
function google_seo_meta_forum($fid)
{
    global $db;

    if($fid > 0)
    {
        $query = $db->simple_select("forums", "description", "fid=$fid");
        $description = $db->fetch_field($query, "description");
        google_seo_meta($description);
    }
}

/**
 * Generate a meta description for a post (usually firstpost).
 *
 * @param int Post-ID
 */
function google_seo_meta_post($pid)
{
    global $db;

    if($pid > 0)
    {
        $query = $db->simple_select("posts", "message", "pid=$pid");
        $message = $db->fetch_field($query, "message");
        google_seo_meta($message);
    }
}

/**
 * Generate a meta description for a thread.
 *
 * @param int Thread-ID
 */
function google_seo_meta_thread($tid)
{
    global $db;

    if($tid > 0)
    {
        $query = $db->simple_select("threads", "firstpost", "tid=$tid");
        $firstpost = $db->fetch_field($query, "firstpost");
        google_seo_meta_post($firstpost);
    }
}

/**
 * Generate a meta description for a user.
 *
 * @param int User-ID
 */
function google_seo_meta_user($uid)
{
    if($uid > 0)
    {
        /* not implemented */
    }
}

/**
 * Generate a meta description for an announcement.
 *
 * @param int Announcement-ID
 */
function google_seo_meta_announcement($aid)
{
    global $db;

    if($aid > 0)
    {
        $query = $db->simple_select("announcements", "message", "aid=$aid");
        $message = $db->fetch_field($query, "message");
        google_seo_meta($message);
    }
}

/**
 * Generate a meta description for an event.
 *
 * @param int Event-ID
 */
function google_seo_meta_event($eid)
{
    global $db;

    if($eid > 0)
    {
        $query = $db->simple_select("events", "description", "eid=$eid");
        $description = $db->fetch_field($query, "description");
        google_seo_meta($description);
    }
}

/**
 * Generate a meta description for a calendar
 *
 * @param int Calendar-ID
 */
function google_seo_meta_calendar($cid)
{
    if($cid > 0)
    {
        /* not implemented */
    }
}

/**
 * Generate and set meta data for the current page, if applicable.
 *
 */
function google_seo_meta_global_start()
{
    global $mybb, $google_seo_meta;

    switch(THIS_SCRIPT)
    {
        case 'forumdisplay.php':
            if($mybb->input['fid'])
            {
                google_seo_meta_forum($mybb->input['fid']);
            }

            break;

        case 'showthread.php':
            if($mybb->input['tid'])
            {
                google_seo_meta_thread($mybb->input['tid']);
            }

            else if($mybb->input['pid'])
            {
                google_seo_meta_post($mybb->input['pid']);
            }

            break;

        case 'announcement.php':
            if($mybb->input['aid'])
            {
                google_seo_meta_announcement($mybb->input['aid']);
            }

            break;

        case 'member.php':
            if($mybb->input['uid'] && $mybb->input['action'] == 'profile')
            {
                google_seo_meta_user($mybb->input['uid']);
            }

            break;

        case 'calendar.php':
            if($mybb->input['eid'])
            {
                google_seo_meta_event($mybb->input['eid']);
            }

            else if($mybb->input['cid'])
            {
                google_seo_meta_calendar($mybb->input['cid']);
            }

            break;
    }
}

/* --- End of file. --- */
?>