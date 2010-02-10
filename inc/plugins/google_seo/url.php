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

// URL -> ID lookups:
$plugins->add_hook("global_start", "google_seo_url_hook", 1);

// Optimization for various pages:
$plugins->add_hook("forumdisplay_start", "google_seo_url_hook_forumdisplay");
$plugins->add_hook("forumdisplay_thread", "google_seo_url_hook_forumdisplay_thread");
$plugins->add_hook("index_start", "google_seo_url_hook_index");
$plugins->add_hook("build_forumbits_forum", "google_seo_url_hook_forumbits");
$plugins->add_hook("showthread_start", "google_seo_url_hook_showthread");

/* --- URL processing: --- */

/**
 * Separate a string by punctuation for use in URLs.
 *
 * @param string The input string that may contain punctuation
 * @return string The string with punctuation replaced with separator
 */
function google_seo_url_separate($str)
{
    global $settings;

    $pattern = $settings['google_seo_url_punctuation'];

    if($pattern)
    {
        // Escape the pattern.
        $pattern = preg_replace("/[\\\\\\^\\-\\[\\]\\/]/u",
                                "\\\\\\0",
                                $pattern);

        // Cut off punctuation at beginning and end.
        $str = preg_replace("/^[$pattern]+|[$pattern]+$/u",
                            "",
                            $str);

        // Replace middle punctuation with one separator.
        $str = preg_replace("/[$pattern]+/u",
                            $settings['google_seo_url_separator'],
                            $str);
    }

    return $str;
}

/**
 * Truncate too long URLs.
 *
 * @param string The string to be truncated.
 * @param string The word separator.
 * @param int The soft limit.
 * @param int The hard limit.
 * @return truncated string
 */
function google_seo_url_truncate($str)
{
    global $settings;

    $separator = $settings['google_seo_url_separator'];
    $soft = $settings['google_seo_url_length_soft'];
    $hard = $settings['google_seo_url_length_hard'];

    // Cut off word past soft limit.
    if($soft && my_strlen($str) > $soft)
    {
        // Search the separator after the soft limit.
        $part = my_substr($str, $soft);
        $pos = my_strpos($part, $separator);

        if($pos === 0 || $pos > 0)
        {
            $str = my_substr($str, 0, $soft + $pos);
        }
    }

    // Truncate hard limit.
    if($hard && my_strlen($str) > $hard)
    {
        $str = my_substr($str, 0, $hard);
    }

    return $str;
}

/**
 * Finalize URLs. (Lowercase step done by the caller.)
 *
 * @param string URL to be finalized.
 * @param string Scheme to be used for this URL.
 * @return string Finalized URL.
 */
function google_seo_url_finalize($url, $scheme)
{
    eval("\$url = \"$scheme\";");

    if(strlen($url) >= 256)
    {
        /*
         * Maximum URL length hack.
         *
         * Most hosts do not allow for filenames > 256 bytes in URLs.
         * Trying to access such an URL results in 403 Forbidden.
         *
         * Since MyBB already limits length of user names and topic
         * subjects this is normally not a problem. However it is still
         * possible to hit the limit with UTF-8 multi byte characters.
         *
         * This hack causes Google SEO to fall back to stock URLs in
         * the rare case where the Google SEO URL would be unuseable.
         *
         */
        return 0;
    }

    return urlencode($url);
}

/* --- URL Caching: --- */

/**
 * Put ID numbers into the queue.
 * On the next query all IDs in the queue will be queried at once.
 */
function google_seo_url_queue($type, $ids)
{
    global $google_seo_url_queue, $google_seo_url_cache;

    foreach($ids as $id)
    {
        if($google_seo_url_cache[$type][$id] === NULL)
        {
            $google_seo_url_queue[$type][] = $id;
        }
    }
}

/**
 * Get the entry for $type, $id from the cache.
 *
 * If $id is not yet in the cache, the $id will be queried from the DB.
 * To keep number of total DB queries low, all $ids that have been queued
 * up to here will be fetched in the same query as well.
 *
 */
