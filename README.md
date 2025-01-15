Views Alphabetic Pagination
===========================

Allows an alphabetical menu to be added in the header or footer of a views display.

Dependencies
------------

* Views
* Entity Plus

Install
-------

Install the Alpha Pagination module.

1) Install this module using the official [Backdrop CMS instructions](https://backdropcms.org/guide/modules).
2) Download and enable dependencies (Entity Plus).
3) Enable the Alpha Pagination for Views module using Administer -> Modules (/admin/build/modules).

Views Integration and Configuration
------------------------------------

An optional sample view is included and can be enabled via the alpha_pagination_sample view. The sample relies on the 'post' default content type. You can create sample content using the devel module or rely on your own data.

1) Build a new view of either users, content (nodes), or comments.
2) Add whatever field you want to use as the basis for the alphabetic grouping (e.g. title, body). You can optionally exclude this field from display if you don't want it to appear in the results shown on the page for some reason. You can only choose a field that is a textfield, textarea or a textarea with a summary.
3) Add either a header or a footer to your view. Select the new item available in the menu of options for Global: Alpha Pagination.
4) Configure how you want alpha_pagination to work and specify where it should appear:
  a) set the path to the results view page.
  b) select the field you want to use as the basis for the alphabetic grouping from the options presented in the select list (note: if the field you want to use does not appear, go back and add it to your view and then return to this configuration page to select the field).
  c) add a contextual filter that is the same as the field you wish to use as the basis for alphabetic sorting. Be sure to enable Glossary mode and set the character limit to 1. The transform case option on the URL should be set to Upper Case. Also be sure this is the value item for the field and not something else like formatter.
  d) by default the alpha pagination will apply to all displays; if you only want the alpha pagination to appear on the current display, use the drop-down menu at the top of the administrative interface to change the setting from "All displays" to "This page (override)".

License
-------

This project is GPL v2 software. See the LICENSE.txt file in this directory for
complete text.

Current Maintainers
-------------------

* [Herb v/d Dool](https://github.com/herbdool).
* This module is currently seeking maintainers.

Credits
--------

Ported to Backdrop CMS by Herb v/d Dool.

Alpha Pagination for Views was designed and written by [Michael Bagnall](https://www.drupal.org/u/elusivemind).

This module exists thanks to the support of Highwire Press, Inc and the Institute for the Arts and Humanities and UNC and was created as part of the Symposiac Conference Platform.

Additional styles & configuration options by [MarkCarver](https://www.drupal.org/u/markcarver).

Arabic Letter Support by [Jad Sayegh](https://www.drupal.org/u/jadsay).
