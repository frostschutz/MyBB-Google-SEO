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

$plugins->add_hook("global_start", "google_seo_redirect_hook", 2);

/* --- Redirect: --- */

/**
 * Obtain the current URL.
 *
 * @return current URL
 */
function google_seo_redirect_current_url()
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

    return $page_url;
}

/**
 * Redirect if necessary.
 *
 */
function google_seo_redirect_hook()
{
    global $db, $mybb, $settings;

    if($mybb->request_method == "post")
    {
        // Never touch posts.
        return;
    }

    // Build the target URL we should be at:
    switch(THIS_SCRIPT)
    {
        case 'forumdisplay.php':
            if($mybb->input['fid'])
            {
                $target = get_forum_link($mybb->input['fid'],
                                         $mybb->input['page']);
                $kill = array('fid' => '',
                              'page' => '');
            }

            break;

        case 'showthread.php':
            // pid overrules tid, so we must check pid first,
            // even at the cost of an additional query.
            if($mybb->input['pid'])
            {
                $target = get_post_link($mybb->input['pid']);
                $kill = array('pid' => '');
            }

            else if($mybb->input['tid'])
            {
                $target = get_thread_link($mybb->input['tid'],
                                          $mybb->input['page'],
                                          $mybb->input['action']);
                $kill = array('tid' => '',
                              'page' => '',
                              'action' => '');
            }

            break;

        case 'announcement.php':
            if($mybb->input['aid'])
            {
                $target = get_announcement_link($mybb->input['aid']);
                $kill = array('aid' => '');
            }

            break;

        case 'member.php':
            if($mybb->input['uid'])
            {
                $target = get_profile_link($mybb->input['uid']);
                $kill = array('uid' => '');

                if($mybb->input['action'] == 'profile')
                {
                    $kill['action'] = '';
                }
            }

            break;

        case 'calendar.php':
            if($mybb->input['eid'])
            {
                $target = get_event_link($mybb->input['eid']);
                $kill = array('eid' => '');

                if($mybb->input['action'] == 'event')
                {
                    $kill['action'] = '';
                }
            }

            else
            {
                if(!$mybb->input['calendar'])
                {
                    // Special case: Default calendar.
                    // Code taken from calendar.php
                    $query = $db->simple_select("calendars", "cid", "",
                                                array('order_by' => 'disporder',
                                                      'limit' => 1));
                    $cid = $db->fetch_field($query, "cid");
                    $mybb->input['calendar'] = $cid;
                }

                if($mybb->input['action'] == "weekview")
                {
                    $target = get_calendar_week_link($mybb->input['calendar'],
                                                     $mybb->input['week']);
                    $kill = array('calendar' => '',
                                  'week' => '',
                                  'action' => '');
                }

                else
                {
                    $target = get_calendar_link($mybb->input['calendar'],
                                                $mybb->input['year'],
                                                $mybb->input['month'],
                                                $mybb->input['day']);
                    $kill = array('calendar' => '',
                                  'year' => '',
                                  'month' => '',
                                  'day' => '');
                }
            }

            break;
    }

    // Verify that we are already at the target.
    if($target)
    {
        $target = html_entity_decode($settings['bburl'].'/'.$target);
        $current = html_entity_decode(google_seo_redirect_current_url());

        // Not identical (although it may only be the query string).
        if($target != $current)
        {
            // Parse current and target
            $target_parse = split("\\?", $target, 2);
            $current_parse = split("\\?", $current, 2);

            // Location
            $location_target = $target_parse[0];
            $location_current = $current_parse[0];

            // Query
            parse_str($target_parse[1], &$query_target);
            parse_str($current_parse[1], &$query_current);

            $query = $query_current;

            // Kill query string elements that already are part of the URL.
            foreach($kill as $k=>$v)
            {
                unset($query[$k]);
            }

            // Final query, current parameters retained
            $query = array_merge($query_target, $query);

            // Compare query.
            if(count($query) != count($query_current))
            {
                $change = 1;
            }

            else
            {
                foreach($query as $k=>$v)
                {
                    if($query_current[$k] != $v)
                    {
                        $change = 1;
                    }
                }
            }

            // Definitely not identical?
            if($change || $target_parse[0] != $current_parse[0])
            {
                // Redirect but retain query.
                foreach($query as $k=>$v)
                {
                    $querystr[] = "$k=".urlencode($v);
                }

                if(sizeof($querystr))
                {
                    $location_target .= "?" . implode("&", $querystr);
                }

                header("Location: $location_target", true, 301);
                exit;
            }
        }
    }
}

/* --- End of file. --- */
?>