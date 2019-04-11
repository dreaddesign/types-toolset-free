Usage:

include 'common/forms/bootstrap.php';
// API call, returns HTML formatted output
$html = wptoolset_form_field( $field_db_data );

To get HTML and rest of the scripts queued,
call function before queue_styles WP hook.


Filters:

toolset_valid_image_extentions
toolset_valid_video_extentions

Parameters:
- array - valid extension to be filtered

Output:
- array - filtered extension array

Example: add jfif extension:

add_filter( 'toolset_valid_image_extentions', 'my_toolset_valid_image_extentions' );
function my_toolset_valid_image_extentions($valid_extensions)
{
    $valid_extensions[] = 'jfif';
    return $valid_extensions;
}

= Changelog =

2015-11-16

- Fixed Cosmetic Issue on Radios field validation message

2015-10-08

- Fixed a problem with backslashes in WYSIWYG field title
  https://onthegosystems.myjetbrains.com/youtrack/issue/tssupp-682

2015-06-29

- Added ability to have default value for custom fields
  https://onthegosystems.myjetbrains.com/youtrack/issue/types-58

2015-03-25

- Fixed missing warning for date type field.
  https://icanlocalize.basecamphq.com/projects/7393061-toolset/todo_items/196695908/comments

2015-02-06

- Fixed empty object in WPV_Handle_Users_Functions class when user is
  not logged always return false.
  https://icanlocalize.basecamphq.com/projects/7393061-toolset/todo_items/194981023/comments

2014-12-29

- fixed display CPT in CT

2014-11-18 - plugins release - CRED 1.3.4, Types 1.6.4

2014-11-13

- Fixed a problem with missing taxonomies after form fail:

2014-11-10

- Fixed a problem with datepicker witch do not working inside a modal
  dialog

2014-11-03

- add filters to change taxonomies buttons text:
  - toolset_button_show_popular_text
  - toolset_button_hide_popular_text
  - toolset_button_add_new_text
  - toolset_button_cancel_text
  - toolset_button_add_text

- add filters to change repetitive buttons text:
  - toolset_button_delete_repetition_text
  - toolset_button_add_repetition_text

2014-10-23
- Fixed issue with missing previously saved data.

- Fixed a problem with not working build-in taxonomies (category,
  post_tag) when we use this in CPT and this post are not included on
  frontend.

- Fixed a problem with WYSIWYG field description.

2014-10-21
- Fixed issue on checkbox after submit - there was wrong condition to
  display checked checkbox.

2014-10-13

- Fixed a wrong error message position, was under date field.

2014-10-10

- Improved - add class for li element for checkboxes, radio, taxonomy
  (both: flat and hierarchical), this class is based on checkbox label
  and is sanitizet by "sanitize_title" function.

- Added filter "cred_item_li_class" which allow to change class of LI
  element in checkboxes, radio and hierarchical taxonomy field.

2014-10-09

- Fixed warning on user site, when CRED is not installed and we check
  CRED setting

- Fixed problem with validation if is empty conditions, validation
  should return true, not false.

- Improved taxonomy buttons by adding extra class "btn-cancel" when it
  is needed - on "Cancel" for hierarchical and on "Hide" on
  non-hierarchical.

2014-10-07

- Fixed problem with replacing @ char from filename

2014-10-03

- Fixed a problem with abandon filter wpcf_fields_*_value_get - this
  groups of filters was not copy to common library.

2014-10-01

- Fixed a problem with not changed label, when adding new taxonomy.

- Fixed changing the file name when upload the file

2014-09-30
- Fixed a problem with multiple CRED form on one screen.

