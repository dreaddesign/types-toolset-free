# Toolset Common Library

## 2.8
* Legacy branch of the library created to support Types 2.3. Contains all fixes up until 3.0.1.

## 3.0.1
* Adjust the shortcodes GUI API so it generates attributes wrapped in single quotes.

## 3.0
* (types-1537) Display the Installer's Compatibility Reporting setting wherever necessary.
* (toolsetcommon-406) Add new actions to inform about associations being created and deleted.
* (types-1583) Fix: Errors in PHP 5.3.6.
* (types-1553) Fix: Do not assume that an option for post type definitions is set.
* Migration: do not enforce a specific translation mode between posts involved in relationships to migrate.
* API: fails gracefully when trying to get a relationship by a non existing slug.
* API: two new public functions to create associations.
* WPML: support defining associations between posts without enforcing a specific translation mode for them.
* WPML: ensure that data from a post reference field can be rendered.

## 2.7.1
* Tag for Forms 2.0-RC3
* Better validation methods in shared forms.
* Fixed styling issue in the Toolset shared admin pages.
* Fixed the shortcodes API GUI on items that have a content and include instructions for it.

## 2.7.0
* Tag for upcoming Toolset releases.
* Fix a PHP 5.3 compatibility issue.

## 2.6.9.1
* Restore compatibility with Toolset plugins using previous versions of the assets manager shared class.

## 2.6.9
* Restore the $current_page value for the post selector shortcode attribute.
* Remove an admin notice about using Toolset plugins together.

## 2.6.8
* (types-1444) Fixed inconsistent API call results when WPML switcher is set to "All languages".

## 2.6.7
* Improve the shortcodes GUI post selector for better m2m compatibility.
* Extend the m2m relationships query API so it can play with intermediary post types.

## 2.6.6
* (toolsetcommon-390) Make sure that there are no potential posts for association when the other (known) post has reached its cardinality limit.
* (toolsetcommon-381) Make the `_wpcf_belongs_{$post_type}_id` usage in WP_Query meta queries safe to use also in m2m.
* (types-1467) Fixes in the data structure upgrade mechanism and in the upgrade command for m2m-v1 tables.

## 2.6.5
* Released with CRED 2.0-b2 and Layouts 2.3-b1
* Include a compatibility layer for xxx_post_meta native functions usage on legacy relationships postmeta keys.

## 2.6.4
* Added a set of checks to avoid errors with third parties also loading the Recaptcha v1.11 library.
* (toolsetcommon-382) Fix issues with the Toolset_Post_Type_Repository too early initialization.
* (toolsetcommon-369) Fix: Avoid repeated results in the association query.

## 2.6.3
* Compatibility for Types 2.3-b4
* Fixed an issue using repeatable File field inside a relationship
* (types-1374) Fail gracefully if dealing with corrupted `_wpcf_belongs_*_id` postmeta during the m2m migration.

## 2.6.2
* Various minor bugfixes.
* (toolsetcommon-290) First version of the public relationship API for both legacy and m2m relationships.
* (types-1436) Removed a notice about Types becoming commercial if there's no Types 2.2.* version or below active.

## 2.6.1
* Fixed an issue regarding the Gutenberg integration with the extension of the Custom HTML block.
* Fixed a link to documentation about relationships and WPML.
* Removed a final keyword on some classes.

## 2.6.0
* Released with Types 2.3-b3, Views 2.6-b2 and CRED 2.0-b1
* Breaking change in the Toolset_Ajax implementation and subclassing mechanism.

## 2.5.10
* (toolsetcommon-315) Added a WHIP package warning about dropping the PHP 5.2 support.
* (toolsetcommon-361) Types custom fields can't be saved
* (toolsetcommon-334) Introduce a new, more flexible version of the association query class.
* (toolsetcommon-345) Delete the intermediary post when an association is deleted.
* (toolsetcommon-346, toolsetcommon-349, toolsetcommon-355, toolsetcommon-370, toolsetcommon-359) Heavy changes to the m2m API to bring WPML support.
* (toolsetcommon-368) Create intermediary posts even if there are no association fields
* (toolsetcommon-351) Make sure that the translation mode of a post type involved in a relationship cannot be changed
* (types-1376) Fixed: Post Type API does not update relationships when post type slug is changed.
* (types-1413) Fixed: Bug when creating an association with a translatable IPT in a non-default language.

## 2.5.9
* Released with Views 2.5.2, CRED 1.9.4 and Layouts 2.2
* (types-1339) Prevent PHP errors when offering to display data from a legacy Types relationship when the parent side post type no longer exists.

## 2.5.8
* New admin notice about Types becoming commercial.
* (toolsetcommon-339) Extend the relationship query with a mechanism to obtain the total number of found rows.

