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

// URL -> ID conversion:
$plugins->add_hook("global_start", "google_seo_url_hook", 1);
$plugins->add_hook("moderation_do_merge", "google_seo_url_merge_hook", 1);

/* --- Global Variables: --- */

// Required for database queries to the google_seo table.
// Do not change.
global $google_seo_url_idtype;

$google_seo_url_idtype = array(
    "users" => 1,
    "announcements" => 2,
    "forums" => 3,
    "threads" => 4,
    "events" => 5,
    "calendars" => 6,
    );

// There are several more global variables defined in functions below.

/* --- URL processing: --- */

/**
 * Character translation callback.
 *
 * @param array matches
 * @return string replace string
 */
function google_seo_url_translate_callback($matches)
{
    global $google_seo_translate;

    $r = $google_seo_translate[$matches[0]];

    if($r)
    {
        return $r;
    }

    return $matches[0];
}

/**
 * Character translation for URLs (optional).
 *
 * @param string The input string
 * @return string The output string
 */
function google_seo_url_translate($str)
{
    // Required for URL translation.
    global $google_seo_translate, $google_seo_translate_pat;

    if(!$google_seo_translate)
    {
        if(file_exists(MYBB_ROOT."inc/plugins/google_seo/translate.php"))
        {
            require_once MYBB_ROOT."inc/plugins/google_seo/translate.php";
        }

        if($google_seo_translate)
        {
            foreach($google_seo_translate as $k=>$v)
            {
                $google_seo_translate_pat[] = preg_quote($k, '/');
            }

            $google_seo_translate_pat = implode($google_seo_translate_pat, "|");
        }

        else
        {
            // prevent translate pat from getting generated again.
            $google_seo_translate = true;
        }
    }

    if($google_seo_translate_pat)
    {
        return preg_replace_callback("/$google_seo_translate_pat/u",
                                     "google_seo_url_translate_callback",
                                     $str);
    }

    return $str;
}

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
        // (preg_quote breaks UTF-8 and doesn't escape -)
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
 * @return string truncated string
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
 * Uniquify URLs.
 *
 * @param string The URL that collided with something
 * @param int The ID of this item
 * @return string The uniquified URL
 */
function google_seo_url_uniquify($url, $id)
{
    global $settings;

    $separator = $settings['google_seo_url_separator'];
    eval("\$str = \"{$settings['google_seo_url_uniquifier']}\";");

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
    global $settings;

    eval("\$url = \"$scheme\";");

    if(strlen($url) >= 256)
    {
        /**
         * Maximum URL length hack.
         *
         * Most hosts do not allow for filenames > 255 bytes in URLs.
         * Trying to access such an URL results in 403 Forbidden.
         *
         * Since MyBB already limits length of user names and topic
         * subjects this is normally not a problem. However it is still
         * possible to hit the limit with UTF-8 multibyte characters.
         *
         * This hack causes Google SEO to fall back to stock URLs in
         * the rare case where the Google SEO URL would be unuseable.
         *
         */
        return 0;
    }

    $url = rawurlencode($url);

    return $url;
}

/* --- URL Creation: --- */

/**
 * Create a unique URL in the database for a specific item type,id.
 * First fetch the title of the item from the MyBB database,
 * then process (translate, separate, truncate, uniquify) that title,
 * then check for existing entries in the Google SEO database,
 * finally insert it into the database if it's not already there.
 *
 * @param string Type of the item (forums, threads, etc.)
 * @param array IDs of the item (fid for forums, tid for threads, etc.)
 */
