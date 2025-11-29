<?php
/**
 * Participants List
 * An extension for the phpBB Forum Software package.
 *
 * @copyright (c) 2025, Thorsten Ahlers
 * @license GNU General Public License, version 2 (GPL-2.0)
 *
 */

namespace imcger\participantslist\migrations;

class v_1_0_0  extends \phpbb\db\migration\migration
{
	public static function depends_on()
	{
		return ['\imcger\participantslist\migrations\s_1_0_0'];
	}

	public function update_data()
	{
		return [
			['permission.add', ['f_imcger_ptsl_enable', false, false]],

			['if', [
				['permission.role_exists', ['ROLE_FORUM_FULL']],
				['permission.permission_set', ['ROLE_FORUM_FULL', 'f_imcger_ptsl_enable']],
			]],
			['if', [
				['permission.role_exists', ['ROLE_FORUM_NOACCESS']],
				['permission.permission_set', ['ROLE_FORUM_NOACCESS', 'f_imcger_ptsl_enable', 'role', false]],
			]],
			['if', [
				['permission.role_exists', ['ROLE_FORUM_READONLY']],
				['permission.permission_unset',	['ROLE_FORUM_READONLY', 'f_imcger_ptsl_enable']],
			]],
			['if', [
				['permission.role_exists', ['ROLE_FORUM_BOT']],
				['permission.permission_unset',	['ROLE_FORUM_BOT', 'f_imcger_ptsl_enable']],
			]],
			['if', [
				['permission.role_exists', ['ROLE_FORUM_STANDARD']],
				['permission.permission_unset',	['ROLE_FORUM_STANDARD', 'f_imcger_ptsl_enable']],
			]],
			['if', [
				['permission.role_exists', ['ROLE_FORUM_LIMITED']],
				['permission.permission_unset',	['ROLE_FORUM_LIMITED', 'f_imcger_ptsl_enable']],
			]],
			['if', [
				['permission.role_exists', ['ROLE_FORUM_ONQUEUE']],
				['permission.permission_unset',	['ROLE_FORUM_ONQUEUE', 'f_imcger_ptsl_enable']],
			]],
			['if', [
				['permission.role_exists', ['ROLE_FORUM_POLLS']],
				['permission.permission_unset',	['ROLE_FORUM_POLLS', 'f_imcger_ptsl_enable']],
			]],
			['if', [
				['permission.role_exists', ['ROLE_FORUM_LIMITED_POLLS']],
				['permission.permission_unset',	['ROLE_FORUM_LIMITED_POLLS', 'f_imcger_ptsl_enable']],
			]],
			['if', [
				['permission.role_exists', ['ROLE_FORUM_NEW_MEMBER']],
				['permission.permission_unset',	['ROLE_FORUM_NEW_MEMBER', 'f_imcger_ptsl_enable']],
			]],
		]
	}
}