## 2.5.7
* (toolsetcommon-305) Improve the database structure for relationships and associations.
* (types-1213) Implement a mechanism for handling database integrity issues within the m2m API.
* (types-1265) Introduce a QUERY_HAS_TRASHED_POSTS argument to Toolset_Association_Query
* Many improvements to the m2m API, especially to the relationship query.
* (toolsetcommon-328) Enforce cardinality limits when creating associations between two elements.
* (toolsetcommon-330) Prevent upgrade routines from running repeatedly. Fix a m2m activation issue.
* (toolsetcommon-249) The Toolset_Twig_Autoloader now bails out when it's possible to load the Twig_Environment class.

## 2.5.6
* Fixed a but that prevented CRED attributes offered as select2 instances from getting their values in the final shortcode.

## 2.5.5
* Released with Types 2.2.20 and Views 2.5.1
* Fix the way we determine whether the m2m API should be enabled on fresh sites without post relationships by default (types-1252).
* Fix several compatibility issues with Visual Composer.
* Fix a problem when gathering the title of some Types fields.

## 2.5.4
* Released with CRED 1.9.3
* Include a shared Toolset JS shortcodes library.
* Fixed a problem with loading the latest Bootstrap CSS Components from Toolset Common.
* Fixed some notices caused by slightly different array structures on fields.

## 2.5.3
* Released with Types 2.2.17

## 2.5.2
* Released with Views 2.5 and Layouts 2.1

## 2.5.1
* Moved Twig from Types to the Toolset Common Library (toolsetcommon-63).
* Created an abstraction of a listing page in the Toolset Common Library (toolsetcommon-64).
* Added an extendable Toolset Troubleshooting page (toolsetcommon-76).
* Implemented a generic upgrade mechanism (toolsetcommon-75).
* Moved classes related to Types fields to the library (toolsetcommon-84, toolsetcommon-104).
* Fix a minor issue with search while not on first page of the listing in Field Control and Custom Fields pages in Types (toolsetcommon-178)
* Implemented the m2m API (toolsetcommon-70).

## 2.5.0
- Added the ability to control the settings from selected themes.

## 2.4.5
- Compatibility with Types 2.2.16 and the new admin notice asking users to register their commercial plugins.

## 2.4.4
- Compatibility with CRED 1.9.2

## 2.4.3
- Compatibility with Types 2.2.14, Vies 2.4.1, CRED 1.9.1, Access 2.4.2 and Layouts 2.0.2

## 2.4.2
- Removed notices, which leads to documentation of integration between Layouts and 3rd party themes.

## 2.4.1
- Removed notices, to push users to activate Layouts theme integration plugins based on the current theme.
- Added filter to remove/add automatic notices on demand.

## 2.4.0
- Updated Font Awesome library to 4.7.0
- Included a mechanism to insert Bootstrap grid rows into selected Toolset editors.
- Included the needed changes for providing Bootstrap compatible output to CRED frontend forms.
- Improved the shared admin notices so they only get displayed for admins.
- Included the Chosen library as alternative to select2.
- Fixed an issue with a missing CRED datepicker stylesheet.
- Fixed an issue with link elements of Visual Composer when used as editor in Content Templates.
- Moved a CSS rule regarding jQuery UI datepicker, that was removed from Types.


## 2.3.2
- Fixed localization issue with date field and WordPress below 4.7
- Most notices are now only shown to Administrators.

## 2.3.1

- Improved the styling for CRED file-related fields.
- Fixed some compatibility issues with PHP 7.1
- Fixed taxonomy suggestions on CRED forms.

## 2.3.0

- Various fixes and adjustments for CRED 1.8.6
- Adds callback methods for wpPointer object onOpen and onClose
- Allows user to ask Toolset to load Bootstrap library for them, or alternatively set the Bootstrap version they are using on their own
- layouts-1239: Added Toolset_Admin_Notice_Layouts_Help
- Extend the post objects relationships management with two actions to gather data on demand.
- Only include the jQuery datepicker stylesheet on demand when the current page contains a datepicker from Toolset.
- Include the user editors in the common bootstrap class.
- toolsetcommon-127, toolsetcommon-55: Include knockout.js
- toolsetcommon-139: Clean up Toolset_Assets_Manager and define constants for asset handles
- toolsetcommon-144: Added Toolset_Admin_Notices_Manager
- toolsetcommon-137: Make the toolset-forms classes autoloaded.
- toolsetcommon-72: Implemented a classmap-based autoloader for all Toolset plugins.
- toolsetcommon-140: Improve a way to detect the status of WPML.
- toolsetcommon-142: Use get_user_locale() instead of get_locale() if it's available.

## 2.2.10

- types-1045: Do not assume field value type in Enlimbo_Form::textfield().

## 2.2.9

- Fix a validation issue for file, audio, video and embed fields affecting Types.
  Allow URLs with non-latin characters, but only for URLs that point to attachments
  from the Media Library (validated by WordPress media upload mechanism) (types-1013).
