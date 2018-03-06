==================================================
 Google Search Engine Optimization Plugin for MyBB
==================================================

End User Manual
===============

:Author: Andreas Klauer <Andreas.Klauer@metamorpher.de>
:Version: 1.8.2 of 2018 Feb 4
:Homepage: https://github.com/frostschutz/MyBB-Google-SEO

.. contents::
  :backlinks: top

About
-----

The development of this plugin started when Google released a document called
"`Google's Search Engine Optimization starter guide`__" in November 2008.
After a long public beta phase the plugin finally reached stable status in
March 2009. A new, revised edition for MyBB 1.6.4 and newer was released
in August 2011.

__ https://www.google.com/webmasters/docs/search-engine-optimization-starter-guide.pdf

The goal of this plugin is to implement Search Engine Optimization (SEO)
in MyBB according to the guidelines presented in `Google's SEO starter guide`__
to make MyBB more friendly to both users and search engines.

__ https://www.google.com/webmasters/docs/search-engine-optimization-starter-guide.pdf

*Google SEO* is free open source software (LGPL). This means that you can
download the plugin for free, modify it to your liking, and you do not have
to put any copyright or backlinks on your page.

Features
~~~~~~~~

The plugin currently supports the following features. All features can be
selectively disabled / enabled. For more detailed information, refer to
the Configuration section below.

- Google SEO 404:

  * Error pages return HTTP 404 Not Found (and others) instead of 200 OK
  * Custom 404 error pages
  * Google's 404 widget

- Google SEO Meta:

  * description meta tags for forums / threads / etc.
  * specify canonical pages
  * nofollow links

- Google SEO Redirect:

  * redirect old URLs to new URLs (or vice versa)
  * avoid URL breakage and double content

- Google SEO Sitemap:

  * dynamic generation of XML Sitemaps
  * search engines find your content without crawling

- Google SEO URL:

  * Keyword URLs (e.g. Thread-Some-Subject instead of showthread.php?tid=12345)
  * UTF-8 in URLs is supported
  * Customizable URL styles

Installing
----------

In order to install this plugin, first check that you meet the
requirements, then upload the plugin's files (usually the entire
inc/ folder). The list of requirements and files with additional
instructions is listed below. Once all files are present, you can
use the Install/Activate controls in the Admin CP and configure it.
Once installed, the plugin will show a status which may contain
further instructions.

Requirements
~~~~~~~~~~~~

In order to use this plugin, you must meet these requirements:

- MyBB (older versions of MyBB need the *Google SEO 1.6* or *Legacy* plugin)
- `PluginLibrary <http://mods.mybb.com/view/pluginlibrary>`_ 11
- PHP 5.1.0
- Apache (alternative webservers may work but are not supported)
- MySQL (alternative databases are not supported at this time)

Some features of this plugin also require changes to core files.
The changes can be applied and reverted in the Admin CP.

Upload language files
~~~~~~~~~~~~~~~~~~~~~

This plugin uses the following language files. Please upload them all.

- inc/languages/english/admin/googleseo_plugin.lang.php
- inc/languages/english/admin/googleseo_settings.lang.php
- inc/languages/english/googleseo.lang.php

.. note::

  If your board uses languages other than English, please upload
  another copy of the language files for each language, i.e.
  into every inc/languages/\*/ folder.

Language Packs
++++++++++++++

Language Packs for this plugin may be available on the MyBB Mods site,
but there is no guarantee they will be up to date. This plugin has very
few (less than ten) language strings that will be used outside of
the Admin CP. You will find them in googleseo.lang.php. You can either
translate them yourself or leave them as is.

Upload plugin files
~~~~~~~~~~~~~~~~~~~

This plugin uses the following plugin files. Please upload them all.

