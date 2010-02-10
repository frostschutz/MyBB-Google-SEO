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

/* --- Character Translation: --- */

/**
 * If you want to use character translation for Google SEO URL,
 * add your translations to this file, then enable the option
 * for character translation in Google SEO URL settings.
 */

// Example translation for German umlauts:
$google_seo_translate = array(
    "Ä" => "Ae",
    "Ö" => "Oe",
    "Ü" => "Ue",
    "ä" => "ae",
    "ö" => "oe",
    "ü" => "ue",
    "ß" => "ss",
    );

/* --- End of file. --- */
?>
