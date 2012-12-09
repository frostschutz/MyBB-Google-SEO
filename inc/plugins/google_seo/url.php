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

/* --- Global Variables: --- */

global $db, $mybb, $settings, $plugins, $cache;

// Required for database queries to the google_seo table. In theory this
// could be used to coerce Google SEO into managing URLs of other types.
// In practice there is no guarantee that this API will stay stable.

$db->google_seo_url = array(
    GOOGLE_SEO_USER => array(
        'table' => TABLE_PREFIX.'users',
        'id' => 'uid',
        'name' => 'username',
        'scheme' => str_replace('&', '&amp;', $settings['google_seo_url_users']),
        ),
    GOOGLE_SEO_ANNOUNCEMENT => array(
        'table' => TABLE_PREFIX.'announcements',
        'id' => 'aid',
        'name' => 'subject',
        'scheme' => str_replace('&', '&amp;', $settings['google_seo_url_announcements']),
        ),
    GOOGLE_SEO_FORUM => array(
        'table' => TABLE_PREFIX.'forums',
        'id' => 'fid',
        'name' => 'name',
        'scheme' => str_replace('&', '&amp;', $settings['google_seo_url_forums']),
        ),
    GOOGLE_SEO_THREAD => array(
        'table' => TABLE_PREFIX.'threads',
        'id' => 'tid',
        'name' => 'subject',
        'scheme' => str_replace('&', '&amp;', $settings['google_seo_url_threads']),
        ),
    GOOGLE_SEO_EVENT => array(
        'table' => TABLE_PREFIX.'events',
        'id' => 'eid',
        'name' => 'name',
        'scheme' => str_replace('&', '&amp;', $settings['google_seo_url_events']),
        ),
    GOOGLE_SEO_CALENDAR => array(
        'table' => TABLE_PREFIX.'calendars',
        'id' => 'cid',
        'name' => 'name',
        'scheme' => str_replace('&', '&amp;', $settings['google_seo_url_calendars']),
        ),
    );

// Lazy Mode.

global $google_seo_url_lazy;

$google_seo_url_lazy = false;

if($settings['google_seo_url_mode'] == 'lazy'
   && $mybb->request_method != 'post')
{
    $google_seo_url_lazy = random_str(4);

    $db->google_seo_url[GOOGLE_SEO_ANNOUNCEMENT]['lazy'] = "announcements.php?aid={id}&amp;google_seo={$google_seo_url_lazy}";
    $db->google_seo_url[GOOGLE_SEO_CALENDAR]['lazy'] = "calendar.php?calendar={id}&amp;google_seo={$google_seo_url_lazy}";
    $db->google_seo_url[GOOGLE_SEO_EVENT]['lazy'] = "calendar.php?action=event&amp;eid={id}&amp;google_seo={$google_seo_url_lazy}";
    $db->google_seo_url[GOOGLE_SEO_FORUM]['lazy'] = "forumdisplay.php?fid={id}&amp;google_seo={$google_seo_url_lazy}";
    $db->google_seo_url[GOOGLE_SEO_THREAD]['lazy'] = "showthread.php?tid={id}&amp;google_seo={$google_seo_url_lazy}";
    $db->google_seo_url[GOOGLE_SEO_USER]['lazy'] = "member.php?action=profile&amp;uid={id}&amp;google_seo={$google_seo_url_lazy}";

    $google_seo_url_lazy = true;
}

// Thread Prefix

if($settings['google_seo_url_threadprefix'])
{
    $db->google_seo_url[GOOGLE_SEO_THREAD]['extra'] .= ',prefix';
}

// Parents

