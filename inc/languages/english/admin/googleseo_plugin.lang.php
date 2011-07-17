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

$l['googleseo_plugin_404'] = "404";
$l['googleseo_plugin_description'] = "Google Search Engine Optimization as described in the official <a href=\"http://www.google.com/webmasters/docs/search-engine-optimization-starter-guide.pdf\">Google's SEO starter guide</a>. Please see the <a href=\"../inc/plugins/google_seo.txt\">documentation</a> for details.";
$l['googleseo_plugin_error'] = "{1} is disabled. (<a href=\"index.php?module=config&amp;action=change&amp;search=google%20seo\">Configure</a>)";
$l['googleseo_plugin_error_plural'] = "{1} are disabled. (<a href=\"index.php?module=config&amp;action=change&amp;search=google%20seo\">Configure</a>)";
$l['googleseo_plugin_htaccess_404'] = "Google SEO 404";
$l['googleseo_plugin_htaccess_announcements'] = "Google SEO URL Announcements";
$l['googleseo_plugin_htaccess_calendars'] = "Google SEO URL Calendars";
$l['googleseo_plugin_htaccess_events'] = "Google SEO URL Events";
$l['googleseo_plugin_htaccess_forums'] = 'Google SEO URL Forums';
$l['googleseo_plugin_htaccess_search'] = "Google SEO workaround for search.php highlights:";
$l['googleseo_plugin_htaccess_search_first'] = "Make this rule the first rewrite rule in your .htaccess!";
$l['googleseo_plugin_htaccess_threads'] = "Google SEO URL Threads";
$l['googleseo_plugin_htaccess_users'] = "Google SEO URL Users";
$l['googleseo_plugin_list'] = "{1}, {2}";
$l['googleseo_plugin_list_final'] = "{1} and {2}";
$l['googleseo_plugin_meta'] = "Meta";
$l['googleseo_plugin_mybb_old'] = "Your copy of MyBB is too old for this version of Google SEO. Please update MyBB!";
$l['googleseo_plugin_pl_missing'] = 'Google SEO requires <a href="http://mods.mybb.com/view/pluginlibrary">PluginLibrary</a>. Please download and install it.';
$l['googleseo_plugin_pl_old'] = 'Your <a href="http://mods.mybb.com/view/pluginlibrary">PluginLibrary</a> is too old. Please download and install the new version.';
$l['googleseo_plugin_redirect'] = "Redirect";
$l['googleseo_plugin_redirect_warn_bburl'] = "Board URL is set to '{1}', but you currently seem to be on '{2}'. A wrong Board URL setting may cause problems with Redirect.";
$l['googleseo_plugin_redirect_warn_url'] = "Redirect enabled, but URL disabled. This is fine for redirecting stock MyBB URLs (showthread.php?tid=x) to MyBB search engine friendly URLs (thread-x.html) or vice versa. If you want to redirect stock MyBB URLs to Google SEO URLs or vice versa, please enable URL as well.";
$l['googleseo_plugin_sitemap'] = "Sitemap";
$l['googleseo_plugin_success'] = "{1} is enabled.";
$l['googleseo_plugin_success_plural'] = "{1} are enabled.";
$l['googleseo_plugin_url'] = "URL";
$l['googleseo_plugin_url_warn_functions'] = "Modifications to inc/functions.php are required for URL support. Please see the <a href=\"../inc/plugins/google_seo.txt\">documentation</a> for details.";
$l['googleseo_plugin_url_warn_translate'] = "inc/plugins/google_seo/translate.php is required for URL translation. Please see the <a href=\"../inc/plugins/google_seo.txt\">documentation</a> for details.";
$l['googleseo_plugin_warn_htaccess'] = "Add to .htaccess:";
$l['googleseo_plugin_warn_mbstring'] = "Your host does not seem to support mbstring. This may cause problems with UTF-8.";
$l['googleseo_plugin_warning_encoding'] = "Your database encoding is '{1}', should be 'utf8'. Please update your MyBB to use UTF-8 everywhere.";

?>