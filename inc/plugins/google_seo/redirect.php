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
    global $settings;

    // Determine the current page URL.
    if($_SERVER["HTTPS"] == "on")
    {
        $page_url = "https://".$_SERVER["HTTP_HOST"];
    }

    else
    {
        $page_url = "http://".$_SERVER["HTTP_HOST"];
    }

    $request_uri = explode('?', $_SERVER["REQUEST_URI"], 2);
    $request_uri[0] = urldecode($request_uri[0]);
    $request_uri = implode('?', $request_uri);

    $page_url .= $request_uri;

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
            if((int)$mybb->input['fid'])
            {
                $target = get_forum_link((int)$mybb->input['fid'],
                                         (int)$mybb->input['page']);
                $kill['fid'] = '';
                $kill['page'] = '';
                $kill['google_seo_forum'] = '';
                $kill['google_seo'] = '';
            }

            break;

        case 'showthread.php':
            // pid overrules tid, so we must check pid first,
            // even at the cost of an additional query.
            if((int)$mybb->input['pid'])
            {
                $tid = google_seo_tid((int)$mybb->input['pid'],
                                      (int)$mybb->input['tid'],
                                      $settings['google_seo_redirect_posts']);
                $target = get_post_link((int)$mybb->input['pid'], $tid);
                $kill['pid'] = '';
                $kill['tid'] = '';
                $kill['google_seo_thread'] = '';
                $kill['google_seo'] = '';
            }

            else if((int)$mybb->input['tid'])
            {
                $target = get_thread_link((int)$mybb->input['tid'],
                                          (int)$mybb->input['page'],
                                          (string)$mybb->input['action']);
                $kill['tid'] = '';
                $kill['action'] = '';
                $kill['google_seo_thread'] = '';
                $kill['google_seo'] = '';

                if($mybb->input['page'] != 'last')
                {
                    $kill['page'] = '';
                }
            }

            break;

        case 'announcements.php':
            if((int)$mybb->input['aid'])
            {
                $target = get_announcement_link((int)$mybb->input['aid']);
                $kill['aid'] = '';
                $kill['google_seo_announcement'] = '';
                $kill['google_seo'] = '';
            }

            break;

        case 'member.php':
            if((int)$mybb->input['uid'])
            {
                if($settings['google_seo_redirect_litespeed']
                   && $mybb->input['action'] != 'profile')
                {
                    // Work around rewrite bug in LiteSpeed (double action conflict).
                    break;
                }

                $target = get_profile_link((int)$mybb->input['uid']);
                $kill['uid'] = '';
                $kill['google_seo_user'] = '';
                $kill['google_seo'] = '';

                if($mybb->input['action'] == 'profile')
                {
                    $kill['action'] = '';
                }
            }

            break;

        case 'calendar.php':
            if((int)$mybb->input['eid'])
            {
                if($settings['google_seo_redirect_litespeed']
                   && $mybb->input['action'] != 'profile')
                {
                    // Work around rewrite bug in LiteSpeed (double action conflict).
                    break;
                }

                $target = get_event_link((int)$mybb->input['eid']);
                $kill['eid'] = '';
                $kill['google_seo_event'] = '';
                $kill['google_seo'] = '';

                if($mybb->input['action'] == 'event')
                {
                    $kill['action'] = '';
                }
            }

            else
            {
                if(!(int)$mybb->input['calendar'])
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
                    $target = get_calendar_week_link((int)$mybb->input['calendar'],
                                                     (int)$mybb->input['week']);
                    $kill['calendar'] = '';
                    $kill['week'] = '';
                    $kill['action'] = '';
                    $kill['google_seo_calendar'] = '';
                    $kill['google_seo'] = '';
                }

                else
                {
                    $target = get_calendar_link((int)$mybb->input['calendar'],
                                                (int)$mybb->input['year'],
                                                (int)$mybb->input['month'],
                                                (int)$mybb->input['day']);
                    $kill['calendar'] = '';
                    $kill['year'] = '';
                    $kill['month'] = '';
                    $kill['day'] = '';
                    $kill['google_seo_calendar'] = '';
                    $kill['google_seo'] = '';
                }
            }

            break;
    }

    // Verify that we are already at the target.
    if($target)
    {
        $target = $settings['bburl'].'/'.urldecode($target);
        $current = google_seo_redirect_current_url();

        // Not identical (although it may only be the query string).
        if($current != $target)
        {
            // Parse current and target
            $target_parse = explode("?", $target, 2);
            $current_parse = explode("?", $current, 2);

            // Location
            $location_target = $target_parse[0];
            $location_current = $current_parse[0];

            // Fix broken query strings (e.g. search.php)
            $broken_query = preg_replace("/\?([^&?=]+)([=&])/u",
                                         '&$1$2',
                                         $current_parse[1]);

            if($current_parse[1] != $broken_query)
            {
                $change = 1;
                $current_parse[2] = $current_parse[1];
                $current_parse[1] = $broken_query;
            }

            // Query
            $current_dynamic = google_seo_dynamic('?'.$current_parse[1]);
            $target_dynamic = google_seo_dynamic('?'.$target_parse[1]);
            parse_str(htmlspecialchars_decode($target_parse[1]), $query_target);
            parse_str($current_parse[1], $query_current);

            if(@get_magic_quotes_gpc())
            {
                // Dear PHP, I don't need magic, thank you very much.
                $mybb->strip_slashes_array($query_target);
                $mybb->strip_slashes_array($query_current);
            }

            $query = $query_current;

            // Kill query string elements that already are part of the URL.
            if(!$query[$target_dynamic])
            {
                unset($query[$target_dynamic]);
                unset($query_current[$target_dynamic]);
                unset($query_target[$target_dynamic]);
            }

            if(!$query[$current_dynamic])
            {
                unset($query[$current_dynamic]);
                unset($query_current[$current_dynamic]);
                unset($query_target[$current_dynamic]);
            }

            foreach($kill as $k=>$v)
            {
                unset($query[$k]);
            }

            // Final query, current parameters retained
            $query = array_merge($query_target, $query);

            if(count($query) != count($query_current))
            {
                $change = 2;
            }

            else if($current_dynamic != $target_dynamic)
            {
                $change = 3;
            }

            else
            {
                foreach($query as $k=>$v)
                {
                    if($query_current[$k] != $v)
                    {
                        $change = 4;
                    }
                }
            }

            // Definitely not identical?
            if($change || $location_target != $location_current)
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
                        echo "!!! WARNING: This may contain cookie authentication data. Don't post debug info in public. !!!\n";
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
                                    'target_dynamic' => $target_dynamic,
                                    'current_dynamic' => $current_dynamic,
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
                if($target_dynamic)
                {
                    $querystr[] = google_seo_encode($target_dynamic);
                }

                foreach($query as $k=>$v)
                {
                    $querystr[] = urlencode($k)."=".urlencode($v);
                }

                $location_target = google_seo_encode($location_target);

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
                    $plugins->add_hook("calendar_editevent_end", "google_seo_redirect_header", 2);
                    $plugins->add_hook("calendar_event_end", "google_seo_redirect_header", 2);
                    $plugins->add_hook("calendar_end", "google_seo_redirect_header", 2);
                    $plugins->add_hook("pre_output_page", "google_seo_redirect_header", 2);
                    // Except on error.
                    $plugins->add_hook("error", "google_seo_redirect_remove_hooks", 2);
                    $plugins->add_hook("no_permission", "google_seo_redirect_remove_hooks", 2);
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
    header("Location: {$google_seo_redirect}", true, 301);

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
    google_seo_redirect_remove_hooks();
}

function google_seo_redirect_remove_hooks()
{
    global $plugins;

    // Remove hooks to prevent getting called again.
    $plugins->remove_hook("forumdisplay_end", "google_seo_redirect_header", "", 2);
    $plugins->remove_hook("postbit", "google_seo_redirect_header", "", 2);
    $plugins->remove_hook("postbit_announcement", "google_seo_redirect_header", "", 2);
    $plugins->remove_hook("calendar_editevent_end", "google_seo_redirect_header", "", 2);
    $plugins->remove_hook("calendar_event_end", "google_seo_redirect_header", "", 2);
    $plugins->remove_hook("calendar_end", "google_seo_redirect_header", "", 2);
    $plugins->remove_hook("pre_output_page", "google_seo_redirect_header", "", 2);
    $plugins->remove_hook("error", "google_seo_redirect_remove", "", 2);
    $plugins->remove_hook("no_permission", "google_seo_redirect_remove", "", 2);
}

/* --- End of file. --- */
?>