if($db->google_seo_url[GOOGLE_SEO_FORUM]['scheme'])
{
    if($settings['google_seo_url_parent_announcement'])
    {
        $db->google_seo_url[GOOGLE_SEO_ANNOUNCEMENT]['extra'] .= ',fid AS parent';
        $db->google_seo_url[GOOGLE_SEO_ANNOUNCEMENT]['parent'] = $settings['google_seo_url_parent_announcement'];
        $db->google_seo_url[GOOGLE_SEO_ANNOUNCEMENT]['parent_type'] = GOOGLE_SEO_FORUM;
    }

    if($settings['google_seo_url_parent_forum'])
    {
        $db->google_seo_url[GOOGLE_SEO_FORUM]['extra'] .= ',pid AS parent';
        $db->google_seo_url[GOOGLE_SEO_FORUM]['parent'] = $settings['google_seo_url_parent_forum'];
        $db->google_seo_url[GOOGLE_SEO_FORUM]['parent_type'] = GOOGLE_SEO_FORUM;
    }

    if($settings['google_seo_url_parent_thread'])
    {
        $db->google_seo_url[GOOGLE_SEO_THREAD]['extra'] .= ',fid AS parent';
        $db->google_seo_url[GOOGLE_SEO_THREAD]['parent'] = $settings['google_seo_url_parent_thread'];
        $db->google_seo_url[GOOGLE_SEO_THREAD]['parent_type'] = GOOGLE_SEO_FORUM;
    }
}

if($db->google_seo_url[GOOGLE_SEO_CALENDAR]['scheme']
   && $settings['google_seo_url_parent_event'])
{
    $db->google_seo_url[GOOGLE_SEO_EVENT]['extra'] .= ',cid AS parent';
    $db->google_seo_url[GOOGLE_SEO_EVENT]['parent'] = $settings['google_seo_url_parent_event'];
    $db->google_seo_url[GOOGLE_SEO_EVENT]['parent_type'] = GOOGLE_SEO_CALENDAR;
}

// Query limit. Decreases by 1 for every query.

$db->google_seo_query_limit = (int)$settings['google_seo_url_query_limit'];

if($db->google_seo_query_limit <= 0)
{
    $db->google_seo_query_limit = 32767;
}

// Cache

global $google_seo_url_cache;

if($settings['google_seo_url_cache'])
{
    $data = $cache->read('google_seo_url');

    if(is_array($data) && $data['time'] >= TIME_NOW)
    {
        $google_seo_url_cache = $data;
    }

    if(strpos($settings['google_seo_url_cache'], THIS_SCRIPT) !== false)
    {
        $plugins->add_hook("post_output_page", "google_seo_url_cache_hook", 100);
    }
}

// There are several more global variables defined in functions below.

/* --- Hooks: --- */

$plugins->add_hook("moderation_do_merge", "google_seo_url_merge_hook", 1);
$plugins->add_hook("class_moderation_merge_threads", "google_seo_url_after_merge_hook");

// Originally a global_start hook, it's called too late for session location
google_seo_url_hook();

/* --- URL processing: --- */

/**
 * Character translation for URLs (optional).
 *
 * @param string The input string
 * @return string The output string
 */
