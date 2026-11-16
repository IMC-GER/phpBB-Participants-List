# phpBB Participants List

## Changelog

### v1.4.0-rc1 (16-11-2026)
- Fixed: In the version-checking class, the logical OR was used instead of the bitwise OR
- Added: Censoring table column names and descriptions
- Added: Form key check for deletion prozess
- Added: A list icon has been added to the list of topics in the forum view.
- Added: The list has been integrated into the MCP for moderation
- Changed: The trim() function is backward-compatible with PHP 8.6
- Changed: phpBB min version is set to 3.3.19
- Changed: Encoding query parameters for the pagination URL.

### v1.3.3 (07-06-2026)
- Fixed: SQL-Error in `migrations/v_1_3_2.php`

### v1.3.2 (27-05-2026)
- Added: Vars in config table for minimum and maximum chars in comment to use in `generate_text_for_storage()`
- Added: Column in ptsl data table to mark list as soft delete
- Added: Function in migration to fill ptsl data table with data from old List before v1.3.0
- Added: Support for soft delete
- Added: Added link to table title
- Added: The table content becomes scrollable when the list gets long
- Changed: Improved conditions for deleting the list 
- Changed: Right margin for list buttons in responsive view
- Changed: Set max char in ptsl_comment with TWIG
- Changed: Rename `ext_requirements.php` to `imc_ext_requirements.php`

### v1.3.1 (09-04-2026)
- Fixed: Missing language variable in template
- Fixed: Missing GET parameter ‘t’ when the first post in the topic is accessed directly
- Fixed: PHP Warning when data table is empty

### v1.3.0 (07-04-2026)
- Fixed: Improved protection against SQL injection
- Added: Three optional columns added
- Added: Can sort the list by name
- Changed: Create table with div elements
- Changed: Display full username in list (colour & profile link)

### v1.1.0 (13-01-2026)
- Fixed: Incorrect colours in list while using css-class `zebra-list`.
- Fixed: Sign counter don't count when paste text.
- Fixed: Don't display special chars in username.
- Added: Button properties can be changed in css-file.
- Added: Support for emojis in comment.
- Added: User Permission “Can see participant list”.
- Changed: Improved permission check.
- Changed: Improved db-query for the breadcrumbs menue.
- Changed: Formatting of the input form optimized.

### v1.0.0-rc2 (16-12-2025)
- Fixed: Incorrect permissions have been granted for the role with full access.
- Fixed: Sign counter leaves position when changing the size of the textarea
- Fixed: Footer display incorrect

### v1.0.0-rc (13-12-2025)
