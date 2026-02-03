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

class s_1_2_0 extends \phpbb\db\migration\migration
{
	public function effectively_installed()
	{
		return $this->db_tools->sql_column_exists($this->table_prefix . ext::PTSL_DATA_TABLE, 'ptsl_opt1');
	}

	public static function depends_on()
	{
		return ['\imcger\participantslist\migrations\s_1_1_0'];
	}

	public function update_schema()
	{
		return [
			'add_columns' => [
				$this->table_prefix . ext::PTSL_DATA_TABLE => [
					'ptsl_opt1'	=> ['USINT', 0],
					'ptsl_opt2'	=> ['USINT', 0],
					'ptsl_opt3'	=> ['USINT', 0],
				],
			],
		];
	}

	public function revert_schema()
	{
		return [
			'drop_columns' => [
				$this->table_prefix . ext::PTSL_DATA_TABLE => [
					'ptsl_opt1',
					'ptsl_opt2',
					'ptsl_opt3',
				],
			],
		];
	}
}
