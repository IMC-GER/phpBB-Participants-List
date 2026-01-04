<?php
/**
 * Active Topics
 * An extension for the phpBB Forum Software package.
 *
 * @copyright (c) 2023, Thorsten Ahlers
 * @license GNU General Public License, version 2 (GPL-2.0)
 *
 */

if (!defined('IN_PHPBB'))
{
	exit;
}

if (empty($lang) || !is_array($lang))
{
	$lang = [];
}

// DEVELOPERS PLEASE NOTE
//
// All language files should use UTF-8 as their encoding and the files must not contain a BOM.
//
// Placeholders can now contain order information, e.g. instead of
// 'Page %s of %s' you can (and should) write 'Page %1$s of %2$s', this allows
// translators to re-order the output of data while ensuring it remains correct
//
// You do not need this where single placeholders are used, e.g. 'Message %d' is fine
// equally where a string contains only two placeholders which are used to wrap text
// in a url you again do not need to specify an order e.g., 'Click %sHERE%s' is fine
//
// Some characters you may want to copy&paste:
// ’ »« „“ ” …
//

$lang = array_merge($lang, [
	'PTSL_TITLE'				=> 'List of participants',
	'PTSL_NAME'					=> 'Name',
	'PTSL_PTS_NUMBER_SHORT'		=> 'Pts.',
	'PTSL_PTS_NUMBER'			=> 'Number of participants',
	'PTSL_COMMENT'				=> 'Comment',
	'PTSL_PTS_SUM_STRING'		=> '%1$d participants have registered.',
	'PTSL_CONFIRMBOX_TEXT'		=> 'Are you sure you want to delete this entry?<br><b>The deletion cannot be undone.</b>',
	'PTSL_HAS_NO_PERMISSION'	=> 'You do not have permission to perform this action.',
	'PTSL_SAVE'					=> 'Save',

	'PTSL_EDIT_LIST'			=> 'Edit entry',
	'PTSL_DEL_FROM_LIST'		=> 'Delete entry',
	'PTSL_ADD_TO_LIST'			=> 'Register participant',

	'PTSL_GO_TO_LIST_SHORT'		=> 'Pts.-list',
	'PTSL_GO_TO_LIST'			=> 'Go to the participants list',

	'PTSL_ADD_PTS_LIST'			=> 'Add participant list',
]);
