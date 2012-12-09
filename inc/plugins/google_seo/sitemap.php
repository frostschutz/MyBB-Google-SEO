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

// Hijack misc.php for XML Sitemap output.
$plugins->add_hook("misc_start", "google_seo_sitemap_hook", 5);

// WOL extension for custom page:
$plugins->add_hook("build_friendly_wol_location_end", "google_seo_sitemap_wol");

/* --- Helpers: --- */

/**
 * Get a list of calendar IDs the user is not allowed to view.
 *
 */
function google_seo_get_unviewable_calendars()
{
    // Calendar specific permission check.
    require_once MYBB_ROOT."inc/functions_calendar.php";

    $calendars = get_calendar_permissions();
    $unviewablecalendars = array();

    foreach($calendars as $cid => $permissions)
    {
        if($cid == (int)$cid
           && $permissions['canviewcalendar'] == 0)
        {
            $unviewablecalendars[] = (int)$cid;
        }
    }

    return implode(",", $unviewablecalendars);
}

/* --- Sitemap: --- */

/**
 * Build and output a sitemap.
 *
 * @param string Tag which determines the type of the sitemap
 * @param array List of items to be included in this sitemap
 */
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
        $output[] = "    <loc>{$bbsite}{$item['loc']}</loc>";

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

    if($tag == "sitemap")
    {
        $output[] = "</sitemapindex>";
    }

    else if($tag == "url")
    {
        $output[] = "</urlset>";
    }

    @header('Content-Type: text/xml; charset=utf-8');
    @header('X-Robots-Tag: noindex');
    echo implode("\n", $output);
}

/**
 * Generate the list of items for a sitemap.
 * This will be handed to google_seo_sitemap() to produce the XML output.
 *
 * @param string XML Sitemap URL scheme
 * @param string type of items to list in sitemap
 * @param int page number
 * @param int number of items per page
 * @return array List of items (in case of main index)
 */
