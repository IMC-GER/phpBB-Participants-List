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

class s_1_0_0 extends \phpbb\db\migration\migration
{
	public function effectively_installed()
	{
		return $this->db_tools->sql_column_exists(TOPICS_TABLE, 'topic_ptsl_disp');
	}

	public static function depends_on()
	{
		return ['\phpbb\db\migration\data\v33x\v331'];
	}

	public function update_schema()
	{
		return [
			'add_tables' => [
				$this->table_prefix . ext::PTSL_DATA_TABLE => [
					'COLUMNS' => [
						'ptsl_id'		=> ['UINT', null, 'auto_increment'],
						'topic_id'		=> ['UINT', 0],
						'user_id'		=> ['UINT', 0],
						'ptsl_number'	=> ['UINT', 0],
						'ptsl_comment'	=> ['VCHAR:255', ''],
					],
					'PRIMARY_KEY' => 'ptsl_id',
				],
			],
			'add_columns' => [
				TOPICS_TABLE => [
					'topic_ptsl_disp' => ['BOOL', 0],
				],
			],
		];
	}

	public function revert_schema()
	{
		return [
			'drop_tables' => [
				$this->table_prefix . ext::PTSL_DATA_TABLE,
			],
			'drop_columns' => [
				TOPICS_TABLE => [
					'topic_ptsl_disp',
				],
			],
		];
	}
}
