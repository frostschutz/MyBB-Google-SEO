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

// Hijack misc.php for XML Sitemap output.
$plugins->add_hook("misc_start", "google_seo_sitemap_hook");

// WOL extension for custom page:
$plugins->add_hook("build_friendly_wol_location_end", "google_seo_sitemap_wol");

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
        $loc = htmlspecialchars($item['loc'], ENT_QUOTES, "UTF-8");
        $output[] = "    <loc>$bbsite$loc</loc>";

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

    @header('Content-type: text/xml; charset=utf-8');
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
    global $db, $mybb, $settings, $google_seo_url_optimize;

    if(!$settings["google_seo_sitemap_$type"])
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
            if($unviewableforums)
            {
                $condition = "WHERE fid NOT IN ($unviewableforums)";
            }
            break;

        case "threads":
            $table = 'threads';
            $idname = 'tid';
            $datename = 'dateline';
            $getlink = 'get_thread_link';
            $condition = "WHERE visible>0 AND closed NOT LIKE 'moved|%'";
            // Additional permission check.
            $unviewableforums = get_unviewable_forums(true);
            if($unviewableforums)
            {
                $condition .= " AND fid NOT IN ($unviewableforums)";
            }
            break;

        case "users":
            $table = 'users';
            $idname = 'uid';
            $datename = 'regdate';
            $getlink = 'get_profile_link';
            break;

        case "announcements":
            $table = 'announcements';
            $idname = 'aid';
            $datename = 'startdate';
            $getlink = 'get_announcement_link';
            // Additional permission check.
            $unviewableforums = get_unviewable_forums(true);
            if($unviewableforums)
            {
                $condition = "WHERE fid NOT IN ($unviewableforums)";
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
            break;

        case "events":
            if($mybb->settings['enablecalendar'] == 0
               || $mybb->usergroup['canviewcalendar'] == 0)
            {
                return;
            }

            $table = 'events';
            $idname ='eid';
            $datename = 'dateline';
            $getlink = 'get_event_link';
            break;
    }

    if(!$page)
    {
        // Do a pagination index.
        $query = $db->query("SELECT COUNT(*) as count
                             FROM ".TABLE_PREFIX."$table
                             $condition");
        $count = $db->fetch_field($query, "count");
        $offset = 0;

        while($offset < $count)
        {
            $page++;
            $item = array();
            $url = $type;

            if($scheme)
            {
                eval("\$item['loc'] = \"$scheme?page={$page}\";");
            }

            else
            {
                $item['loc'] = "misc.php?google_seo_sitemap={$url}&page={$page}";
            }

            // find the last (newest) of the oldest posts
            $query = $db->query("SELECT $datename FROM
                                   (SELECT $datename FROM ".TABLE_PREFIX."$table
                                    $condition
                                    ORDER BY $datename ASC
                                    LIMIT $offset, $pagination) AS foobar
                                 ORDER BY $datename DESC LIMIT 1");

            $lastmod = $db->fetch_field($query, $datename);

            if($lastmod)
            {
                $item["lastmod"] = $lastmod;
            }

            $items[] = $item;

            $offset += $pagination;
        }

        // Do not build a sitemap here. Instead return the items.
        // This way, items for all types can be collected for the main index page.
        return $items;
    }

    // Build the sitemap for this page.
    $offset = ($page - 1) * $pagination;

    $query = $db->query("SELECT $idname,$datename FROM ".TABLE_PREFIX."$table
                         $condition
                         ORDER BY $datename ASC
                         LIMIT $offset, $pagination");

    while($row = $db->fetch_array($query))
    {
        $id = $row[$idname];
        $ids[] = $id;
        $dates[$id] = $row[$datename];

        // Google SEO URL Optimization:
        $google_seo_url_optimize[$type][$id] = 0;
    }

    if(!sizeof($ids))
    {
        error("Sitemap empty or invalid page.");
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
                $items[] = array('loc' => $loc);
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
        $url = "index";

        if($scheme)
        {
            eval("\$loc = \"$scheme?page=1\";");
        }

        else
        {
            $loc = "misc.php?google_seo_sitemap=index&page=1";
        }

        $items[] = array('loc' => $loc);
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
    global $mybb, $settings;

    if(!isset($mybb->input['google_seo_sitemap']))
    {
        // This does not mean us. Do nothing.
        return;
    }

    $type = $mybb->input['google_seo_sitemap'];

    if($type != "index" && !$settings["google_seo_sitemap_$type"])
    {
        // This type of sitemap is not enabled.
        error("Sitemap disabled or invalid");
    }

    // Set pagination to something between 100 and 50000.
    $pagination = (int)$settings['google_seo_sitemap_pagination'];
    $pagination = min(max($pagination, 100), 50000);
    $scheme = $settings['google_seo_sitemap_url'];

    // Set page to something between 0 and 50000.
    $page = (int)$mybb->input['page'];
    $page = min(max($page, 0), 50000);

    if($type == "index")
    {
        google_seo_sitemap_index($scheme, $page, $pagination);
    }

    else if(!$page)
    {
        error("Sitemap page invalid.");
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
function google_seo_sitemap_wol($plugin_array)
{
    global $user, $settings;

    // Check if this user is on a sitemap page.
    if(strstr($plugin_array['user_activity']['location'], "google_seo_sitemap") !== false)
    {
        $plugin_array['user_activity']['activity'] = 'google_seo_sitemap';
        $location = $plugin_array['user_activity']['location'];
        $location = str_replace("&amp;", "&", $location); // MyBB 1.4.4 Bug workaround
        $location_name = $settings['google_seo_sitemap_wol'];
        $location_name = str_replace("[location]", "<a href=\"$location\">", $location_name);
        $location_name = str_replace("[/location]", "</a>", $location_name);
        $plugin_array['location_name'] = $location_name;
    }
}

/* --- End of file. --- */
?>