function google_seo_url_translation($str)
{
    // Required for URL translation.
    global $settings;
    global $google_seo_url_translation;

    if(!is_array($google_seo_url_translation))
    {
        // Build the translation array.
        $google_seo_translation = array();

        $lines = explode("\n", $settings['google_seo_url_translation']);

        foreach($lines as $line)
        {
            $fields = explode('=', $line);

            if(count($fields) == 2)
            {
                $key = trim($fields[0]);

                if(strlen($key))
                {
                    $value = trim($fields[1]);
                    $google_seo_url_translation[$key] = $value;
                }
            }
        }
    }

    if($google_seo_url_translation)
    {
        $str = strtr($str, $google_seo_url_translation);
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
    $str = $settings['google_seo_url_uniquifier'];

    $str = google_seo_expand($str, array('url' => $url,
                                         'id' => $id,
                                         'separator' => $separator));

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

    $url = google_seo_expand($scheme, array('url' => $url));

    $test = explode('?', $url);
    $test = explode('/', $test[0]);

    foreach($test as $element)
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

        if(strlen($element) >= 256)
        {
            return 0;
        }
    }

    $url = google_seo_encode($url);

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
    global $google_seo_url_cache;

    $ids = (array)$ids;

    foreach($ids as $id)
    {
        $google_seo_url_cache[$type][$id] = 0;
    }

    if($db->google_seo_query_limit <= 0)
    {
        return;
    }

    $data = $db->google_seo_url[$type];

    // Is Google SEO URL enabled for this type?
    if($data['scheme'])
    {
        // Query the item title as base for our URL.
        $db->google_seo_query_limit--;
        $titles = $db->query("SELECT {$data['name']},{$data['id']}{$data['extra']}
                              FROM {$data['table']}
                              WHERE {$data['id']} IN ("
                             .implode(",", $ids)
                             .")");

        while($row = $db->fetch_array($titles))
        {
            $url = $row[$data['name']];

            // MyBB unfortunately allows HTML in forum names.
            if($type == GOOGLE_SEO_FORUM)
            {
                $url = strip_tags($url);
            }

            // Thread Prefixes
            if($row['prefix'])
            {
                $prefix = build_prefixes($row['prefix']);

                if($prefix['prefix'])
                {
                    $url = google_seo_expand(
                        $settings['google_seo_url_threadprefix'],
                        array('url' => $url,
                              'prefix' => $prefix['prefix'],
                              'separator' => $settings['google_seo_url_separator']));
                }
            }

            $id = $row[$data['id']];

            // Prepare the URL.
            if($settings['google_seo_url_translation'])
            {
                $url = google_seo_url_translation($url);
            }

            $url = google_seo_url_separate($url);
            $url = google_seo_url_truncate($url);

            $uniqueurl = google_seo_url_uniquify($url, $id);

            // Special case: for empty URLs we must use the unique variant
            if($url == "" || $settings['google_seo_url_uniquifier_force'])
            {
                $url = $uniqueurl;
            }

            // Parents
            if($row['parent'])
            {
                $parent_type = $db->google_seo_url[$type]['parent_type'];
                $parent_id = (int)$row['parent'];

                // TODO: Parents costs us an extra query. Cache?
                $db->google_seo_query_limit--;
                $query = $db->simple_select('google_seo',
                                            'url AS parent',
                                            "active=1 AND idtype={$parent_type} AND id={$parent_id}");
                $parent = $db->fetch_field($query, 'parent');

                if($parent)
                {
                    $url = google_seo_expand($db->google_seo_url[$type]['parent'],
                                             array('url' => $url,
                                                   'parent' => $parent));
                    $uniqueurl = google_seo_expand($db->google_seo_url[$type]['parent'],
                                                   array('url' => $url,
                                                         'parent' => $parent));
                }
            }

            // Check for existing entry and possible collisions.
            $db->google_seo_query_limit--;
            $query = $db->query("SELECT url,id FROM ".TABLE_PREFIX."google_seo
                                 WHERE active=1 AND idtype={$type} AND id<={$id}
                                 AND url IN ('"
                                .$db->escape_string($url)."','"
                                .$db->escape_string($uniqueurl)."')
                                 AND EXISTS(SELECT {$data['id']}
                                            FROM {$data['table']}
                                            WHERE {$data['id']}=id)
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
                $db->google_seo_query_limit--;
                $db->write_query("UPDATE ".TABLE_PREFIX."google_seo
                                  SET active=NULL
                                  WHERE active=1
                                  AND idtype={$type}
                                  AND id={$id}");

                // Insert new entry (while possibly replacing old ones).
                $db->google_seo_query_limit--;
                $db->write_query("REPLACE INTO ".TABLE_PREFIX."google_seo
                                  VALUES (active,idtype,id,url),
                                  ('1','{$type}','{$id}','".$db->escape_string($url)."')");
            }

            // Finalize URL.
            if($settings['google_seo_url_lowercase'])
            {
                $url = my_strtolower($url);
            }

            $url = google_seo_url_finalize($url, $data['scheme']);

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
    global $db, $mybb, $settings, $cache;
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
                            $google_seo_url_optimize[GOOGLE_SEO_FORUM][$c['fid']] = 0;
                            $google_seo_url_optimize[GOOGLE_SEO_USER][$c['lastposteruid']] = 0;
                            $google_seo_url_optimize[GOOGLE_SEO_THREAD][$c['lastposttid']] = 0;
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
                $google_seo_url_optimize[GOOGLE_SEO_USER][$stats['lastuid']] = 0;

                // who's online
                if($mybb->settings['showwol'] != 0 && $mybb->usergroup['canviewonline'] != 0)
                {
                    $timesearch = TIME_NOW - $mybb->settings['wolcutoff'];
                    $db->google_seo_query_limit--;
                    $query = $db->query("SELECT uid FROM ".TABLE_PREFIX."sessions
                                         WHERE uid != '0' AND time > '$timesearch'");

                    while($user = $db->fetch_array($query))
                    {
                        $google_seo_url_optimize[GOOGLE_SEO_USER][$user['uid']] = 0;
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

                        $google_seo_url_optimize[GOOGLE_SEO_USER][$row['uid']] = 0;
                        $google_seo_url_optimize[GOOGLE_SEO_USER][$row['lastposteruid']] = 0;
                        $google_seo_url_optimize[GOOGLE_SEO_THREAD][$row['tid']] = 0;
                        $google_seo_url_optimize[GOOGLE_SEO_FORUM][$row['fid']] = 0;
                        $google_seo_url_optimize[GOOGLE_SEO_ANNOUNCEMENT][$row['aid']] = 0;
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

                        $google_seo_url_optimize[GOOGLE_SEO_USER][$row['uid']] = 0;
                        $google_seo_url_optimize[GOOGLE_SEO_USER][$row['lastposteruid']] = 0;
                        $google_seo_url_optimize[GOOGLE_SEO_THREAD][$row['tid']] = 0;
                        $google_seo_url_optimize[GOOGLE_SEO_FORUM][$row['fid']] = 0;
                        $google_seo_url_optimize[GOOGLE_SEO_ANNOUNCEMENT][$row['aid']] = 0;
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
                    $google_seo_url_optimize[GOOGLE_SEO_FORUM][$f['fid']] = 0;
                    $google_seo_url_optimize[GOOGLE_SEO_USER][$f['lastposteruid']] = 0;
                }
            }

            // threadcache optimization (forumdisplay.php)
            global $threadcache, $google_seo_threadcache;

            if($threadcache !== $google_seo_threadcache)
            {
                $GLOBALS['google_seo_threadcache'] =& $threadcache;

                foreach($threadcache as $t)
                {
                    $google_seo_url_optimize[GOOGLE_SEO_THREAD][$t['tid']] = 0;
                    $google_seo_url_optimize[GOOGLE_SEO_FORUM][$t['fid']] = 0;
                    $google_seo_url_optimize[GOOGLE_SEO_USER][$t['uid']] = 0;
                    $google_seo_url_optimize[GOOGLE_SEO_USER][$t['lastposteruid']] = 0;
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
                    $google_seo_url_optimize[GOOGLE_SEO_THREAD][$t['tid']] = 0;
                    $google_seo_url_optimize[GOOGLE_SEO_FORUM][$t['fid']] = 0;
                    $google_seo_url_optimize[GOOGLE_SEO_USER][$t['uid']] = 0;
                    $google_seo_url_optimize[GOOGLE_SEO_USER][$t['lastposteruid']] = 0;
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
                        $google_seo_url_optimize[GOOGLE_SEO_USER][$e['uid']] = 0;
                        $google_seo_url_optimize[GOOGLE_SEO_EVENT][$e['eid']] = 0;
                        $google_seo_url_optimize[GOOGLE_SEO_CALENDAR][$e['cid']] = 0;
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
                        $google_seo_url_optimize[GOOGLE_SEO_USER][$uid] = 0;
                    }
                }

                // aid_list
                if(count($aid_list) && $aid_list !== $google_seo_aid_list)
                {
                    $GLOBALS['google_seo_aid_list'] =& $aid_list;

                    foreach($aid_list as $aid)
                    {
                        $google_seo_url_optimize[GOOGLE_SEO_ANNOUNCEMENT][$aid] = 0;
                    }
                }

                // eid_list
                if(count($eid_list) && $eid_list !== $google_seo_eid_list)
                {
                    $GLOBALS['google_seo_eid_list'] =& $eid_list;

                    foreach($eid_list as $eid)
                    {
                        $google_seo_url_optimize[GOOGLE_SEO_EVENT][$eid] = 0;
                    }
                }

                // fid_list
                if(count($fid_list) && $fid_list !== $google_seo_fid_list)
                {
                    $GLOBALS['google_seo_fid_list'] =& $fid_list;

                    foreach($fid_list as $fid)
                    {
                        $google_seo_url_optimize[GOOGLE_SEO_FORUM][$fid] = 0;
                    }
                }

                // tid_list
                if(count($tid_list) && $tid_list !== $google_seo_tid_list)
                {
                    $GLOBALS['google_seo_tid_list'] =& $tid_list;

                    foreach($tid_list as $tid)
                    {
                        $google_seo_url_optimize[GOOGLE_SEO_THREAD][$tid] = 0;
                    }
                }
            }

            break;
    }

    // Include the ID that was originally requested.
    $google_seo_url_optimize[$type][$id] = 0;

    // Clean up
    foreach($google_seo_url_optimize as $key => $value)
    {
        if(!$db->google_seo_url[$key]['scheme'])
        {
            unset($google_seo_url_optimize[$key]);
        }

        unset($google_seo_url_optimize[$key][0]);
        unset($google_seo_url_optimize[$key]['']);
    }

    // return the collected IDs
    $result = $google_seo_url_optimize;
    $google_seo_url_optimize = array();

    return $result;
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
    global $db, $settings;
    global $google_seo_url_cache, $google_seo_url_lazy;

    // If it's not in the cache, try loading the cache.
    if($google_seo_url_cache[$type][$id] === NULL
        && $db->google_seo_query_limit > 0)
    {
        $google_seo_url_cache['dirty'] = 1;

        // Special case: Lazy Mode
        if($google_seo_url_lazy !== false)
        {
            if($google_seo_url_lazy === true)
            {
                // first time call
                global $plugins;
                $plugins->add_hook('pre_output_page', 'google_seo_url_lazy', 1000);
                ob_start('google_seo_url_lazy');
                register_shutdown_function(create_function('', 'while(@ob_end_flush());'));
                $google_seo_url_lazy = array();
            }

            // Create a temporary placeholder (but working) URL.
            $url = google_seo_expand(
                $db->google_seo_url[$type]['lazy'],
                array('id' => $id)
                );

            // strtr is SO much faster if all strings have the exact same length
            // 64 is the longest lazy url assuming 10 digit ids
            $url = str_pad($url, 64, "+");

            $google_seo_url_cache[$type][$id] = $url;
            $google_seo_url_lazy[$type][$id] = $url;

            return $url;
        }

        // Full Mode

        // Prepare database query.
        if($settings["google_seo_url_lowercase"])
        {
            $what = "LOWER(url) AS url,id,idtype";
        }

        else
        {
            $what = "url,id,idtype";
        }

        // Optimize by collecting more IDs for this query.
        $ids = google_seo_url_optimize($type, $id);

        $condition = array();

        foreach($ids as $key => $value)
        {
            if($value)
            {
                $condition[] = "(idtype={$key} AND id IN ("
                    .implode(",", array_keys($value)).
                    "))";
            }
        }

        $condition = implode(" OR ", $condition);

        // Run database query.
        $db->google_seo_query_limit--;
        $query = $db->query("SELECT $what
                             FROM ".TABLE_PREFIX."google_seo
                             WHERE active=1
                             AND ({$condition})");

        // Process the query results.
        while($row = $db->fetch_array($query))
        {
            $rowid = (int)$row['id'];
            $rowtype = (int)$row['idtype'];
            $google_seo_url_cache[$rowtype][$rowid] =
                google_seo_url_finalize($row['url'],
                                        $db->google_seo_url[$rowtype]['scheme']);
            unset($ids[$rowtype][$rowid]);
        }

        // Create URLs for the remaining IDs.
        foreach($ids as $key => $value)
        {
            unset($value[0]);

            if(count($value))
            {
                google_seo_url_create($key, array_keys($value));
            }
        }
    }

    // Return the cached entry for the originally requested type and id.
    return $google_seo_url_cache[$type][$id];
}

/*
 * Lazy Mode
 */
function google_seo_url_lazy($message)
{
    global $plugins;
    global $google_seo_url_lazy, $google_seo_url_optimize, $google_seo_url_cache;

    if(!$google_seo_url_lazy)
    {
        // Do nothing.
        return $message;
    }

    $lazy = $google_seo_url_lazy;
    $google_seo_url_lazy = false;

    // We've been lazy and now we have to pay the price.

    // Prepare cache and optimize.
    foreach($lazy as $key => $value)
    {
        // substract lazy from cache so it will redo these URLs
        $google_seo_url_cache[$key] = array_diff_key((array)$google_seo_url_cache[$key],
                                                     (array)$lazy[$key]);
        // add lazy to optimize so it will use a single query when redoing them
        $google_seo_url_optimize[$key] = (array)$google_seo_url_optimize[$key] + (array)$lazy[$key];
    }

    // Make it load the URLs.
    reset($lazy);
    $type = key($lazy);
    reset($lazy[$type]);
    $id = key($lazy[$type]);

    google_seo_url_cache($type, $id);

    // Build the replacement foo.
    $strtr = array();

    foreach($lazy as $type => $list)
    {
        foreach($list as $id => $url)
        {
            $seourl = $google_seo_url_cache[$type][$id];

            if($seourl)
            {
                $strtr[$url] = $seourl;
            }
        }
    }

    // This is the most expensive bit.
    $message = strtr($message, $strtr);

    return $message;
}

/*
 * Populate MyBB's Cache
 */
function google_seo_url_cache_hook()
{
    global $settings, $cache;
    global $google_seo_url_cache;

    // Do we need to update the cache?
    if($google_seo_url_cache['dirty'])
    {
        unset($google_seo_url_cache['dirty']);

        // New cache?
        if(!$google_seo_url_cache['time'])
        {
            $delta = (int)$settings['google_seo_url_cache'];

            if(!$delta)
            {
                // Default: 15 Minutes
                $delta = 15;
            }

            $google_seo_url_cache['time'] = TIME_NOW + $delta * 60;
        }

        $cache->update('google_seo_url', $google_seo_url_cache);
    }
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
    global $db, $settings;

    $db->google_seo_query_limit--;
    $query = $db->query("SELECT id
                         FROM ".TABLE_PREFIX."google_seo
                         WHERE idtype={$type}
                         AND url='".$db->escape_string($url)."'");

    $id = $db->fetch_field($query, "id");

    if(!$id)
    {
        // Fallback for wrong punctuation, character translation:
        $urls[0] = $db->escape_string(google_seo_url_separate($url));

        if($settings['google_seo_url_translation'])
        {
            $urls[1] = $db->escape_string(google_seo_url_translation($url));
            $urls[2] = $db->escape_string(google_seo_url_separate($urls[1]));
        }

        $db->google_seo_query_limit--;
        $query = $db->query("SELECT id
                             FROM ".TABLE_PREFIX."google_seo
                             WHERE idtype={$type}
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

/*
 * Dynamic URLs, e.g. showthread.php?Subject-Here
 *
 */
function google_seo_url_dynamic($url='')
{
    if(!strlen((string)$url))
    {
        $url = google_seo_dynamic($_SERVER['REQUEST_URI']);
    }

    return $url;
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
    global $db, $mybb, $settings;

    // Translate URL name to ID and verify.
    switch(THIS_SCRIPT)
    {
        case 'forumdisplay.php':
            // Translation.
            $url = google_seo_url_dynamic($mybb->input['google_seo_forum']);

            if(strlen($url) && !array_key_exists('fid', $mybb->input))
            {
                $fid = google_seo_url_id(GOOGLE_SEO_FORUM, $url);
                $mybb->input['fid'] = $fid;
                $location = get_current_location();
                $location = str_replace("google_seo_forum={$url}",
                                        "fid={$fid}", $location);
            }

            // Verification.
            $fid = (int)$mybb->input['fid'];

            if($fid)
            {
                google_seo_url_create(GOOGLE_SEO_FORUM, $fid);
            }

            break;

        case 'showthread.php':
            // Translation.
            $url = google_seo_url_dynamic($mybb->input['google_seo_thread']);

            if(strlen($url) && !array_key_exists('tid', $mybb->input))
            {
                $tid = google_seo_url_id(GOOGLE_SEO_THREAD, $url);
                $mybb->input['tid'] = $tid;
                $location = get_current_location();
                $location = str_replace("google_seo_thread={$url}",
                                        "tid={$tid}", $location);
            }

            // Verification.
            $tid = (int)$mybb->input['tid'];

            if($tid)
            {
                google_seo_url_create(GOOGLE_SEO_THREAD, $tid);
            }

            $pid = $mybb->input['pid'];

            break;

        case 'announcements.php':
            // Translation.
            $url = google_seo_url_dynamic($mybb->input['google_seo_announcement']);

            if(strlen($url) && !array_key_exists('aid', $mybb->input))
            {
                $aid = google_seo_url_id(GOOGLE_SEO_ANNOUNCEMENT, $url);
                $mybb->input['aid'] = $aid;
                $location = get_current_location();
                $location = str_replace("google_seo_announcement={$url}", "aid={$aid}", $location);
            }

            // Verification.
            $aid = (int)$mybb->input['aid'];

            if($aid)
            {
                google_seo_url_create(GOOGLE_SEO_ANNOUNCEMENT, $aid);
            }

            break;

        case 'member.php':
            // Translation.
            $url = google_seo_url_dynamic($mybb->input['google_seo_user']);

            if(strlen($url) && !array_key_exists('uid', $mybb->input))
            {
                $uid = google_seo_url_id(GOOGLE_SEO_USER, $url);
                $mybb->input['uid'] = $uid;
                $location = get_current_location();
                $location = str_replace("google_seo_user={$url}", "uid={$uid}", $location);
            }

            // Verification.
            $uid = (int)$mybb->input['uid'];

            if($uid && $mybb->input['action'] == 'profile')
            {
                google_seo_url_create(GOOGLE_SEO_USER, $uid);
            }

            break;

        case 'calendar.php':
            // Translation.
            // Event.
            $url = google_seo_url_dynamic($mybb->input['google_seo_event']);

            if(strlen($url) && !array_key_exists('eid', $mybb->input))
            {
                $eid = google_seo_url_id(GOOGLE_SEO_EVENT, $url);
                $mybb->input['eid'] = $eid;
                $location = get_current_location();
                $location = str_replace("google_seo_event={$url}", "eid={$eid}", $location);
            }

            // Verification.
            $eid = (int)$mybb->input['eid'];

            if($eid)
            {
                google_seo_url_create(GOOGLE_SEO_EVENT, $eid);
            }

            else
            {
                // Calendar.
                $url = google_seo_url_dynamic($mybb->input['google_seo_calendar']);

                if(strlen($url) && !array_key_exists('calendar', $mybb->input))
                {
                    $cid = google_seo_url_id(GOOGLE_SEO_CALENDAR, $url);

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
                }

                // Verification.
                $cid = (int)$mybb->input['calendar'];

                if($cid)
                {
                    google_seo_url_create(GOOGLE_SEO_CALENDAR, $cid);
                }
            }

            break;
    }

    if($location)
    {
        $location = substr($location, 0, 150);

        global $google_seo_location;

        $google_seo_location = $location;
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
        $regexp = preg_quote($regexp, '#');
        $regexp = str_replace('\\{\\$url\\}', '([^./]+)', $regexp);
        $regexp = str_replace('\\{url\\}', '([^./]+)', $regexp);
        $regexp = "#^{$regexp}$#u";
    }

    // Fetch the (presumably) Google SEO URL:
    $url = $mybb->input['threadurl'];

    // $url can be either 'http://host/Thread-foobar' or just 'foobar'.

    // Kill anchors and parameters.
    $url = preg_replace('/^([^#?]*)[#?].*$/u', '\\1', $url);

    // Extract the name part of the URL.
    $url = preg_replace($regexp, '\\1', $url);

    // Unquote the URL.
    $url = urldecode($url);

    // If $url was 'http://host/Thread-foobar', it is just 'foobar' now.

    // Look up the ID for this item.
    $tid = google_seo_url_id(GOOGLE_SEO_THREAD, $url);

    // If we have an ID, produce an URL suitable for merge.
    if($tid)
    {
        $mybb->input['threadurl'] = "{$mybb->settings['bburl']}/showthread.php?tid={$tid}";
    }
}

/**
 *
 * Google SEO After Merge hook.
 *
 * When two threads are merged, one of them is actually deleted.
 * As a result, all URLs pointing to the old thread would be lost.
 *
 * However if we merge the URL entries in the google_seo table as well,
 * Google SEO Redirect can redirect URLs of the old thread to the new thread.
 *
 */
function google_seo_url_after_merge_hook($arguments)
{
    global $db;

    $mergetid = (int)$arguments['mergetid'];
    $tid = (int)$arguments['tid'];

    // Integrate mergetid into tid:
    $type = GOOGLE_SEO_THREAD;
    $db->google_seo_query_limit--;
    $db->write_query("UPDATE ".TABLE_PREFIX."google_seo
                      SET active=NULL, id={$tid}
                      WHERE idtype={$type} AND id={$mergetid}");
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
        return google_seo_url_cache(GOOGLE_SEO_USER, $uid);
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
        return google_seo_url_cache(GOOGLE_SEO_ANNOUNCEMENT, $aid);
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
        $url = google_seo_url_cache(GOOGLE_SEO_FORUM, $fid);

        if($url && $page && $page != 1)
        {
            $glue = (strpos($url, '?') === false ? '?' : '&amp;');
            $url .= "{$glue}page={$page}";
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
        $url = google_seo_url_cache(GOOGLE_SEO_THREAD, $tid);

        if($url)
        {
            $glue = (strpos($url, '?') === false ? '?' : '&amp;');

            if($page && $page != 1 && $action)
            {
                $url .= "{$glue}page={$page}&amp;action={$action}";
            }

            else if($page && $page != 1)
            {
                $url .= "{$glue}page={$page}";
            }

            else if($action)
            {
                $url .= "{$glue}action={$action}";
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
    global $db, $settings;
    global $google_seo_url_tid;

    if($settings['google_seo_url_threads'] && $pid > 0)
    {
        $tid = google_seo_tid($pid, $tid, $settings['google_seo_url_posts'],
                              $db->google_seo_query_limit);

        if($tid > 0)
        {
            $url = google_seo_url_cache(GOOGLE_SEO_THREAD, $tid);

            if($url)
            {
                $glue = (strpos($url, '?') === false ? '?' : '&amp;');
                $url .= "{$glue}pid={$pid}";
                return $url;
            }
        }
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
        return google_seo_url_cache(GOOGLE_SEO_EVENT, $eid);
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
        $url = google_seo_url_cache(GOOGLE_SEO_CALENDAR, $cid);

        if($url && $year)
        {
            $glue = (strpos($url, '?') === false ? '?' : '&amp;');
            $url .= "{$glue}year={$year}";

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
        $url = google_seo_url_cache(GOOGLE_SEO_CALENDAR, $cid);

        if($url)
        {
            $glue = (strpos($url, '?') === false ? '?' : '&amp;');
            $url .= "{$glue}action=weekview&amp;week={$week}";
            return $url;
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
        if($url == $urlcheck
           || strpos($url, $urlcheck.'?') === 0
           || strpos($url, $urlcheck.'&amp;') === 0)
        {
            // Check that the Google SEO URLs are being used.
            $seourl = $getlink_googleseo($id, "{page}");

            if($seourl == $getlink($id, "{page}"))
            {
                // Replace it with the Google SEO URL
                $newurl = $seourl;

                // Append extra parameters.
                $extra = substr($url, strlen($urlcheck));

                if($extra)
                {
                    $newurl .= $extra;
                }
            }
        }
    }

    return $newurl;
}

/* --- End of file. --- */
?>
