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

class s_1_3_0 extends \phpbb\db\migration\migration
{
	public function effectively_installed()
	{
		return $this->db_tools->sql_table_exists($this->table_prefix . ext::PTSL_TABLE_DATA_TABLE);
	}

	public static function depends_on()
	{
		return ['\imcger\participantslist\migrations\s_1_2_0'];
	}

	public function update_schema()
	{
		return [
			'add_tables' => [
				$this->table_prefix . ext::PTSL_TABLE_DATA_TABLE => [
					'COLUMNS' => [
						'topic_id'				=> ['ULINT', 0],
						'ptsl_column_opt1'		=> ['USINT', 0],
						'ptsl_column_opt1_name'	=> ['VCHAR:32', ''],
						'ptsl_column_opt1_desc'	=> ['STEXT_UNI', ''],
						'ptsl_column_opt2'		=> ['USINT', 0],
						'ptsl_column_opt2_name'	=> ['VCHAR:32', ''],
						'ptsl_column_opt2_desc'	=> ['STEXT_UNI', ''],
						'ptsl_column_opt3'		=> ['USINT', 0],
						'ptsl_column_opt3_name'	=> ['VCHAR:32', ''],
						'ptsl_column_opt3_desc'	=> ['STEXT_UNI', ''],
					],
					'PRIMARY_KEY' => 'topic_id',
				],
			],
			'change_columns' => [
					$this->table_prefix . ext::PTSL_DATA_TABLE => [
					'topic_id'	   => ['ULINT', 0],
					'ptsl_comment' => ['TEXT_UNI', ''],
				],
			],
		];
	}

	public function revert_schema()
	{
		return [
			'drop_tables' => [
				$this->table_prefix . ext::PTSL_TABLE_DATA_TABLE,
			],
		];
	}
}