function google_seo_url_create($type, $ids)
{
    global $db, $settings;
    global $google_seo_url_idtype, $google_seo_url_cache;

    $scheme = $settings["google_seo_url_$type"];

    // Is Google SEO URL enabled for this type?
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
        $titles = $db->query("SELECT $titlename,$idname
                              FROM ".TABLE_PREFIX."$type
                              WHERE $idname IN ("
                             .implode((array)$ids, ",")
                             .")");

        while($row = $db->fetch_array($titles))
        {
            $url = $row[$titlename];

            // MyBB unfortunately allows HTML in forum names.
            if($type == "forums")
            {
                $url = strip_tags($url);
            }

            $id = $row[$idname];

            // Prepare the URL.
            if($settings['google_seo_url_translate'])
            {
                $url = google_seo_url_translate($url);
            }

            $url = google_seo_url_separate($url);
            $url = google_seo_url_truncate($url);
            $uniqueurl = google_seo_url_uniquify($url, $id);

            // Special case: for empty URLs we must use the unique variant
            if($url == "" || $settings['google_seo_url_uniquifier_force'])
            {
                $url = $uniqueurl;
            }

            $idtype = $google_seo_url_idtype[$type];

            // Check for existing entry and possible collisions.
            $query = $db->query("SELECT url,id FROM ".TABLE_PREFIX."google_seo
                                 WHERE idtype=$idtype
                                 AND url IN ('"
                                .$db->escape_string($url)."','"
                                .$db->escape_string($uniqueurl)."')
                                 AND active=1
                                 AND id<=$id
                                 AND EXISTS(SELECT * FROM ".TABLE_PREFIX."$type
                                            WHERE $idname=id)
                                 ORDER BY id ASC");

            $urlrow = $db->fetch_array($query);
            $uniquerow = $db->fetch_array($query);

            // Check if the entry was not up to date anyway.
            if($urlrow && $urlrow['id'] == $id && $urlrow['url'] == $url)
            {
                // It's up to date. Do nothing.
            }

            else if($uniquerow && $uniquerow['id'] == $id && $uniquerow['url'] == $uniqueurl)
            {
                // It's up to date for the unique URL.
                $url = $uniqueurl;
            }

            else
            {
                // Use unique URL if there was a row with a different id.
                if($urlrow && $urlrow['id'] != $id)
                {
                    $url = $uniqueurl;
                }

                // Set old entries for us to not active.
                $db->write_query("UPDATE ".TABLE_PREFIX."google_seo
                                  SET active=NULL
                                  WHERE active=1
                                  AND idtype=$idtype
                                  AND id=$id");

                // Insert new entry (while possibly replacing old ones).
                $db->write_query("REPLACE INTO ".TABLE_PREFIX."google_seo
                                  VALUES (active,idtype,id,url),
                                  ('1','$idtype','$id','".$db->escape_string($url)."')");
            }

            // Finalize URL.
            if($settings['google_seo_url_lowercase'])
            {
                $url = my_strtolower($url);
            }

            $url = google_seo_url_finalize($url, $scheme);

            $google_seo_url_cache[$type][$id] = $url;
        }
    }
}

/* --- URL Cache: --- */

/**
 * Optimize queries by collecting as many IDs as possible before sending
 * a request off to a database. This is a dirty trick, a hack, black magic.
 *
 * This list of IDs is obtained by either accessing global data structures
 * of MyBB (possible when MyBB stores data in some sort of cache) or by
 * querying the IDs from the database (necessary when MyBB doesn't cache
 * the data by itself).
 *
 * The list of IDs obtained here is used later to fetch Google SEO URLs
 * in a single query, and create URLs for IDs that do not have an URL yet.
 *
 * @param string Type of the item that caused the query.
 * @param int ID of the item that caused the query.
 * @return array IDs (ID as index, value 0) to be merged into the cache.
 */
function google_seo_url_optimize($type, $id)
{
    global $mybb, $settings, $cache, $db;
    global $google_seo_url_optimize, $google_seo_url_cache;

    switch(THIS_SCRIPT)
    {
        case 'index.php':
            // fcache
            global $fcache, $google_seo_fcache;

            if($fcache !== $google_seo_fcache)
            {
                $GLOBALS['google_seo_fcache'] =& $fcache;

                foreach($fcache as $a)
                {
                    foreach($a as $b)
                    {
                        foreach($b as $c)
                        {
                            $google_seo_url_optimize["forums"][$c['fid']] = 0;
                            $google_seo_url_optimize["users"][$c['lastposteruid']] = 0;
                            $google_seo_url_optimize["threads"][$c['lastposttid']] = 0;
                        }
                    }
                }
            }

            global $google_seo_index;

            if(!$google_seo_index)
            {
                $google_seo_index = true;

                // last user
                $stats = $cache->read("stats");
                $google_seo_url_optimize["users"][$stats['lastuid']] = 0;

                // who's online
                if($mybb->settings['showwol'] != 0 && $mybb->usergroup['canviewonline'] != 0)
                {
                    $timesearch = TIME_NOW - $mybb->settings['wolcutoff'];
                    $query = $db->query("SELECT uid FROM ".TABLE_PREFIX."sessions
                                         WHERE uid != '0' AND time > '$timesearch'");

                    while($user = $db->fetch_array($query))
                    {
                        $google_seo_url_optimize["users"][$user['uid']] = 0;
                    }
                }
            }

            break;

        case 'portal.php':
            global $query, $google_seo_portal_query;

            // Hack: Let's hijack queries made by MyBB. Do not try this at home!
            if($query !== $google_seo_portal_query)
            {
                $GLOBALS['google_seo_portal_query'] &= $query;

                if($query)
                {
                    $num_rows = $db->num_rows($query);
                }

                if($num_rows)
                {
                    // Loop from current pointer to end of query.
                    $i = 0;

                    while($row = $db->fetch_array($query))
                    {
                        $i++;

                        $google_seo_url_optimize["users"][$row['uid']] = 0;
                        $google_seo_url_optimize["users"][$row['lastposteruid']] = 0;
                        $google_seo_url_optimize["threads"][$row['tid']] = 0;
                        $google_seo_url_optimize["forums"][$row['fid']] = 0;
                        $google_seo_url_optimize["announcements"][$row['aid']] = 0;
                    }

                    // Determine original pointer position.
                    $pointer = $num_rows - $i;

                    // Seek to beginning of query.
                    $db->data_seek($query, 0);

                    // Loop from beginning until we reach the original position.
                    $i = 0;

                    while($i < $pointer && $row = $db->fetch_array($query))
                    {
                        $i++;

                        $google_seo_url_optimize["users"][$row['uid']] = 0;
                        $google_seo_url_optimize["users"][$row['lastposteruid']] = 0;
                        $google_seo_url_optimize["threads"][$row['tid']] = 0;
                        $google_seo_url_optimize["forums"][$row['fid']] = 0;
                        $google_seo_url_optimize["announcements"][$row['aid']] = 0;
                    }
                }
            }

            break;

        case 'forumdisplay.php':
            // forum_cache
            global $forum_cache, $google_seo_forum_cache;

            if($forum_cache !== $google_seo_forum_cache)
            {
                $GLOBALS['google_seo_forum_cache'] =& $forum_cache;

                foreach($forum_cache as $f)
                {
                    $google_seo_url_optimize["forums"][$f['fid']] = 0;
                    $google_seo_url_optimize["users"][$f['lastposteruid']] = 0;
                }
            }

            // threadcache optimization (forumdisplay.php)
            global $threadcache, $google_seo_threadcache;

            if($threadcache !== $google_seo_threadcache)
            {
                $GLOBALS['google_seo_threadcache'] =& $threadcache;

                foreach($threadcache as $t)
                {
                    $google_seo_url_optimize["threads"][$t['tid']] = 0;
                    $google_seo_url_optimize["forums"][$t['fid']] = 0;
                    $google_seo_url_optimize["users"][$t['uid']] = 0;
                    $google_seo_url_optimize["users"][$t['lastposteruid']] = 0;
                }
            }

            break;

        case 'search.php':
            // thread_cache
            global $thread_cache, $google_seo_thread_cache;

            if($thread_cache !== $google_seo_thread_cache)
            {
                $GLOBALS['google_seo_thread_cache'] =& $thread_cache;

                foreach($thread_cache as $t)
                {
                    $google_seo_url_optimize["threads"][$t['tid']] = 0;
                    $google_seo_url_optimize["forums"][$t['fid']] = 0;
                    $google_seo_url_optimize["users"][$t['uid']] = 0;
                    $google_seo_url_optimize["users"][$t['lastposteruid']] = 0;
                }
            }

            break;

        case 'calendar.php':
            // events_cache
            global $events_cache, $google_seo_events_cache;

            if($events_cache !== $google_seo_events_cache)
            {
                $GLOBALS['google_seo_events_cache'] =& $events_cache;

                foreach($events_cache as $d)
                {
                    foreach($d as $e)
                    {
                        $google_seo_url_optimize["users"][$e['uid']] = 0;
                        $google_seo_url_optimize["events"][$e['eid']] = 0;
                        $google_seo_url_optimize["calendars"][$e['cid']] = 0;
                    }
                }
            }

            break;

        case 'online.php':
            if($settings['google_seo_url_wol'])
            {
                global $uid_list, $aid_list, $eid_list, $fid_list, $tid_list;

                // uid_list
                if(count($uid_list) && $uid_list !== $google_seo_uid_list)
                {
                    $GLOBALS['google_seo_uid_list'] =& $uid_list;

                    foreach($uid_list as $uid)
                    {
                        $google_seo_url_optimize["users"][$uid] = 0;
                    }
                }

                // aid_list
                if(count($aid_list) && $aid_list !== $google_seo_aid_list)
                {
                    $GLOBALS['google_seo_aid_list'] =& $aid_list;

                    foreach($aid_list as $aid)
                    {
                        $google_seo_url_optimize["announcements"][$aid] = 0;
                    }
                }

                // eid_list
                if(count($eid_list) && $eid_list !== $google_seo_eid_list)
                {
                    $GLOBALS['google_seo_eid_list'] =& $eid_list;

                    foreach($eid_list as $eid)
                    {
                        $google_seo_url_optimize["events"][$eid] = 0;
                    }
                }

                // fid_list
                if(count($fid_list) && $fid_list !== $google_seo_fid_list)
                {
                    $GLOBALS['google_seo_fid_list'] =& $fid_list;

                    foreach($fid_list as $fid)
                    {
                        $google_seo_url_optimize["forums"][$fid] = 0;
                    }
                }

                // tid_list
                if(count($tid_list) && $tid_list !== $google_seo_tid_list)
                {
                    $GLOBALS['google_seo_tid_list'] =& $tid_list;

                    foreach($tid_list as $tid)
                    {
                        $google_seo_url_optimize["threads"][$tid] = 0;
                    }
                }
            }

            break;
    }

    // Include the ID that was originally requested.
    $google_seo_url_optimize[$type][$id] = 0;

    // Extract and return IDs for this type.
    $ids = $google_seo_url_optimize[$type];

    // kill optimize cache for this type because they're getting queried now
    unset($google_seo_url_optimize[$type]);

    // kill bad entries (guests, empty strings)
    unset($ids[0]);
    unset($ids['0']);
    unset($ids['']);

    // return ids
    return $ids;
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
    global $db, $settings, $google_seo_url_cache, $google_seo_url_idtype;

    // If it's not in the cache, try loading the cache.
    if($google_seo_url_cache[$type][$id] === NULL)
    {
        // Prepare database query.
        $idtype = $google_seo_url_idtype[$type];

        if($settings["google_seo_url_lowercase"])
        {
            $what = "LOWER(url) AS url,id";
        }

        else
        {
            $what = "url,id";
        }

        // Optimize by collecting more IDs for this query.
        $ids = google_seo_url_optimize($type, $id);

        // Run database query.
        $query = $db->query("SELECT $what
                             FROM ".TABLE_PREFIX."google_seo
                             WHERE active=1
                             AND idtype=$idtype
                             AND id IN ("
                            .implode(array_keys($ids),",")
                            .")");

        // Process the query results.
        $scheme = $settings["google_seo_url_$type"];

        while($row = $db->fetch_array($query))
        {
            $rowid = $row['id'];
            $google_seo_url_cache[$type][$rowid] =
                google_seo_url_finalize($row['url'], $scheme);
            unset($ids[$rowid]);
        }

        // Create URLs for the remaining IDs.
        if(count($ids))
        {
            google_seo_url_create($type, array_keys($ids));
        }
    }

    // Return the cached entry.
    return $google_seo_url_cache[$type][$id];
}

/* --- URL Lookup: --- */

/**
 * Convert a given URL to ID.
 *
 * @param string Type of the item
 * @param string Given URL of the item
 * @return int ID of the requested item if found.
 */
function google_seo_url_id($type, $url)
{
    global $db, $settings, $google_seo_url_idtype;

    $idtype = $google_seo_url_idtype[$type];

    $query = $db->query("SELECT id
                         FROM ".TABLE_PREFIX."google_seo
                         WHERE idtype=$idtype
                         AND url='".$db->escape_string($url)."'");

    $id = $db->fetch_field($query, "id");

    if(!$id)
    {
        // Fallback for wrong punctuation, character translation:
        $urls[0] = $db->escape_string(google_seo_url_separate($url));

        if($settings['google_seo_url_translate'])
        {
            $urls[1] = $db->escape_string(google_seo_url_translate($url));
            $urls[2] = $db->escape_string(google_seo_url_separate($urls[1]));
        }

        $query = $db->query("SELECT id
                             FROM ".TABLE_PREFIX."google_seo
                             WHERE idtype=$idtype
                             AND url IN ('".implode($urls,"','")."')
                             ORDER BY id ASC
                             LIMIT 1");

        $id = $db->fetch_field($query, "id");
    }

    if($id)
    {
        return $id;
    }

    return 0;
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
    global $db, $settings, $mybb, $session;

    // Translate URL name to ID and verify.
    switch(THIS_SCRIPT)
    {
        case 'forumdisplay.php':
            // Translation.
            $url = $mybb->input['google_seo_forum'];

            if($url && !array_key_exists('fid', $mybb->input))
            {
                $fid = google_seo_url_id("forums", $url);
                $mybb->input['fid'] = $fid;
                $location = get_current_location();
                $location = str_replace("google_seo_forum={$url}",
                                        "fid={$fid}", $location);
                $speciallocs = $session->get_special_locations();
                $updatesession = array(
                    'location' => $location,
                    'location1' => intval($speciallocs['1']),
                    'location2' => intval($speciallocs['2']),
                    );
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
                $tid = google_seo_url_id("threads", $url);
                $mybb->input['tid'] = $tid;
                $location = get_current_location();
                $location = str_replace("google_seo_thread={$url}",
                                        "tid={$tid}", $location);
                $speciallocs = $session->get_special_locations();
                $updatesession = array(
                    'location' => $location,
                    'location1' => intval($speciallocs['1']),
                    'location2' => intval($speciallocs['2'])
                    );
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
                $aid = google_seo_url_id("announcements", $url);
                $mybb->input['aid'] = $aid;
                $location = get_current_location();
                $location = str_replace("google_seo_announcement={$url}", "aid={$aid}", $location);
                $updatesession = array('location' => $location);
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
                $uid = google_seo_url_id("users", $url);
                $mybb->input['uid'] = $uid;
                $location = get_current_location();
                $location = str_replace("google_seo_user={$url}", "uid={$uid}", $location);
                $updatesession = array('location' => $location);
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
                $eid = google_seo_url_id("events", $url);
                $mybb->input['eid'] = $eid;
                $location = get_current_location();
                $location = str_replace("google_seo_event={$url}", "eid={$eid}", $location);
                $updatesession = array('location' => $location);
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
                    $cid = google_seo_url_id("calendars", $url);

                    // Hack to cause invalid calendar message to appear:
                    // If cid is not set, the default calendar would be shown.
                    // However in this case it means an invalid URL was given.
                    if(!$cid)
                    {
                        $cid = -1;
                    }

                    $mybb->input['calendar'] = $cid;
                    $location = get_current_location();
                    $location = str_replace("google_seo_calendar={$cid}", "calendar={$cid}", $location);
                    $updatesession = array('location' => $location);
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

    // Update translated location in the sessions table.
    if($updatesession)
    {
        $db->update_query('sessions', $updatesession,
                          "sid='".$db->escape_string($session->sid)."'");
    }
}

/**
 * Google SEO Merge hook.
 *
 * Unfortunately MyBB asks for a thread URL when merging threads.
 * We have to translate Google SEO URLs back to showthread.php?tid=x URLs.
 */
function google_seo_url_merge_hook()
{
    global $mybb;

    // Build regexp to match URL.
    $regexp = "{$mybb->settings['bburl']}/{$mybb->settings['google_seo_url_threads']}";

    if($regexp)
    {
        $regexp = explode('{$url}', $regexp);
        $regexp = array_map('preg_quote', $regexp, array("/"));
        $regexp = implode('(.*)', $regexp);
        $regexp = "/^{$regexp}$/u";
    }

    // Fetch the (presumably) Google SEO URL:
    $url = $mybb->input['threadurl'];

    // Kill anchors and parameters.
    $url = preg_replace('/^([^#?]*)[#?].*$/u', '\\1', $url);

    // Extract the name part of the URL.
    $url = preg_replace($regexp, '\\1', $url);

    // Unquote the URL.
    $url = urldecode($url);

    // Look up the ID for this item.
    $tid = google_seo_url_id('threads', $url);

    // If we have an ID, produce an URL suitable for merge.
    if($tid)
    {
        $mybb->input['threadurl'] = "{$mybb->settings['bburl']}/showthread.php?tid={$tid}";
    }
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

    if($settings['google_seo_url_users'] && $uid > 0)
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

    if($settings['google_seo_url_announcements'] && $aid > 0)
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

    if($settings['google_seo_url_forums'] && $fid > 0)
    {
        $url = google_seo_url_cache("forums", $fid);

        if($url && $page && $page != 1)
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

    if($settings['google_seo_url_threads'] && $tid > 0)
    {
        $url = google_seo_url_cache("threads", $tid);

        if($url)
        {
            if($page && $page != 1 && $action)
            {
                $url .= "?page=$page&amp;action=$action";
            }

            else if($page && $page != 1)
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

    if($settings['google_seo_url_threads'] && $pid > 0)
    {
        if($tid <= 0)
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

            // If we still don't have a tid, we were given an invalid pid.
            if($tid <= 0)
            {
                return 0;
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

    if($settings['google_seo_url_events'] && $eid > 0)
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

    if($settings['google_seo_url_calendars'] && $cid > 0)
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

    if($settings['google_seo_url_calendars'] && $cid > 0)
    {
        $url = google_seo_url_cache("calendars", $cid);

        if($url)
        {
            return "$url?action=weekview&amp;week=$week";
        }
    }
}

/**
 * Replacement for multipage links
 *
 * @param string base URL of the multipage
 * @return string new base URL of the multipage
 */
function google_seo_url_multipage($url)
{
    switch(THIS_SCRIPT)
    {
        case 'forumdisplay.php':
            // Initialize variables for forumdisplay.
            global $fid;
            $id = $fid;
            $urlcheck = FORUM_URL_PAGED;
            $idname = "fid";
            $type = "forum";
            break;

        case 'showthread.php':
            // Initialize variables for showthread.
            global $tid;
            $id = $tid;
            $urlcheck = THREAD_URL_PAGED;
            $idname = "tid";
            $type = "thread";
            break;
    }

    // The actual code that builds the new multipage URL:
    if($id)
    {
        $getlink = "get_{$type}_link";
        $getlink_googleseo = "google_seo_url_{$type}";

        // replace {idname} with the id to get the default URL
        $urlcheck = str_replace("{{$idname}}", $id, $urlcheck);

        // see if we have a default multipage URL here
        if($url == $urlcheck || strpos($url, $urlcheck.'?') === 0)
        {
            // Check that the Google SEO URLs are being used.
            $seourl = $getlink_googleseo($id, "{page}");

            if($seourl == $getlink($id, "{page}"))
            {
                // Replace it with the Google SEO URL
                $newurl = $seourl;

                // Append extra parameters.
                $extra = substr($url, strlen($urlcheck)+1);

                if($extra)
                {
                    $newurl .= '&'.$extra;
                }
            }
        }
    }

    return $newurl;
}

/* --- End of file. --- */
?>