- inc/plugins/google_seo.html (the documentation you are reading)
- inc/plugins/google_seo.php
- inc/plugins/google_seo/404.php
- inc/plugins/google_seo/meta.php
- inc/plugins/google_seo/plugin.php
- inc/plugins/google_seo/redirect.php
- inc/plugins/google_seo/sitemap.php
- inc/plugins/google_seo/url.php

Enable the plugin
~~~~~~~~~~~~~~~~~

Once all files are uploaded to the correct location, go to your
*Admin CP -> Plugins* page. The Google SEO Plugin should show up in
the plugin list. Click *Install & Activate* to install the plugin.
The plugin will show a status information with further instructions
for you to follow. By default, all features of the plugins will be
disabled at first and can be enabled on the *Admin CP -> Configuration* page.

Updating
--------

The Google SEO plugin can be updated by uploading the new files
(as described in the Installing section above). Once all the new files
are in place, go to your Admin CP -> Plugins page and deactivate
the plugin, then activate it again. Further instructions may be
given in the plugin status. New settings might be available
in the Admin CP -> Configuration page.

.. note::

  If you are updating from Google SEO Legacy or Google SEO 1.1.13 or older,
  you have to undo any modifications made to inc/functions.php prior to
  updating the plugin. Please obtain the original, unmodified inc/functions.php
  directly from the `MyBB downloads page`__ or `MyBB Wiki`__.

  If you were using the Patches plugin to modify inc/functions.php,
  please deactivate / remove the Google SEO related Patches.

__ http://www.mybb.com/downloads
__ http://wiki.mybb.com/index.php/Versions

Uninstalling
------------

If you want to redirect SEO URLs back to MyBB stock URLs,
do not uninstall but refer to the Redirection section below.

To uninstall the plugin, go to your *Admin CP -> Plugins* page and
click *Uninstall*. Then remove the plugin's files (listed in the Installing
section above). Also remove all other modifications you may have made
to additional files such as htaccess.

.. note::

  Uninstalling Google SEO removes its URL database. If you were
  using SEO URLs, those URLs will no longer work and old URLs
  won't be redirected even if you reinstall the plugin.

Plugin Status
-------------

When installed and activated, *Google SEO* will display a plugin status
on the *Admin CP -> Plugins* page. The status gives an overview of which
of the plugin's features are enabled or disabled. It will also warn you
about known issues and tell you when you have to make changes, such
as adding Rewrite Rules or applying or reverting changes to core files.

.. note::

  Check the plugin status regularly (especially when changing settings)
  to see if everything is the way you want it to be.

Configuration & Settings
------------------------

*Google SEO* has lots of settings, organized into several setting
groups. If you go to your *Admin CP -> Configuration* page, and
scroll down, you should see the Google SEO Setting groups.
The following sections will describe the settings available in
each group. Please also read the descriptions of the settings
themselves directly in the Admin CP.

Google SEO
~~~~~~~~~~

This is the main setting group of the plugin. In here you can decide
whether or not to enable the various feature sections of the plugin.
Disabling a setting here also disables all other settings of that
feature, so for example if you disable URL, none of the settings in
the Google SEO URL setting group below will have any effect.

Settings in this group:

- Enable Google SEO 404
- Enable Google SEO Meta
- Enable Google SEO Redirect
- Enable Google SEO Sitemap
- Enable Google SEO URL

.. note::

  Many of the settings below are intended for advanced users only.
  If you do not understand what a setting does, stick to the
  recommended default value.

Google SEO 404
~~~~~~~~~~~~~~

Settings in this group:

- 404 widget
- Show 404 errors in Who's Online
- Customize HTTP status codes
- Debug 404 error labels

Google SEO Meta
~~~~~~~~~~~~~~~

Settings in this group:

- Meta description
- Canonical Page
- Meta for Archive Mode
- Provide page number for forum and thread titles
- Nofollow links
- Noindex forums

Page number in forum and thread titles
++++++++++++++++++++++++++++++++++++++

By default, MyBB does not include a page number in the title.
This causes Google to complain about lots of duplicate title
tags for forums and threads which have many pages.