- Improve the validation for non-required Skype fields (toolsetcommon-128).

## 2.2.6

- Fix a CRED issue with added validation rules (types-988).
- Handle several issues related to using "0" as a default field value and saving it to the database (toolsetcommon-106).
- Minor compatibility fixes for the upcoming CRED 1.8.4 release.
- Extend WPToolset_Cake_Validation with the "url2" validation as a counterpart to the validation method in JQuery UI (types-988).

## 2.2.5 (November 5, 2016)

- Thorough check for security vulnerabilities.

## 2.2.4 (November 2, 2016)

- Fixed a problem with some assets management by defining better rules on constant definitions.

## 2.2.3 (October 10, 2016)

- Fixed select2 edge cases when methods are called on non-select2 initialised element.
- Refined special handling of old inputs by making sure target is only a select and not the hidden relative element
- Extended the valid date formats that Types and CRED supports for the Date field.

## 2.2.2 (September 26, 2016)

- Updated the bundled select2 script to version 4.0.3
- Fixed a problem with some assets URLs lacking a backslash
- Improved management of CRED file fields uploads
- Improved the frontend markup for CRED taxonomy fields
- Added an internal Toolset compatibility class

## 2.2.1 (August 25, 2016)

- Avoid translating the Toolset top level admin menu label

## 2.2 (August 24, 2016)

- Added compatibility classes for Relevanssi and Beaver Builder
- Added a CSS components class
- Improved the Toolset Common assets management
- Added the Glyphicons library

## 2.1 (June 13, 2016)

- Refactored event-manager library to toolset-event manager to namespace it and avoid conficts with ACF Plugin
- Added a new class for promotional and help videos management
- Improved compatibility with PHP 5.2
- Improved compatibility with WPML, including admin language switchers and loading times
- Improved compatibility for CRED file uploads and bundled scripts
- Fixed double slashes on assets URLs

## 2.0 (April 7, 2016)

- Created new loader to load resources for all plugins when plugin loads
- Refactored in a more organised way files and resources to be compatible with the new loader
- Added scripts and styles manager class to register and enqueue static resources with a unified system
- Added Toolset Dialog Boxes to provide a unified way to create and render dialogs
- Fixed various bugs

## 1.9.2 (March 17, 2016)

- Fixed issue in validation messages on Amazon S3

## 1.9.1 (March 15, 2016)

- Added control to filter array to prevent exceptions
- Prevented error when object does not have property or key is not set in array filter callback
- Fixed glitch in validation library
- Absolute path to include toolset-forms/api.php
- Fixed search terms with language translation

## 1.9 (February 15, 2016)

- Tagged for Types 1.9, Views 1.12, CRED 1.5, Layouts 1.5 and Access 1.2.8
- Updated parser.php constructors for PHP7 compatibility.
- Updated the adodb time library for PHP7 compatibility.
- Introduced the Shortcode Generator class.
- New utils.

## 1.8 (November 10, 2015)

- Tagged for Views 1.11.1, Types 1.8.9 and CRED 1.4.2
- Improved the media manager script.
- Added helper functions for dealing with $_GET, $_POST and arrays.
- Improved CRED file uploads.
- Improved taxonomy management in CRED forms.
- Improved usermeta fields management in CRED forms.

## 1.7 (October 30, 2015)

- Tagged for Views 1.11 and Layouts 1.4

## 1.6.2 (September 25, 2015)

- Tagged for CRED 1.4, Types 1.8.2

## 1.6.1 (August 17, 2015)

- Tagged for Composer Types 1.8, Views 1.10, CRED 1.4 and Layouts 1.3

## 1.6 (June 11, 2015)

- Tagged for Types 1.7, Views 1.9 and Layouts 1.2

## 1.5 (Apr 1, 2015)

- Tagged for Types 1.6.6, Views 1.8, CRED 1.3.6 and Layouts 1.1.
- Fixed issue when there is more than one CRED form on a page with the same taxonomy.
- Fixed a little problem with edit skype button modal window - was too narrow.
- Fixed empty title problem for filter "wpt_field_options" on user edit/add screen.
- Added filter "toolset_editor_add_form_buttons" to disable Toolset buttons on the post editor.
- Added placeholder attributes to fields.
- Updated CakePHP validation URL method to allow new TLD's.

## 1.4 (Feb 2 2015)

- Tagged for Views 1.7, Types 1.6.5, CRED 1.3.5 and Layouts 1.0 beta1
- Updated Installer to 1.5

## 1.3.1 (Dec 16 2014)

- Tagged for Views 1.7 beta1 and Layouts 1.0 beta1
- Fixed issue about Editor addon and ACF compatibility
- Fixed issue about branding loader

## 1.3 (Dec 15 2014)

- Tagged for Views 1.7 beta1 and Layouts 1.0 beta1