function google_seo_url_cache($type, $id)
{
    global $db, $settings, $google_seo_url_cache, $google_seo_url_queue;

    // If it's not in the cache, try loading the cache.
    if($google_seo_url_cache[$type][$id] === NULL)
    {
        // Prepare database query.
        $ids = $google_seo_url_queue[$type];

        if($ids)
        {
            $where = "id IN ($id,".join($ids,",").")";
            unset($google_seo_url_queue[$type]);
        }

        else
        {
            $where = "id=$id";
        }

        if($settings["google_seo_url_lowercase"])
        {
            $what = "LOWER(url) as url,id";
        }

        else
        {
            $what = "url,id";
        }

        // Run database query.
        $query = $db->query("SELECT $what FROM ".TABLE_PREFIX."google_seo_$type
                             WHERE $where
                             GROUP BY id
                             ORDER BY rowid DESC");

        // Process the query results.
        $scheme = $settings["google_seo_url_$type"];

        while($row = $db->fetch_array($query))
        {
            $google_seo_url_cache[$type][$row['id']] =
                google_seo_url_finalize($row['url'], $scheme);
        }

        // If it's still not in the cache, create an entry.
        if($google_seo_url_cache[$type][$id] === NULL)
        {
            google_seo_url_create($type, $id);
        }
    }

    // Return the cached entry.
    return $google_seo_url_cache[$type][$id];
}

/* --- URL Creation: --- */

function google_seo_url_create($type, $id)
{
    global $db, $settings, $google_seo_url_cache;

    $scheme = $settings["google_seo_url_$type"];

    if($scheme)
    {
        // Prepare the query of the item title:
        switch($type)
        {
            case "users":
                $idname = "uid";
                $titlename = "username";
                break;
            case "announcements":
                $idname = "aid";
                $titlename = "subject";
                break;
            case "forums":
                $idname = "fid";
                $titlename = "name";
                break;
            case "threads":
                $idname = "tid";
                $titlename = "subject";
                break;
            case "events":
                $idname = "eid";
                $titlename = "name";
                break;
            case "calendars":
                $idname = "cid";
                $titlename = "name";
                break;
        }

        // Query the item title as base for our URL.
        $query = $db->query("SELECT $titlename FROM ".TABLE_PREFIX."$type
                         WHERE $idname=$id");
        $url = $db->fetch_field($query, $titlename);

        if($url)
        {
            // Prepare the URL.
            $url = google_seo_url_separate($url);
            $url = google_seo_url_truncate($url);

            // Check for existing entry and possible collision.
            $query = $db->query("SELECT url,id FROM
                                   (SELECT url,id FROM ".TABLE_PREFIX."google_seo_$type
                                      WHERE id IN
                                        (SELECT id FROM ".TABLE_PREFIX."google_seo_$type
                                         WHERE url='".$db->escape_string($url)."'
                                         AND id<=$id)
                                      ORDER BY rowid DESC
                                      LIMIT 1) as top_row
                                 WHERE url='".$db->escape_string($url)."'
                                 ORDER BY id ASC
                                 LIMIT 1");

            $row = $db->fetch_array($query);

            // Check if the entry was not up to date anyway.
            if(!($row && $row['id'] == $id && $row['url'] == $url))
            {
                // Uniquify in case of collision.
                if($row && $row['id'] != $id)
                {
                    eval("\$url = \"{$settings['google_seo_url_uniquifier']}\";");
                }

                // Delete old entries in favour of the new one.
                $db->delete_query("google_seo_$type",
                                  "url='".$db->escape_string($url)."'");

                // Insert the new URL into the database.
                $db->write_query("INSERT INTO ".TABLE_PREFIX."google_seo_$type
                                  (id,url)
                                  VALUES('$id','".$db->escape_string($url)."')");
            }

            // Finalize URL.
            if($settings['google_seo_lowercase'])
            {
                $url = my_strtolower($url);
            }

            $url = google_seo_url_finalize($url, $scheme);

            // Put into cache.
            $google_seo_url_cache[$type][$id] = $url;

            return $url;
        }
    }
}

/* --- URL Lookup: --- */

/**
 * Convert URL to ID.
 *
 * @param string Name of the Google SEO database table.
 * @param string Name of the ID column of that table.
 * @param string Name of the requested URL.
 * @return int ID of the requested item.
 */
function google_seo_url_id($tablename, $idname, $url)
{
    global $db;

    $query = $db->query("SELECT id
                         FROM ".TABLE_PREFIX."google_seo_$tablename
                         WHERE url='".$db->escape_string($url)."'");

    $id = $db->fetch_field($query, "id");

    if(!$id)
    {
        // Something went wrong. Maybe user added some punctuation?
        $url = google_seo_url_separate($url);

        $query = $db->query("SELECT id
                             FROM ".TABLE_PREFIX."google_seo_$tablename
                             WHERE url='".$db->escape_string($url)."'");

        $id = $db->fetch_field($query, "id");
    }

    return $id;
}

/**
 * Google SEO URL hook.
 *
 * Converts requested Google SEO URLs back to the ID number parameters
 * which are required and expected by stock MyBB to find the correct
 * forum / thread / etc. to display.
 *
 * This is also the point where Google SEO updates the URLs in case
 * the requested item changed its name since the last update.
 */
function google_seo_url_hook()
{
    global $db, $settings, $mybb;

    // Translate URL name to ID and verify.
    switch(THIS_SCRIPT)
    {
        case 'forumdisplay.php':
            // Translation.
            $url = $mybb->input['google_seo_forum'];

            if($url && !array_key_exists('fid', $mybb->input))
            {
                $fid = google_seo_url_id("forums", "fid", $url);
                $mybb->input['fid'] = $fid;
            }

            // Verification.
            $fid = $mybb->input['fid'];

            if($fid)
            {
                google_seo_url_create("forums", $fid);
            }

            break;

        case 'showthread.php':
            // Translation.
            $url = $mybb->input['google_seo_thread'];

            if($url && !array_key_exists('tid', $mybb->input))
            {
                $tid = google_seo_url_id("threads", "tid", $url);
                $mybb->input['tid'] = $tid;
            }

            // Verification.
            $tid = $mybb->input['tid'];

            if($tid)
            {
                google_seo_url_create("threads", $tid);
            }

            $pid = $mybb->input['pid'];

            break;

        case 'announcement.php':
            // Translation.
            $url = $mybb->input['google_seo_announcement'];

            if($url && !array_key_exists('aid', $mybb->input))
            {
                $aid = google_seo_url_id("announcements", "aid", $url);
                $mybb->input['aid'] = $aid;
            }

            // Verification.
            $aid = $mybb->input['aid'];

            if($aid)
            {
                google_seo_url_create("announcements", $aid);
            }

            break;

        case 'member.php':
            // Translation.
            $url = $mybb->input['google_seo_user'];

            if($url && !array_key_exists('uid', $mybb->input))
            {
                $uid = google_seo_url_id("users", "uid", $url);
                $mybb->input['uid'] = $uid;
            }

            // Verification.
            $uid = $mybb->input['uid'];

            if($uid && $mybb->input['action'] == 'profile')
            {
                google_seo_url_create("users", $uid);
            }

            break;

        case 'calendar.php':
            // Translation.
            $url = $mybb->input['google_seo_event'];

            if($url && !array_key_exists('eid', $mybb->input))
            {
                $eid = google_seo_url_id("events", "eid", $url);
                $mybb->input['eid'] = $eid;
            }

            // Verification.
            $eid = $mybb->input['eid'];

            if($eid)
            {
                google_seo_url_create("events", $eid);
            }

            else if(!$url)
            {
                $url = $mybb->input['google_seo_calendar'];

                if($url && !array_key_exists('calendar', $mybb->input))
                {
                    $cid = google_seo_url_id("calendars", "cid", $url);
                    $mybb->input['calendar'] = $cid;
                }

                // Verification.
                $cid = $mybb->input['calendar'];

                if($cid)
                {
                    google_seo_url_create("calendars", $cid);
                }
            }

            break;
    }
}

/* --- Optimization: --- */

function google_seo_url_hook_forumdisplay()
{
    global $forum_cache;

    if(!is_array($forum_cache))
    {
        cache_forums();
    }

    foreach($forum_cache as $v)
    {
        $fids[] = $v['fid'];
        $uids[] = $v['lastposteruid'];
    }

    google_seo_url_queue("forums", $fids);
    google_seo_url_queue("users", $uids);
}

function google_seo_url_hook_forumdisplay_thread()
{
    global $threadcache, $plugins;

    // We are only interested in the first call.
    $plugins->remove_hook("forumdisplay_thread",
                          "google_seo_url_hook_forumdisplay_thread");

    foreach($threadcache as $thread)
    {
        $tids[] = $thread['tid'];
        $fids[] = $thread['fid'];
        $uids[] = $thread['uid'];
        $uids[] = $thread['lastposteruid'];
    }

    google_seo_url_queue("threads", $tids);
    google_seo_url_queue("forums", $fids);
    google_seo_url_queue("users", $uids);
}

function google_seo_url_hook_index()
{
    global $mybb;

    $uids[] = $mybb->user['uid'];

    google_seo_url_queue("users", $uids);
}

function google_seo_url_hook_forumbits()
{
    global $fcache, $plugins;

    $plugins->remove_hook("build_forumbits_forum",
                          "google_seo_url_hook_forumbits");

    foreach($fcache as $p)
    {
        foreach($p as $c)
        {
            foreach($c as $f)
            {
                $fids[] = $f['fid'];
                $uids[] = $f['lastposteruid'];
                $tids[] = $f['lastposttid'];
            }
        }
    }

    google_seo_url_queue("forums", $fids);
    google_seo_url_queue("users", $uids);
    google_seo_url_queue("threads", $tids);
}

function google_seo_url_hook_showthread()
{
    global $thread;

    $tids[] = $thread['tid'];
    $fids[] = $thread['fid'];
    $uids[] = $thread['uid'];
    $uids[] = $thread['lastposteruid'];

    google_seo_url_queue("threads", $tids);
    google_seo_url_queue("forums", $fids);
    google_seo_url_queue("uids", $uids);
}

/* --- URL API: --- */

/**
 * Replacement for get_profile_link()
 *
 * @param int ID of the linked user profile.
 * @return string User profile URL.
 */
function google_seo_url_profile($uid=0)
{
    global $settings;

    if($settings['google_seo_url_users'])
    {
        return google_seo_url_cache("users", $uid);
    }
}

/**
 * Replacement for get_announcement_link()
 *
 * @param int ID of the linked announcement.
 * @return string Announcement URL.
 */
function google_seo_url_announcement($aid=0)
{
    global $settings;

    if($settings['google_seo_url_announcements'])
    {
        return google_seo_url_cache("announcements", $aid);
    }
}


/**
 * Replacement for get_forum_link()
 *
 * @param int ID of the linked forum.
 * @param int Optional page number.
 * @return string Forum URL.
 */
function google_seo_url_forum($fid, $page=0)
{
    global $settings;

    if($settings['google_seo_url_forums'])
    {
        $url = google_seo_url_cache("forums", $fid);

        if($url && $page > 1)
        {
            $url .= "?page=$page";
        }

        return $url;
    }
}

/**
 * Replacement for get_thread_link()
 *
 * @param int ID of the linked thread.
 * @param int Optional page number.
 * @param string Optional action.
 * @return string Thread URL.
 */
function google_seo_url_thread($tid, $page=0, $action='')
{
    global $settings;

    if($settings['google_seo_url_threads'])
    {
        $url = google_seo_url_cache("threads", $tid);

        if($url)
        {
            if($page > 1 && $action)
            {
                $url .= "?page=$page&amp;action=$action";
            }

            else if($page > 1)
            {
                $url .= "?page=$page";
            }

            else if($action)
            {
                $url .= "?action=$action";
            }

            return $url;
        }
    }
}

/**
 * Replacement for get_post_link()
 *
 * @param int ID of the linked posting.
 * @param int Optional thread ID if known (has to be looked up otherwise).
 * @return string Post URL.
 */
function google_seo_url_post($pid, $tid=0)
{
    global $settings, $db;

    if($settings['google_seo_url_threads'])
    {
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
                $query = $db->simple_select("posts", "tid", "pid={$pid}",
                                            $options);
                $tid = $db->fetch_field($query, "tid");
            }
        }

        $url = google_seo_url_cache("threads", $tid);

        if($url)
        {
            $url .= "?pid={$pid}";
        }

        return $url;
    }
}

/**
 * Replacement for get_event_link()
 *
 * @param int ID of the linked event.
 * @return string Event URL.
 */
function google_seo_url_event($eid)
{
    global $settings;

    if($settings['google_seo_url_events'])
    {
        return google_seo_url_cache("events", $eid);
    }
}

/**
 * Replacement for get_calendar_link()
 *
 * @param int ID of the linked calendar.
 * @param int Optional year.
 * @param int Optional month.
 * @param int Optional day.
 * @return string Calendar URL.
 */
function google_seo_url_calendar($cid, $year=0, $month=0, $day=0)
{
    global $settings;

    if($settings['google_seo_url_calendars'])
    {
        $url = google_seo_url_cache("calendars", $cid);

        if($url && $year)
        {
            $url .= "?year=$year";

            if($month)
            {
                $url .= "&amp;month=$month";

                if($day)
                {
                    $url .= "&amp;day=$day&amp;action=dayview";
                }
            }
        }

        return $url;
    }
}

/**
 * Replacement for get_calendar_week_link()
 *
 * @param int ID of the linked calendar.
 * @param int Week.
 * @return string Calendar Week URL.
 */
function google_seo_url_calendar_week($cid, $week)
{
    global $settings;

    if($settings['google_seo_url_calendars'])
    {
        $url = google_seo_url_cache("calendars", $cid);

        if($url)
        {
            return "$url?action=weekview&amp;week=$week";
        }
    }
}

/* --- End of file. --- */
?>
