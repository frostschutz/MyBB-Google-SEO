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

$l['setting_group_google_seo_404'] = "Google SEO 404";
$l['setting_group_google_seo_404_desc'] = "404 error page settings for the Google Search Engine Optimization plugin.";
$l['setting_google_seo_404'] = "Google SEO 404";
$l['setting_google_seo_404_desc'] = "This module replaces the <i>HTTP 200 OK</i> response with <i>HTTP 404 Not Found</i> for invalid thread / forum / etc error pages and provides additional functionality for 404 error pages. You can also do custom 404 error pages by adding an ErrorPage directive to your .htaccess. Please see the <a href=\"../inc/plugins/google_seo.txt\">documentation</a> for details.<br /><br />Set to YES to enable Google SEO 404. Setting this to NO also disables all other settings in this group.";
$l['setting_google_seo_404_widget'] = "404 widget";
$l['setting_google_seo_404_widget_desc'] = "Add the Google 404 widget for invalid thread / forum / etc error pages.";
$l['setting_google_seo_404_wol_show'] = "Show 404 errors in Who's Online";
$l['setting_google_seo_404_wol_show_desc'] = "Specify if you want to show that users are seeing the 404 error page in the Who's Online list. This is not recommended. Enabling this can cause problems such as spambots showing up as guests, or users showing up as seeing error pages if your forum e.g. tries to include an image that does not exist.";
$l['setting_group_google_seo_meta'] = "Google SEO Meta";
$l['setting_group_google_seo_meta_desc'] = "Meta tag settings for the Google Search Engine Optimization plugin.";
$l['setting_google_seo_meta'] = "Google SEO Meta";
$l['setting_google_seo_meta_desc'] = "This module generates meta tags for the current page. Please see the <a href=\"../inc/plugins/google_seo.txt\">documentation</a> for details.<br /><br />Set to YES to enable Google SEO Meta. Setting this to NO also disables all other settings in this group.";
$l['setting_google_seo_meta_length'] = "Meta description";
$l['setting_google_seo_meta_length_desc'] = "Generate Meta description tags based on the contents of the current page (description of a forum, first posting of a thread, ...). Set to the maximum description length you want to allow or to 0 to disable.";
$l['setting_google_seo_meta_canonical'] = "Canonical Page";
$l['setting_google_seo_meta_canonical_desc'] = "Specify a canonical page. This helps avoid Google indexing the same page under several different names. Please see <a href=\"http://www.google.com/support/webmasters/bin/answer.py?hl=en&amp;answer=139394\">About rel=\"canonical\"</a> for details.";
$l['setting_google_seo_meta_archive'] = "Add tags to Archive";
$l['setting_google_seo_meta_archive_desc'] = "Enable this option if you want tags to be added to MyBB's Lite (Archive) Mode pages by using unconventional methods.";
$l['setting_group_google_seo_redirect'] = "Google SEO Redirect";
$l['setting_group_google_seo_redirect_desc'] = "Redirection settings for the Google Search Engine Optimization plugin.";
$l['setting_google_seo_redirect'] = "Google SEO Redirect";
$l['setting_google_seo_redirect_desc'] = "This module redirects old and invalid URLs to their current proper names. This can be used for all sorts of redirections: redirect to the main site if your forum is available under several domain names, redirect stock MyBB URLs to Google SEO URLs (or the other way around). This prevents your users and Google from seeing the same page under several different names. Please see the <a href=\"../inc/plugins/google_seo.txt\">documentation</a> for details.<br /><br />Set to YES to enable Google SEO Redirect. Setting this to NO also disables all other settings in this group.";
$l['setting_google_seo_redirect_permission'] = "Permission Checks";
$l['setting_google_seo_redirect_permission_desc'] = "Should Redirect let permission checks run first? Enabling this option will prevent Redirect from redirecting URLs for items that the user is not allowed to access anyway. This is probably only necessary if you're also using SEO URLs and you're concerned about users getting redirected to the SEO URL of a forum / thread they're not allowed to read, which would give away the subject in the SEO URL.";
$l['setting_google_seo_redirect_debug'] = "Debug Redirect";
$l['setting_google_seo_redirect_debug_desc'] = "If you experience infinite redirection loops due to Google SEO Redirect, please enable this option to obtain more information about what is going wrong with your redirect and then report a bug to the plugin author. The debug information is ugly and therefore shown only to board admins.";
$l['setting_group_google_seo_sitemap'] = "Google SEO Sitemap";
$l['setting_group_google_seo_sitemap_desc'] = "Sitemap settings for the Google Search Engine Optimization plugin.";
$l['setting_google_seo_sitemap'] = "Google SEO Sitemap";
$l['setting_google_seo_sitemap_desc'] = "This module provides <a href=\"http://sitemaps.org/\">XML Sitemap</a> for your forum. This makes it easier for Google to discover pages on your site. Please see the <a href=\"../inc/plugins/google_seo.txt\">documentation</a> for details.<br /><br />Set to YES to enable Google SEO Sitemap. Setting this to NO also disables all other settings in this group.";
$l['setting_google_seo_sitemap_url'] = "XML Sitemap URL scheme";
$l['setting_google_seo_sitemap_url_desc'] = "This is the URL scheme used for the XML Sitemap pages. By default, this is <i>sitemap-{\$url}.xml</i> and your sitemap will be called <i>sitemap-index.xml</i>. Please note that if you change this, you will also need to add a new rewrite rule to your .htaccess. If your host does not support mod_rewrite, leave this empty. Your sitemap will then be called <i>misc.php?google_seo_sitemap=index</i>.";
$l['setting_google_seo_sitemap_forums'] = "XML Sitemap Forums";
$l['setting_google_seo_sitemap_forums_desc'] = "Include Forums in the XML Sitemap.";
$l['setting_google_seo_sitemap_threads'] = "XML Sitemap Threads";
$l['setting_google_seo_sitemap_threads_desc'] = "Include Threads in the XML Sitemap.";
$l['setting_google_seo_sitemap_users'] = "XML Sitemap Users";
$l['setting_google_seo_sitemap_users_desc'] = "Include Users in the XML Sitemap.";
$l['setting_google_seo_sitemap_announcements'] = "XML Sitemap Announcements";
$l['setting_google_seo_sitemap_announcements_desc'] = "Include Announcements in the XML Sitemap.";
$l['setting_google_seo_sitemap_calendars'] = "XML Sitemap Calendars";
$l['setting_google_seo_sitemap_calendars_desc'] = "Include Calendars in the XML Sitemap.";
$l['setting_google_seo_sitemap_events'] = "XML Sitemap Events";
$l['setting_google_seo_sitemap_events_desc'] = "Include Events in the XML Sitemap.";
$l['setting_google_seo_sitemap_additional'] = "XML Sitemap additional pages";
$l['setting_google_seo_sitemap_additional_desc'] = "List of additional URLs relative to your site that should be included in the XML Sitemap. If you have any custom pages you can include them here, one page per line. Entries must be relative to your site, i.e. they must not contain http://, and must not start with .. or /.";
$l['setting_google_seo_sitemap_pagination'] = "XML Sitemap pagination";
$l['setting_google_seo_sitemap_pagination_desc'] = "Set the maximum number of items that may appear in a single XML Sitemap before it is split (not counting optional forum/thread pages). Setting it too low will result in too many sitemaps, setting it too high may cause server load every time the sitemap is generated. If unsure, leave at 1000.";
$l['setting_group_google_seo_url'] = "Google SEO URL";
$l['setting_group_google_seo_url_desc'] = "URL settings for the Google Search Engine Optimization plugin.";
$l['setting_google_seo_url'] = "Enable Google SEO URLs";
$l['setting_google_seo_url_desc'] = "This module replaces the stock MyBB URLs with descriptive URLs that use words (thread subject, forum title, user name, etc) instead of random numeric IDs. Please see the <a href=\"../inc/plugins/google_seo.txt\">documentation</a> for details.<br /><br />Set to YES to enable Google SEO URL. Setting this to NO also disables all other settings in this group.";
$l['setting_google_seo_url_punctuation'] = "Punctuation characters";
$l['setting_google_seo_url_punctuation_desc'] = "Punctuation and other special characters are filtered from the URL string and replaced by the separator. By default, this string contains all special ASCII characters including space. If you are running an international forum with non-ASCII script, you might want to add unwanted punctuation characters of those scripts here.";
$l['setting_google_seo_url_separator'] = "URL separator";
$l['setting_google_seo_url_separator_desc'] = "Enter the separator that should be used to separate words in the URLs. By default this is - which is a good choice as it is easy to type in most keyboard layouts (single keypress without shift/alt modifier). If you want some other character or string as a separator, you can enter it here. Please note that special characters like :&amp;?@/ or space could render your URLs unuseable or hard to work with.";
$l['setting_google_seo_url_uniquifier'] = "URL uniquifier";
$l['setting_google_seo_url_uniquifier_desc'] = "In case of URL collisions (for example two threads with the same title), the uniquifier is applied to the URL of the newer thread. To guarantee uniqueness, the uniquifier must incorporate the ID and use punctuation other than a single separator. Please see the <a href=\"../inc/plugins/google_seo.txt\">documentation</a> for examples of good and bad uniquifiers.";
$l['setting_google_seo_url_uniquifier_force'] = "URL uniquifier enforcer";
$l['setting_google_seo_url_uniquifier_force_desc'] = "This option is NOT recommended. If you set this to yes, Google SEO will be forced to use the uniquifier for all URLs without exception, even if it's not necessary. Use this only if you absolutely want every URL to contain the ID. If you enable this, a single separator will be sufficient for the uniquifier.";
$l['setting_google_seo_url_translate'] = "Character Translation";
$l['setting_google_seo_url_translate_desc'] = "If you want to replace some characters (German umlaut example: Übergrößenträger =&gt; Uebergroessentraeger) or words in your URLs, please add your translations to <i>inc/plugins/google_seo/translate.php</i> and then enable this option. Please see the <a href=\"../inc/plugins/google_seo.txt\">documentation</a> for details.";
$l['setting_google_seo_url_lowercase'] = "lowercase words";
$l['setting_google_seo_url_lowercase_desc'] = "If you prefer lower case URLs, you can set this to YES. This will not affect the way URLs are stored in the database so you can go back to the original case letters any time. Please note that if you set this to YES, you will also have to make sure that your forum URL, as well as scheme and uniqufier are all lowercase too for the URL to be completely in lower case.";
$l['setting_google_seo_url_length_soft'] = "URL length soft limit";
$l['setting_google_seo_url_length_soft_desc'] = "URLs can be shortened after a soft limit by truncating it after a word (punctuation separator). Set to 0 to disable.";
$l['setting_google_seo_url_length_hard'] = "URL length hard limit";
$l['setting_google_seo_url_length_hard_desc'] = "URLs can be shortened after a hard limit by truncating it regardless of word separators. Set to 0 to disable.";
$l['setting_google_seo_url_forums'] = "Forum URL scheme";
$l['setting_google_seo_url_forums_desc'] = "Enter the Forum URL scheme. By default this is <i>Forum-{\$url}</i>. Please note that if you change this, you will also need to add a new rewrite rule in your .htaccess file. Leave empty to disable Google SEO URLs for Forums.";
$l['setting_google_seo_url_threads'] = "Thread URL scheme";
$l['setting_google_seo_url_threads_desc'] = "Enter the Thread URL scheme. By default this is <i>Thread-{\$url}</i>. Please note that if you change this, you will also need to add a new rewrite rule in your .htaccess file. Leave empty to disable Google SEO URLs for Threads.";
$l['setting_google_seo_url_announcements'] = "Announcement URL scheme";
$l['setting_google_seo_url_announcements_desc'] = "Enter the Announcement URL scheme. By default this is <i>Announcement-{\$url}</i>. Please note that if you change this, you will also need to add a new rewrite rule in your .htaccess file. Leave empty to disable Google SEO URLs for Announcements.";
$l['setting_google_seo_url_users'] = "User URL scheme";
$l['setting_google_seo_url_users_desc'] = "Enter the User URL scheme. By default this is <i>User-{\$url}</i>. Please note that if you change this, you will also need to add a new rewrite rule in your .htaccess file. Leave empty to disable Google SEO URLs for Users.";
$l['setting_google_seo_url_calendars'] = "Calendar URL scheme";
$l['setting_google_seo_url_calendars_desc'] = "Enter the Calendar URL scheme. By default this is <i>Calendar-{\$url}</i>. Please note that if you change this, you will also need to add a new rewrite rule in your .htaccess file. Leave empty to disable Google SEO URLs for Calendars.";
$l['setting_google_seo_url_events'] = "Event URL scheme";
$l['setting_google_seo_url_events_desc'] = "Enter the Event URL scheme. By default this is <i>Event-{\$url}</i>. Please note that if you change this, you will also need to add a new rewrite rule in your .htaccess file. Leave empty to disable Google SEO URLs for Events.";

?>