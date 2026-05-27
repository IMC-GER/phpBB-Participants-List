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

class s_1_3_2 extends \phpbb\db\migration\migration
{
	public function effectively_installed()
	{
		return $this->db_tools->sql_column_exists($this->table_prefix . ext::PTSL_TABLE_DATA_TABLE, 'post_not_visibility');
	}

	public static function depends_on()
	{
		return ['\imcger\participantslist\migrations\s_1_3_0'];
	}

	public function update_schema()
	{
		return [
			'add_columns' => [
				$this->table_prefix . ext::PTSL_TABLE_DATA_TABLE => [
					'post_not_visibility' => ['ULINT', 0],
				],
			],
		];
	}

	public function revert_schema()
	{
		return [
			'drop_columns' => [
				$this->table_prefix . ext::PTSL_TABLE_DATA_TABLE => [
					'post_not_visibility',
				],
			],
		];
	}
}