Google SEO Meta provides a variable which you can include
into your *forumdisplay* and *showthread* templates. However
if you want this, you have to edit these templates manually.

Example <title> tag in the *showthread* template::

  <title>{$thread['subject']}{$google_seo_page}</title>

The variable will only be set for pages > 1, so this change
would lead to page titles like "Subject" for page 1 and
"Subject - Page 2" for page 2.

Google SEO Redirect
~~~~~~~~~~~~~~~~~~~

Settings in this group:

- HTTP <-> HTTPS
- Permission Checks
- Redirect Post Links
- LiteSpeed Bug workaround
- Nginx Bug workaround
- Debug Redirect

Redirect Loops
++++++++++++++

If you experience redirect loops (redirects that never end)
please enable the *Debug Redirect* feature and send me the
debug output. Please note that the debug output may contain
confidential information (such as login cookies), so please
don't post it in public, but email / PM me directly.

If you are using an alternative webserver, you can also
give the LiteSpeed / Nginx workaround settings a try (even
if you're not using those webservers) and see if they help.

Otherwise, disable Redirect until a solution can be found.

Redirecting SEO URLs back to MyBB stock URLs
++++++++++++++++++++++++++++++++++++++++++++

*Google SEO* does not force you to keep using its URLs. If you
want to go back to stock URLs, you can do so. Basically you
have two options to achieve a SEO URL -> Stock URL redirection:

- Empty the SEO URL scheme for a particular URL type

  This is useful if for example you want to go back to stock
  URLs for user profiles only, but not for forums and threads.

- Revert changes to core files

  This way the Google SEO URL module will be in inactive state.

Do not disable Google SEO URL or Redirect, and do not remove
the Rewrite Rules from your .htaccess. All of these components
are required to keep old keyword URLs and subsequent redirect
working.

Google SEO Sitemap
~~~~~~~~~~~~~~~~~~

Settings in this group:

- Sitemap URL scheme
- Forums
- Threads
- Users
- Announcements
- Calendars
- Events
- Additional Pages
- Sitemap Pagination

Sitemap Generation
++++++++++++++++++

The sitemap standard (or that what Google, Yahoo, Ask etc. are using)
is described here:

  http://www.sitemaps.org/protocol.php

The sitemap-index.xml is an Sitemap index file as described there.
It links to the actual sitemap files (sitemap-threads.xml?page=1).

Google SEO Sitemaps are created dynamically. When you tell Google about
your XML Sitemap (in Google Webmaster Tools, or by specifying it in
your robots.txt file) it will download the index, and then browse through
the sitemaps listed in this index. So Google goes through your Sitemap
page by page similar to how a user goes through your forums page by page.

It's split into pages because creating a sitemap for tens of thousands of
threads, users and forums all at once would cause too much load.
Also, sitemaps have a limitation of 50000 items per sitemap.

The Sitemap is created dynamically in order to give Google and other
search engines the current up to date status of your forum whenever it
chooses to access your sitemap. This way Google gets up to date sitemaps
as early as possible which leads to google accessing your new content it
found via the sitemap as early as possible which leads to your new content
getting indexed by Google as early as possible.

Please note that the Sitemap displays only forums and threads that
the current user can actually read. So if you see private threads in
your sitemap, it may be because you're currently logged in as admin,
and does not mean that Google will see those threads too.

Submit Sitemap to Search Engines
++++++++++++++++++++++++++++++++

For the Sitemap to be of any use, you have to submit it to Search Engines.
You can automate this process by adding a Sitemap directive to your
robots.txt (example robots.txt included in the Google SEO package).
By default your Sitemap will be called sitemap-index.xml.

Google SEO URL
~~~~~~~~~~~~~~

Settings in this group:

- Query Limit
- Evaluation Mode
- Use MyBB's Cache system for SEO URLs
- Punctuation characters
- URL separator
- URL uniquifier
- URL uniquifier enforcer
- Character Translation
- lowercase words
- URL length soft limit
- URL length hard limit
- Handle Post Links
- Handle multipage links
- Forum URL scheme
- Thread URL scheme
- Thread Prefixes
- Announcement URL scheme
- User URL scheme
- Calendar URL scheme
- Event URL scheme
- Include parent forum in forum URLs?
- Include parent forum in thread URLs?
- Include parent forum in announcement URLs?
- Include parent calendar in event URLs?

Evaluation Mode
+++++++++++++++

Google SEO URL has two possible modes of operation.

In *Full Mode* (Default), every time a SEO URL is requested, it will
be obtained and returned immediately. In worst case (if the URL is
not cached), this will require a database query. When querying URLs
from the database, Google SEO tries to query as many URLs as possible
in one go, but it can't always predict which URLs will be required
for the rest of the page, especially when other plugins create links
too.

In *Lazy Mode*, Google SEO returns a place holder instead of the
SEO URL. Just before the page is sent to the user, it will then
proceed to replace all placeholders with the SEO URL. This way,
all URLs that are on the page can be handled in a single query.

The downside of *Lazy Mode* is that there's no guarantee that
a requested URL will be used in the output. It might just as
well become part of some notification mail or used for other
purposes. Since this is most likely to happen during POST
requests, *Lazy Mode* will only work for GET requests and
fall back to *Full Mode* for POST requests.

If you feel that Google SEO uses too many queries on your board,
or if your board is just very large and active, or if your
database just happens to be very slow, *Lazy Mode* might
be for you. Otherwise stick to *Full Mode* as it is much more
reliable.

Uniquifier
++++++++++

The Google SEO URL Uniquifier is applied to URLs that would otherwise
not be unique (and thus result in threads that are not accessible).
Collision testing (for example for two threads with the same title)
is done only once, therefore the uniquifier must result in a truly
unique URL that can not possibly collide with anything else.

A good uniquifier needs to fulfill these two criteria:

1. contain the items unique {id}
2. contain punctuation that cannot occur in non-uniquified URLs

Early versions of Google SEO used {url}-{id} as uniquifier and
therefore did not fulfill criteria 2. This could lead to collisions
in rare cases, for example:

::

  ID: 1, Title: Unique,   URL: Thread-Unique
  ID: 2, Title: Unique 3, URL: Thread-Unique-3
  ID: 3, Title: Unique,   URL: Thread-Unique-3 (same as Thread 2)

Thread 3 collides with Thread 1 (both are called Unique), so the
uniquifier is applied. This results in Unique-3. However, there
already happens to be a thread called Unique-3. Doesn't work.

With the new uniquifier {url}{separator}{separator}{id},
the uniquified URL will be Thread-Unique--3. Because the id is
already unique, and other URLs can't contain -- (title punctuation
is reduced to one single separator, not two), that makes the URL
as a whole unique.

