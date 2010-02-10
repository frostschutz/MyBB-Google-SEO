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

// Check current URL and redirect if necessary.
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
    global $db, $mybb, $settings, $plugins, $google_seo_redirect;

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
                $kill['fid'] = '';
                $kill['page'] = '';
                $kill['google_seo_forum'] = '';
            }

            break;

        case 'showthread.php':
            // pid overrules tid, so we must check pid first,
            // even at the cost of an additional query.
            if($mybb->input['pid'])
            {
                $target = get_post_link($mybb->input['pid']);
                $kill['pid'] = '';
                $kill['tid'] = '';
                $kill['google_seo_thread'] = '';
            }

            else if($mybb->input['tid'])
            {
                $target = get_thread_link($mybb->input['tid'],
                                          $mybb->input['page'],
                                          $mybb->input['action']);
                $kill['tid'] = '';
                $kill['page'] = '';
                $kill['action'] = '';
                $kill['google_seo_thread'] = '';
            }

            break;

        case 'announcement.php':
            if($mybb->input['aid'])
            {
                $target = get_announcement_link($mybb->input['aid']);
                $kill['aid'] = '';
                $kill['google_seo_announcement'] = '';
            }

            break;

        case 'member.php':
            if($mybb->input['uid'])
            {
                $target = get_profile_link($mybb->input['uid']);
                $kill['uid'] = '';
                $kill['google_seo_user'] = '';

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
                $kill['eid'] = '';

                if($mybb->input['action'] == 'event')
                {
                    $kill['action'] = '';
                    $kill['google_seo_event'] = '';
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
                    $kill['calendar'] = '';
                    $kill['week'] = '';
                    $kill['action'] = '';
                    $kill['google_seo_calendar'] = '';
                }

                else
                {
                    $target = get_calendar_link($mybb->input['calendar'],
                                                $mybb->input['year'],
                                                $mybb->input['month'],
                                                $mybb->input['day']);
                    $kill['calendar'] = '';
                    $kill['year'] = '';
                    $kill['month'] = '';
                    $kill['day'] = '';
                    $kill['google_seo_calendar'] = '';
                }
            }

            break;
    }

    // Verify that we are already at the target.
    if($target)
    {
        $target = $settings['bburl'].'/'.$target;
        $current = google_seo_redirect_current_url();

        // Not identical (although it may only be the query string).
        if($current != $target)
        {
            // Parse current and target
            $target_parse = split("\\?", $target, 2);
            $current_parse = split("\\?", $current, 2);

            // Location
            $location_target = $target_parse[0];
            $location_current = $current_parse[0];

            // Fix broken query strings (e.g. search.php)
            $broken_query = $current_parse[1];
            $broken_query = preg_replace("/\?([^&?]+)=/u", '&$1=', $broken_query);

            if($current_parse[1] != $broken_query)
            {
                $change = 1;
                $current_parse[1] = $broken_query;
            }

            // Query
            parse_str(htmlspecialchars_decode($target_parse[1]), $query_target);
            parse_str($current_parse[1], $query_current);

            $query = array_merge($query_current, $mybb->input);

            // Kill query string elements that already are part of the URL.
            foreach($kill as $k=>$v)
            {
                unset($query[$k]);
            }

            // Final query, current parameters retained
            $query = array_merge($query_target, $query);

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
                // Check if redirect debugging is enabled.
                if($settings['google_seo_redirect_debug']
                   && $mybb->usergroup['cancp'] == 1)
                {
                    if($query['google_seo_redirect'])
                    {
                        // print out information about this redirect and return
                        header("Content-type: text/html; charset=UTF-8");
                        echo "<pre style=\"text-align: left\">";
                        echo "Google SEO Redirect Debug Information:\n";
                        echo htmlspecialchars(
                            print_r(
                                array(
                                    'THIS_SCRIPT' => THIS_SCRIPT,
                                    '_SERVER' => array_merge($_SERVER, array('HTTP_COOKIE' => '')),
                                    'mybb->input' => $mybb->input,
                                    'kill' => $kill,
                                    'target' => $target,
                                    'current' => $current,
                                    'target_parse' => $target_parse,
                                    'current_parse' => $current_parse,
                                    'location_target' => $location_target,
                                    'location_current' => $location_current,
                                    'broken_query' => $broken_query,
                                    'change' => $change,
                                    'query_target' => $query_target,
                                    'query_current' => $query_current,
                                    'query' => $query,
                                    ),
                                true),
                            ENT_COMPAT, "UTF-8");
                        echo "</pre>";
                        return;
                    }

                    else
                    {
                        $query['google_seo_redirect'] = "debug";
                    }
                }

                // Redirect but retain query.
                foreach($query as $k=>$v)
                {
                    $querystr[] = "$k=".urlencode($v);
                }

                if(sizeof($querystr))
                {
                    $location_target .= "?" . implode("&", $querystr);
                }

                $google_seo_redirect = $location_target;

                if($settings['google_seo_redirect_permission'] &&
                   THIS_SCRIPT != "member.php")
                {
                    // Leave permission checks to the current page.

                    // Add hooks to issue redirect later on.
                    $plugins->add_hook("forumdisplay_end", "google_seo_redirect_header", 2);
                    $plugins->add_hook("postbit", "google_seo_redirect_header", 2);
                    $plugins->add_hook("postbit_announcement", "google_seo_redirect_header", 2);
                    $plugins->add_hook("calendar_event_end", "google_seo_redirect_header", 2);
                    $plugins->add_hook("calendar_end", "google_seo_redirect_header", 2);
                }

                else
                {
                    google_seo_redirect_header();
                }
            }
        }
    }
}

function google_seo_redirect_header()
{
    global $plugins, $google_seo_redirect;

    // Issue the redirect.
    header("Location: $google_seo_redirect", true, 301);

    // Only exit if the headers haven't been sent yet.
    // (i.e. if the headers will be sent on exit).
    if(!headers_sent())
    {
        // Hack to prevent any unnecessary queries.
        // (Fixes thread views increase by 2 on redirect issue.)
        global $shutdown_queries;
        $shutdown_queries = array();

        // Exit here, see you at the redirect target.
        exit;
    }

    // Otherwise let the page load normally, but the above
    // call to header will also display a warning message.

    // Remove hooks to prevent getting called again.
    $plugins->remove_hook("forumdisplay_end", "google_seo_redirect_header", "", 2);
    $plugins->remove_hook("postbit", "google_seo_redirect_header", "", 2);
    $plugins->remove_hook("postbit_announcement", "google_seo_redirect_header", "", 2);
    $plugins->remove_hook("calendar_event_end", "google_seo_redirect_header", "", 2);
    $plugins->remove_hook("calendar_end", "google_seo_redirect_header", "", 2);
}

/* --- End of file. --- */
?>