function google_seo_sitemap_gen($scheme, $type, $page, $pagination)
{
    global $lang, $db, $mybb, $settings;
    global $google_seo_url_optimize;

    if(!$settings["google_seo_sitemap_{$type}"])
    {
        return;
    }

    switch($type)
    {
        case "forums":
            $table = 'forums';
            $idname = 'fid';
            $datename = 'lastpost';
            $getlink = 'get_forum_link';

            // Additional permission check.
            $unviewableforums = get_unviewable_forums();
            $inactiveforums = get_inactive_forums();

            if($unviewableforums)
            {
                $condition[] = "fid NOT IN ($unviewableforums)";
            }

            if($inactiveforums)
            {
                $condition[] = "fid NOT IN ($inactiveforums)";
            }

            // passwords already taken care of unviewable forums,
            // but linkto needs special treatment...
            $condition[] = "linkto=''";

            if($condition)
            {
                $condition = implode(" AND ", $condition);
            }

            // Include pages?
            if($settings['google_seo_sitemap_forums'] == 2)
            {
                $pagescount = ', threads AS pagescount';
                $perpage = $mybb->settings['threadsperpage'];

                if(!$perpage)
                {
                    $perpage = 20;
                }
            }

            break;

        case "threads":
            $table = 'threads';
            $idname = 'tid';
            $datename = 'lastpost';
            $getlink = 'get_thread_link';
            $condition = "visible>0 AND closed NOT LIKE 'moved|%'";

            // Additional permission check.
            $unviewableforums = get_unviewable_forums(true);
            $inactiveforums = get_inactive_forums();

            if($unviewableforums)
            {
                $condition .= " AND fid NOT IN ($unviewableforums)";
            }

            if($inactiveforums)
            {
                $condition .= " AND fid NOT IN ($inactiveforums)";
            }

            // Include pages?
            if($settings['google_seo_sitemap_threads'] == 2)
            {
                $pagescount = ', replies+1 AS pagescount';
                $perpage = $settings['postsperpage'];

                if(!$perpage)
                {
                    $perpage = 20;
                }
            }

            break;

        case "users":
            if(!$mybb->usergroup['canviewprofiles'])
            {
                return;
            }

            $table = 'users';
            $idname = 'uid';
            $datename = 'regdate';
            $getlink = 'get_profile_link';
            $condition = '1=1';
            break;

        case "announcements":
            $table = 'announcements';
            $idname = 'aid';
            $datename = 'startdate';
            $getlink = 'get_announcement_link';
            $time = TIME_NOW;
            $condition = "startdate <= '$time' AND (enddate >= '$time' OR enddate='0')";

            // Additional permission check.
            $unviewableforums = get_unviewable_forums(true);
            $inactiveforums = get_inactive_forums();

            if($unviewableforums)
            {
                $condition .= " AND fid NOT IN ($unviewableforums)";
            }

            if($inactiveforums)
            {
                $condition .= " AND fid NOT IN ($inactiveforums)";
            }

            break;

        case "calendars":
            if($mybb->settings['enablecalendar'] == 0
               || $mybb->usergroup['canviewcalendar'] == 0)
            {
                return;
            }

            $table = 'calendars';
            $idname = 'cid';
            $datename = 'disporder';
            $getlink = 'get_calendar_link';
            $condition = '1=1';

            // Calendar permission check.
            $unviewablecalendars = google_seo_get_unviewable_calendars();

            if($unviewablecalendars)
            {
                $condition = "cid NOT IN ($unviewablecalendars)";
            }

            break;

        case "events":
            // Global permission check.
            if($mybb->settings['enablecalendar'] == 0
               || $mybb->usergroup['canviewcalendar'] == 0)
            {
                return;
            }

            $table = 'events';
            $idname ='eid';
            $datename = 'dateline';
            $getlink = 'get_event_link';

            // Event specific permission check.
            $condition = "visible=1 AND private=0";

            // Calendar permission check.
            $unviewablecalendars = google_seo_get_unviewable_calendars();

            if($unviewablecalendars)
            {
                $condition .= " AND cid NOT IN ($unviewablecalendars)";
            }

            break;

        default:
            error($lang->googleseo_sitemap_pageinvalid);
    }

    if(!$page)
    {
        // Do a pagination index.
        $query = $db->simple_select($table,
                                    "MAX({$datename}) AS lastmod, FLOOR({$idname}/{$pagination}.0)+1 AS page",
                                    "{$condition} GROUP BY FLOOR({$idname}/{$pagination}.0)");

        $url = google_seo_expand($scheme, array('url' => $type));
        $url .= (strpos($url, '?') === false ? '?' : '&amp;').'page=';

        while($row = $db->fetch_array($query))
        {
            $row['loc'] = "{$url}{$row['page']}";
            $items[] = $row;
        }

        // Do not build a sitemap here. Instead return the items.
        // This way, items for all types can be collected for the main index page.
        return $items;
    }

    // Build the sitemap for this page.
    $min = ($page - 1) * $pagination;
    $max = $min + $pagination;

    $query = $db->simple_select($table, "{$idname},{$datename}{$pagescount}",
                                "{$condition} AND {$idname} > {$min} AND {$idname} <= {$max}",
                                array('order_by' => $idname));

    while($row = $db->fetch_array($query))
    {
        $id = $row[$idname];
        $ids[] = $id;
        $dates[$id] = $row[$datename];

        if($pagescount)
        {
            $pages[$id] = (int)(($row['pagescount']-1) / $perpage) + 1;
        }

        else
        {
            $pages[$id] = 0;
        }

        // Google SEO URL Optimization:
        $type2id = array(
            'users' => GOOGLE_SEO_USER,
            'announcements' => GOOGLE_SEO_ANNOUNCEMENT,
            'forums' => GOOGLE_SEO_FORUM,
            'threads' => GOOGLE_SEO_THREAD,
            'events' => GOOGLE_SEO_EVENT,
            'calendars' => GOOGLE_SEO_CALENDAR,
            );

        $google_seo_url_optimize[$type2id[$type]][$id] = 0;
    }

    if(!sizeof($ids))
    {
        error($lang->googleseo_sitemap_emptyorinvalid);
    }

    foreach($ids as $id)
    {
        $item = array();
        $item['loc'] = call_user_func($getlink, $id);

        if($dates[$id])
        {
            $item['lastmod'] = $dates[$id];
        }

        $items[] = $item;

        for($p = 2; $p <= $pages[$id]; $p += 1)
        {
            $item = array();
            $item['loc'] = call_user_func($getlink, $id, $p);

            if($dates[$id])
            {
                $item['lastmod'] = $dates[$id];
            }

            // Give pages of items a lower priority.
            // TODO: Temporary solution until I make this a setting.
            $item['priority'] = '0.2';

            $items[] = $item;
        }
    }

    google_seo_sitemap("url", $items);
}

