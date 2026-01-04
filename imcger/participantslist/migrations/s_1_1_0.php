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

use imcger\participantslist\ext;

class s_1_1_0 extends \phpbb\db\migration\migration
{
	public static function depends_on()
	{
		return ['\imcger\participantslist\migrations\v_1_1_0'];
	}

	public function update_schema()
	{
		return [
			'change_columns' => [
					$this->table_prefix . ext::PTSL_DATA_TABLE => [
					'ptsl_comment' => ['STEXT_UNI', ''],
				],
			],
			'add_columns' => [
				$this->table_prefix . ext::PTSL_DATA_TABLE => [
					'bbcode_bitfield' => ['VCHAR:255', ''],
					'bbcode_uid'	  => ['VCHAR:8', ''],
				],
			],
		];
	}

	public function revert_schema()
	{
		return [
			'drop_columns' => [
				$this->table_prefix . ext::PTSL_DATA_TABLE => [
					'bbcode_bitfield',
					'bbcode_uid',
				],
			],
		];
	}
}
