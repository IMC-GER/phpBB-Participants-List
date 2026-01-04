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
				['permission.role_exists', ['ROLE_FORUM_NOACCESS']],
				['permission.permission_set', ['ROLE_FORUM_NOACCESS', 'f_imcger_ptsl_enable', 'role', false]],
			]],
		];
	}
}
