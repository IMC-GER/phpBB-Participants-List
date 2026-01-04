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

class v_1_1_0  extends \phpbb\db\migration\migration
{
	public static function depends_on()
	{
		return ['\imcger\participantslist\migrations\v_1_0_0'];
	}

	public function update_data()
	{
		return [
			['permission.add', ['u_imcger_ptsl_view']],

			['permission.permission_set', ['REGISTERED', 'u_imcger_ptsl_view', 'group']],

			['if', [
				['permission.role_exists', ['ROLE_USER_FULL']],
				['permission.permission_set', ['ROLE_USER_FULL', 'u_imcger_ptsl_view']],
			]],
			['if', [
				['permission.role_exists', ['ROLE_USER_STANDARD']],
				['permission.permission_set', ['ROLE_USER_STANDARD', 'u_imcger_ptsl_view']],
			]],
		];
	}
}