If you use a custom uniquifier, make sure it fulfills the two
criteria listed above. Be aware that special punctuation characters
like :@/?& or space can break your URLs.

Character Translation
+++++++++++++++++++++

Please note that translation of characters is not required (browsers
and Google handle them just fine), and it causes additional CPU cost.

You can do character translation in URLs if you so desire. In the
textbox of the character translation setting, specify one character
per line and its replacement, separated by =.

For example the following would replace German umlauts with their
most commonly used ASCII counterparts:

::

  Ä = Ae
  Ö = Oe
  Ü = Ue
  ä = ae
  ö = oe
  ü = ue
  ß = ss

With this character translation setting, Thread-Übergrößenträger
would appear as Thread-Uebergroessentraeger instead.

.. note::

  Google SEO Legacy used a separate translate.php file instead.
  This file is not used anymore. The translations have to be
  specified in the setting.

URL Schemes
+++++++++++

*Google SEO* uses a simple, static URL scheme by default (Forum-Name,
Thread-Name, etc.). This is recommended because it tells users and search
engines exactly what to expect behind an URL. It is possible to customize
the URL scheme with various settings. However, not every scheme will
actually work. When customizing URL schemes, you have to be aware of the
limitations of both this plugin and MyBB.

Avoid Scheme Conflicts
``````````````````````

