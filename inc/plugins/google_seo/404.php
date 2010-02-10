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

/* --- 404 error handling: --- */

// 404 error handling if the user wants this.
$plugins->add_hook("error", "google_seo_404");

function google_seo_404($error)
{
    global $settings, $mybb;

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
            $error .= "\n <script type=\"text/javascript\">\n"
                ." <!--\n"
                ." var GOOG_FIXURL_LANG='{$settings['google_seo_404_lang']}';\n"
                ." var GOOG_FIXURL_SITE='{$settings['bburl']}';\n"
                ." -->\n"
                ." </script>\n"
                ." <script type=\"text/javascript\" src=\""
                ."http://linkhelp.clients.google.com/tbproxy/lh/wm/fixurl.js"
                ."\"></script>\n";
        }
    }
}

// Custom 404 error pages:
$plugins->add_hook("misc_start", "google_seo_404_page");

function google_seo_404_page()
{
    global $mybb;

    if($mybb->input['google_seo_error'] == 404)
    {
        error("404 Not Found");
    }
}

/* --- End of file. --- */
?>