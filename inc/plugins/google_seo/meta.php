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

/* --- Meta description: --- */

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

function google_seo_meta_forum($fid)
{
    global $db;

    $query = $db->simple_select("forums", "description", "fid=$fid");
    $description = $db->fetch_field($query, "description");
    google_seo_meta($description);
}

function google_seo_meta_post($pid)
{
    global $db;

    $query = $db->simple_select("posts", "message", "pid=$pid");
    $message = $db->fetch_field($query, "message");
    google_seo_meta($message);
}

function google_seo_meta_thread($tid)
{
    global $db;

    $query = $db->simple_select("threads", "firstpost", "tid=$tid");
    $firstpost = $db->fetch_field($query, "firstpost");
    google_seo_meta_post($firstpost);
}

function google_seo_meta_user($uid)
{
/**
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
/**
 * Hmmm, what to put here?
 *
 * Calendars don't have descriptions.
 *
 * Maybe the number of events? :-/
 *
 */
}

$plugins->add_hook("global_start", "google_seo_meta_global_start");

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
            if($mybb->input['pid'])
            {
                google_seo_meta_post($mybb->input['pid']);
            }

            else if($mybb->input['tid'])
            {
                google_seo_meta_thread($mybb->input['tid']);
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