Google SEO URL relies on the webserver to rewrite the URLs to the correct
file. Thread-Subject is rewritten to showthread.php, Forum-Name to
forumdisplay.php, and so on. For those rewrites to work, every URL must
have something in it that identifies it as being of a particular type.

For this reason it's not possible to remove Thread- or Forum- because
then the rewrite rules would confuse Subject for a forum URL and Name
with a thread URL. When you have a conflict of any kind in your URL
scheme, the URLs will stop working and you will also lose the ability
to redirect these URLs later.

.. note::

  If a Rewrite Rule matches more than one type of URL, you have a conflict
  and your URLs will stop working either altogether or at least partially.

The default scheme avoids conflicts by using prefixes: Thread-{url},
Forum-{url}, Announcement-{url}, etc. This way a thread URL can never
start with Forum- and a forum URL can never start with Thread-, so
there are no conflicts possible.

You can change those prefixes to something else as long as you keep
some kind of unique prefix, for example t-{url} instead of Thread-{url}.

You can also use postfixes, such as {url}-Thread and {url}-Forum.
However you can not mix prefix and postfix, as otherwise Thread-Forum
could be either a thread called forum, or a forum called thread.

On the other hand, a postfix such as {url}.thread would work even
if the other URLs use prefixes, because by default the dot character
can not occur in the {url} itself.

Dynamic SEO URLs
````````````````

If your webserver does not support mod_rewrite, you can put the keyword
URL in the dynamic part. The default dynamic URL scheme would be like so::

  Forum:         forumdisplay.php?{url}
  Thread:        showthread.php?{url}
  Announcements: announcements.php?{url}
  Users:         member.php?action=profile&{url}
  Calendars:     calendar.php?{url}
  Events:        calendar.php?action=event&{url}

Please note that {url} must be a stand alone parameter. The following will NOT work::

  ?Something-{url}
  ?something={url}

The only exception to that rule are the parameter names that Google SEO
uses internally for rewrites::

  forumdisplay.php?google_seo_forum={url}
  showthread.php?google_seo_thread={url}
  ...

Virtual Directory Structure
```````````````````````````

Google SEO supports including the parent forum name in thread URL,
and allows the use of the directory separator /. With this,
in theory, you could build a virtual directory structure URL scheme
along the lines of f-My-Category/f-My-Forum/t-Subject.

However, due to the issues involved with Virtual Directory Structure,
this feature will never be directly supported in any way. You can do it
if you absolutely want to but you will have to adapt your own rewrite
rules for it (the standard rewrite rules do not look for / in {url}).
The rewrite rules suggested in the Plugin Status won't work.

Doing this is NOT recommended for several reasons. First of all it
makes URLs more expensive and serves nothing but make your URLs
longer than they need to be.

MyBB uses relative links everywhere. Introducing a directory structure,
virtual or not, breaks those links. Some of these issues can be worked
around, but there's no guarantee that it will work with other things
such as JavaScript.

To work around this issue, add a base tag to your *headerinclude* template::

  <base href="{$settings['bburl']}/" />

Here's an example for a Virtual Directory Structure URL scheme::

  Forum:         f-{url}/
  Thread:        f-{url}
  Announcements: f-{url}
  Users:         u-{url}/
  Calendars:     c-{url}/
  Events:        c-{url}
  Parent Forum:        {parent}/f-{url}
  Parent Thread:       {parent}/t-{url}
  Parent Announcement: {parent}/a-{url}
  Parent Event:        {parent}/e-{url}

And the Rewrite Rules to go with it::

  # Google SEO URL Forums:
  RewriteRule ^f\-([^./]+)/?$ forumdisplay.php?google_seo_forum=$1 [L,QSA,NC]
  RewriteRule ^f\-([^.]+)/f-([^./]+)/?$ forumdisplay.php?google_seo_forum=$1/f-$2 [L,QSA,NC]

  # Google SEO URL Threads:
  RewriteRule ^f\-([^.]+)/t-([^./]+)$ showthread.php?google_seo_thread=$1/t-$2 [L,QSA,NC]

  # Google SEO URL Announcements:
  RewriteRule ^f\-([^.]+)/a-([^./]+)$ announcements.php?google_seo_announcement=$1/a-$2 [L,QSA,NC]

  # Google SEO URL Users:
  RewriteRule ^u\-([^./]+)/?$ member.php?action=profile&google_seo_user=$1 [L,QSA,NC]

  # Google SEO URL Calendars:
  RewriteRule ^c\-([^./]+)/?$ calendar.php?google_seo_calendar=$1 [L,QSA,NC]

  # Google SEO URL Events:
  RewriteRule ^c\-([^./]+)/e-([^./]+)$ calendar.php?action=event&google_seo_event=$1/e-$2 [L,QSA,NC]

Combined Styles
```````````````

