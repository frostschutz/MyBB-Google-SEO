<?php
/**
 * This file is part of Google SEO plugin for MyBB.
 * Copyright (C) 2008, 2009, 2010 Andreas Klauer <Andreas.Klauer@metamorpher.de>
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

/* --- NO_ONLINE Hack: --- */

/**
 * Hooks are called too late, so this is done before hooks:
 *
 * Define NO_ONLINE if it looks like we're going to show an 404 error,
 * unless the user explicitely wants these errors to show in the WOL.
 *
 */

global $mybb;

if(THIS_SCRIPT == "misc.php"
   && $mybb->input['google_seo_error'] == "404")
{
    if($mybb->settings['google_seo_404_wol_show'] == 0)
    {
        define("NO_ONLINE", 1);
    }

    else if(!defined("MYBB_LOCATION"))
    {
        // Include the reason for the 404 error in the location information
        define("MYBB_LOCATION",
               substr('misc.php?google_seo_error=404&amp;uri='
                      .urlencode($_SERVER['REQUEST_URI']),
                      0, 150));
    }
}

/* --- Hooks: --- */

// Set HTTP 404 status code, add widget to error pages:
$plugins->add_hook("error", "google_seo_404");

// Custom 404 error page:
$plugins->add_hook("misc_start", "google_seo_404_page");

// WOL extension for custom page:
$plugins->add_hook("build_friendly_wol_location_end", "google_seo_404_wol");

/* --- 404 error handling: --- */

/**
 * Set the 404 header, add Google 404 widget, if enabled.
 *
 * @param string Error message, which may be modified.
 */
function google_seo_404($error)
{
    global $lang, $settings, $mybb;

    if(!$mybb->input['ajax'])
    {
        // Technically, this is incorrect, as it also hits error messages
        // that are intended to occur. But there is no good way of detecting
        // all cases that should be 404 (error due to bad link) and the user
        // gets to see the same page either way.

        // As a side effect, 404 erroring all error pages gives you a list
        // in Google's Webmaster tools of pages that Google shouldn't access
        // and therefore should be disallowed in robots.txt.

        @header("HTTP/1.1 404 Not Found");

        if($settings['google_seo_404_widget'])
        {
            $error .= $lang->sprintf($lang->googleseo_404_widget,
                                     $settings['bburl']);
        }
    }
}

/* ---  Custom 404 error page --- */

/**
 * Create a custom 404 error page for errors that occur outside of MyBB.
 *
 */
function google_seo_404_page()
{
    global $lang, $mybb, $session;

    if($mybb->input['google_seo_error'] == 404)
    {
        error($lang->googleseo_404_notfound);
    }
}

/* --- WOL --- */

/**
 * Extend WOL for users on the 404 page.
 *
 */
function google_seo_404_wol($p)
{
    global $lang, $user, $settings;

    // Check if this user is on a 404 page.
    if(strpos($p['user_activity']['location'], "misc.php?google_seo_error=404") === 0)
    {
        $p['user_activity']['activity'] = 'google_seo_404';
        $location = $p['user_activity']['location'];
        $p['location_name'] = $lang->sprintf($lang->googleseo_404_wol,
                                             $location);
    }
}

/* --- End of file. --- */
?>
