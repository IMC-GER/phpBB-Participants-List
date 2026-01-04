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
	'PTSL_TITLE'				=> 'Teilnehmerliste',
	'PTSL_NAME'					=> 'Name',
	'PTSL_PTS_NUMBER_SHORT'		=> 'TN',
	'PTSL_PTS_NUMBER'			=> 'Anzahl der Teilnehmer',
	'PTSL_COMMENT'				=> 'Bemerkung',
	'PTSL_PTS_SUM_STRING'		=> 'Es haben sich %1$d Teilnehmer angemeldet.',
	'PTSL_CONFIRMBOX_TEXT'		=> 'Bist du dir sicher das du diesen Eintrag löschen möchtest.<br><b>Die Löschung kann nicht rückgänig gemacht werden.</b>',
	'PTSL_HAS_NO_PERMISSION'	=> 'Du hast keine Berechtigung, diese Aktion auszuführen.',
	'PTSL_SAVE'					=> 'Speichern',

	'PTSL_EDIT_LIST'			=> 'Eintrag bearbeiten',
	'PTSL_DEL_FROM_LIST'		=> 'Eintrag löschen',
	'PTSL_ADD_TO_LIST'			=> 'Teilnehmer eintragen',

	'PTSL_GO_TO_LIST_SHORT'		=> 'TN-Liste',
	'PTSL_GO_TO_LIST'			=> 'Zur Teilnehmerliste',

	'PTSL_ADD_PTS_LIST'			=> 'Teilnehmerliste hinzufügen',
]);