/**
 * Build the main Index sitemap.
 *
 * This index includes all pages for all types and is made
 * by calling google_seo_sitemap_gen for each type.
 *
 * The resulting list of items is handed off to google_seo_sitemap()
 * to produce the XML Sitemap output.
 *
 * @param string XML Sitemap URL scheme
 * @param int Page number (page 1 == custom, static items)
 * @param int Number of items that should appear per page.
 */
function google_seo_sitemap_index($scheme, $page, $pagination)
{
    global $settings;

    if($page)
    {
        // Additional pages.
        $locs = explode("\n", $settings['google_seo_sitemap_additional']);

        foreach($locs as $loc)
        {
            $loc = trim($loc);

            if($loc)
            {
                $items[] = array('loc' => htmlspecialchars($loc, ENT_QUOTES, "UTF-8"));
            }
        }

        google_seo_sitemap("url", $items);
        return;
    }

    $items = array();

    foreach(array("forums", "threads", "users", "announcements",
                  "calendars", "events") as $type)
    {
        $gen = google_seo_sitemap_gen($scheme, $type, $page, $pagination);

        if(sizeof($gen))
        {
            $items = array_merge($items, $gen);
        }
    }

    if($settings['google_seo_sitemap_additional'])
    {
        $url = google_seo_expand($scheme, array('url' => 'index'));
        $url .= (strpos($url, '?') === false ? '?' : '&amp;').'page=1';
        $items[] = array('loc' => $url);
    }

    google_seo_sitemap("sitemap", $items);
}

/**
 * Hook into misc.php in order to hijack it for XML Sitemap output.
 *
 * Call either google_seo_sitemap_index or _gen to generate the requested
 * XML Sitemap on the fly.
 *
 */
function google_seo_sitemap_hook()
{
    global $lang, $mybb, $settings;

    if(!isset($mybb->input['google_seo_sitemap']))
    {
        // This does not mean us. Do nothing.
        return;
    }

    $type = $mybb->input['google_seo_sitemap'];

    if($type != "index" && !$settings["google_seo_sitemap_{$type}"])
    {
        // This type of sitemap is not enabled.
        error($lang->googleseo_sitemap_disabledorinvalid);
    }

    // Set pagination to something between 100 and 50000.
    $pagination = (int)$settings['google_seo_sitemap_pagination'];
    $pagination = min(max($pagination, 100), 50000);
    $scheme = $settings['google_seo_sitemap_url'];

    if(!$scheme)
    {
        $scheme = 'misc.php?google_seo_sitemap={url}';
    }

    // Set page to something between 0 and 50000.
    $page = (int)$mybb->input['page'];
    $page = min(max($page, 0), 50000);

    if($type == "index")
    {
        google_seo_sitemap_index($scheme, $page, $pagination);
    }

    else if(!$page)
    {
        error($lang->googleseo_sitemap_pageinvalid);
    }

    else
    {
        google_seo_sitemap_gen($scheme, $type, $page, $pagination);
    }

    exit;
}

/* --- WOL --- */

/**
 * Extend WOL for users on the Sitemap page.
 *
 */
function google_seo_sitemap_wol(&$plugin_array)
{
    global $lang, $user, $settings;

    // Check if this user is on a sitemap page.
    if(strstr($plugin_array['user_activity']['location'], "google_seo_sitemap"))
    {
        $plugin_array['user_activity']['activity'] = 'google_seo_sitemap';
        $location = $plugin_array['user_activity']['location'];
        $plugin_array['location_name'] = $lang->sprintf($lang->googleseo_sitemap_wol, $location);
    }
}

/* --- End of file. --- */
?>
