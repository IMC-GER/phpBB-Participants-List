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

class v_1_3_2 extends \phpbb\db\migration\migration
{
	public function effectively_installed(): bool
	{
		return isset($this->config['min_ptsl_comment_chars']);
	}

	public static function depends_on(): array
	{
		return ['\imcger\participantslist\migrations\s_1_3_2'];
	}

	public function update_data(): array
	{
		return [
			// Settings for text parser
			['config.add', ['min_ptsl_comment_chars', 0]],
			['config.add', ['max_ptsl_comment_chars', 255]],

			// Add table data entrys for older pts.-lists
			['custom', [[$this, 'update_table_data']]],

		];
	}

	public function update_table_data()
	{
		$ptsl_table_data = [
			'ptsl_column_opt1'		=> 0,
			'ptsl_column_opt1_name'	=> '',
			'ptsl_column_opt1_desc'	=> '',
			'ptsl_column_opt2'		=> 0,
			'ptsl_column_opt2_name'	=> '',
			'ptsl_column_opt2_desc'	=> '',
			'ptsl_column_opt3'		=> 0,
			'ptsl_column_opt3_name'	=> '',
			'ptsl_column_opt3_desc'	=> '',
			'post_not_visibility'	=> 0,
		];

		$sql_array = [
			'SELECT'    => 't.topic_id',
			'FROM'      => [TOPICS_TABLE => 't'],
			'LEFT_JOIN' => [
				[
					'FROM' => [$this->table_prefix . ext::PTSL_TABLE_DATA_TABLE => 'ptd'],
					'ON'   => 'ptd.topic_id > 0',
				],
				[
					'FROM' => [$this->table_prefix . ext::PTSL_DATA_TABLE => 'pd'],
					'ON'   => 'pd.topic_id <> ptd.topic_id',
				],
			],
			'WHERE'     => 't.topic_ptsl_disp = 1 OR t.topic_id = pd.topic_id',
		];

		$sql	= $this->db->sql_build_query('SELECT_DISTINCT', $sql_array);
		$result = $this->db->sql_query($sql);
		$rowset = $this->db->sql_fetchrowset($result);
		$this->db->sql_freeresult($result);

		foreach ($rowset as $row)
		{
			$sql = 'INSERT INTO ' . $this->table_prefix . ext::PTSL_TABLE_DATA_TABLE . ' ' .
					$this->db->sql_build_array('INSERT', array_merge($ptsl_table_data, ['topic_id' => (int) $row['topic_id']]));

			$this->db->sql_query($sql);
		}
	}
}