It is possible to combine the various URL scheme styles to some degree.
You can take the standard URL style Thread-{url}, and put the {url}
in the dynamic part instead using Thread?{url}. To make this work you
need a rewrite for Thread -> showthread.php.

With the {url} in the dynamic part of the URL, you can proceed to
including parent forums in thread URLs, even using directory separators.
Since / in the dynamic part of the URL is not seen as a real directory,
you will avoid most of the pitfalls involved with the Virtual Directory Structure.

Here's an example for a combined URL scheme::

  Forum:         Forum?{url}
  Thread:        Thread?{url}
  Announcements: Announcement?{url}
  Users:         User?{url}/
  Calendars:     Calendar?{url}/
  Events:        Event?{url}
  Parent Forum:        {parent}/{url}
  Parent Thread:       {parent}/{url}
  Parent Announcement: {parent}/{url}
  Parent Event:        {parent}/{url}

The end result would be an URL like Thread?Category/Forum/Subject.
Even so it's not recommended because the URL can just get too long.

Troubleshooting
---------------

SEO URLs do not work
~~~~~~~~~~~~~~~~~~~~

If the SEO URLs do not appear (links are not changed), then you have either
not enabled the URL settings properly, or you did not apply the necessary
changes to core files. Check your *Plugin Status*.

If the SEO URLs appear but give you errors like thread not found, thread
does not exist, etc., then your *Rewrite Rules* do not work for some reason.
Check that you have edited the *.htaccess* (not htaccess.txt!) correctly.
Some hosts need a RewriteBase, others do not. If you are using a custom
SEO URL Scheme, make sure this scheme does not have any conflicts.

There is no sitemap.xml file
~~~~~~~~~~~~~~~~~~~~~~~~~~~~

Google SEO's Sitemap is generated dynamically every time it is accessed.
This means there is no file for it, similar to how there is no file for
a specific thread. Instead of looking for a file on FTP, use HTTP.
By default the URL to your sitemap will be yoursite/sitemap-index.xml

Users show up as seeing error pages in Who's Online
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

This usually happens when you have a missing image or CSS or JS file
in your forum. The user visits a thread or forum, the browser tries
to load the missing image, and the resulting 404 error overrides the
location in the online list.

The Google SEO 404 Who's Online setting has an option to include the
URI in the online status. If you enable this and then hover over the
error page links in Who's Online, you should be able to see which
page / URL caused the error and fix it.

Support
-------

If you need further assistance, the official release thread for this plugin
can be found in the `MyBB Community - Plugin Releases`__ forum.

__ https://community.mybb.com/thread-202483.html

Thank you for reading the documentation first! :)
