<?php
/**
 * Participants List
 * An extension for the phpBB Forum Software package.
 *
 * @copyright (c) 2025, Thorsten Ahlers
 * @license GNU General Public License, version 2 (GPL-2.0)
 *
 */

namespace imcger\participantslist\event;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use imcger\participantslist\ext;

class ptsl_main_listener implements EventSubscriberInterface
{
	private	  string $post_delete_conditions;
	protected string $sortby;

	public function __construct
	(
		protected \phpbb\user $user,
		protected \phpbb\auth\auth $auth,
		protected \phpbb\language\language $language,
		protected \phpbb\template\template $template,
		protected \phpbb\db\driver\driver_interface $db,
		protected \phpbb\request\request_interface $request,
		protected \phpbb\controller\helper $helper,
		protected string $phpbb_root_path,
		protected string $phpEx,
		protected string $table_prefix,
	)
	{
		$this->post_delete_conditions = 0;
		$this->sort_dir = $this->request->variable('ptsl-sd', '');
	}

	/**
	 * Assign functions defined in this class to event listeners in the core
	 */
	public static function getSubscribedEvents(): array
	{
		return [
			'core.posting_modify_post_data'			=> 'posting_modify_post_data',
			'core.posting_modify_submission_errors'	=> 'posting_modify_submission_errors',
			'core.posting_modify_submit_post_after'	=> 'posting_modify_submit_post_after',
			'core.posting_modify_template_vars'		=> 'posting_modify_template_vars',
			'core.viewtopic_get_post_data'			=> 'set_template_vars',
			'core.user_setup_after'					=> 'user_setup_after',
			'core.handle_post_delete_conditions'	=> 'handle_post_delete_conditions',
			'core.delete_post_after'				=> 'delete_post_after',
		];
	}

	/**
	 * Display checkbox in editor only on first post
	 */
	public function posting_modify_post_data(object $event): void
	{
		$post_data = $event['post_data'];

		if (!isset($event['post_data']['topic_ptsl_disp']))
		{
			$post_data += ['topic_ptsl_disp' 	=> 0];
		}

		$post_data += ['ptsl_column_opt1'		=> 0];
		$post_data += ['ptsl_column_opt2'		=> 0];
		$post_data += ['ptsl_column_opt3'		=> 0];
		$post_data += ['ptsl_column_opt1_name'	=> ''];
		$post_data += ['ptsl_column_opt2_name'	=> ''];
		$post_data += ['ptsl_column_opt3_name'	=> ''];
		$post_data += ['ptsl_column_opt1_desc'	=> ''];
		$post_data += ['ptsl_column_opt2_desc'	=> ''];
		$post_data += ['ptsl_column_opt3_desc'	=> ''];
		$post_data += ['ptsl_can_add_list'		=> false];

		// Only show ptsl panel when editing the first post in topic
		if (($event['mode'] == 'post' || ($event['mode'] == 'edit') && $event['post_id'] == $event['post_data']['topic_first_post_id']) && $this->auth->acl_get('f_imcger_ptsl_enable', $event['forum_id']) && $this->auth->acl_get('u_imcger_ptsl_view'))
		{
			$post_data['ptsl_can_add_list'] = true;

			$this->template->assign_vars([
				'S_PTSL_CAN_ADD_LIST' => true,
			]);

			$sql = 'SELECT *
					FROM ' . $this->table_prefix . ext::PTSL_TABLE_DATA_TABLE . '
					WHERE topic_id = ' . (int) $event['topic_id'];

			$result			 = $this->db->sql_query_limit($sql, 1);
			$ptsl_table_data = $this->db->sql_fetchrow($result);
			$this->db->sql_freeresult($result);

			if ($ptsl_table_data != false)
			{
				unset($ptsl_table_data['topic_id']);

				$post_data = array_replace($post_data, $ptsl_table_data);
			}
		}

		$event['post_data'] = $post_data;
	}

	/**
	 * Get checkbox state and update post data
	 */
	public function posting_modify_submission_errors(object $event): void
	{
		if ($event['post_data']['ptsl_can_add_list'])
		{
			$post_data = $event['post_data'];
			$post_data['topic_ptsl_disp']		= (int) $this->request->variable('topic_ptsl_disp', 0);
			$post_data['ptsl_column_opt1']		= (int) $this->request->variable('ptsl_column_opt1', 0);
			$post_data['ptsl_column_opt2']		= (int) $this->request->variable('ptsl_column_opt2', 0);
			$post_data['ptsl_column_opt3']		= (int) $this->request->variable('ptsl_column_opt3', 0);
			$post_data['ptsl_column_opt1_name'] = $this->db->sql_escape($this->request->variable('ptsl_column_opt1_name', '', true));
			$post_data['ptsl_column_opt2_name'] = $this->db->sql_escape($this->request->variable('ptsl_column_opt2_name', '', true));
			$post_data['ptsl_column_opt3_name'] = $this->db->sql_escape($this->request->variable('ptsl_column_opt3_name', '', true));
			$post_data['ptsl_column_opt1_desc'] = $this->db->sql_escape($this->request->variable('ptsl_column_opt1_desc', '', true));
			$post_data['ptsl_column_opt2_desc'] = $this->db->sql_escape($this->request->variable('ptsl_column_opt2_desc', '', true));
			$post_data['ptsl_column_opt3_desc'] = $this->db->sql_escape($this->request->variable('ptsl_column_opt3_desc', '', true));
			$event['post_data'] = $post_data;
		}
	}


	/**
	 * Update topic table after submit
	 */
	public function posting_modify_submit_post_after(object $event): void
	{
		if ($event['post_data']['ptsl_can_add_list'])
		{
			$topic_id = $event['topic_id'];
			$topic_ptsl_disp = $event['post_data']['topic_ptsl_disp'] ?? 0;

			if ($topic_id < 1)
			{
				// Get ID from new topic
				$parts = parse_url($event['redirect_url']);
				parse_str($parts['query'], $query);
				$topic_id = (int) $query['t'];
			}

			$sql = 'UPDATE ' . TOPICS_TABLE . '
					SET topic_ptsl_disp = ' . $topic_ptsl_disp . '
					WHERE topic_id = ' . (int) $topic_id;

			$this->db->sql_query($sql);

			if ($topic_ptsl_disp)
			{
				$sql		= 'SELECT topic_id FROM ' . $this->table_prefix . ext::PTSL_TABLE_DATA_TABLE . ' WHERE topic_id = ' . (int) $topic_id;
				$result		= $this->db->sql_query($sql);
				$row_exists = $this->db->sql_fetchrow($result);
				$this->db->sql_freeresult($result);

				// Filters the array by the key prefix
				$ptsl_table_data = array_filter($event['post_data'], fn($key) => str_starts_with($key, 'ptsl_column_'), ARRAY_FILTER_USE_KEY);

				if ($row_exists)
				{
					$sql = 'UPDATE ' . $this->table_prefix . ext::PTSL_TABLE_DATA_TABLE . '
							SET ' . $this->db->sql_build_array('UPDATE', $ptsl_table_data) . '
							WHERE topic_id = ' . (int) $topic_id;
				}
				else
				{
					$sql = 'INSERT INTO ' . $this->table_prefix . ext::PTSL_TABLE_DATA_TABLE . ' ' .
							$this->db->sql_build_array('INSERT', array_merge($ptsl_table_data, ['topic_id' => (int) $topic_id]));
				}

				$this->db->sql_query($sql);
			}
			else
			{
				$sql = 'DELETE FROM ' . $this->table_prefix . ext::PTSL_TABLE_DATA_TABLE . '
						WHERE topic_id = ' . (int) $event['topic_id'];
				$this->db->sql_query($sql);
			}
		}
	}

	/**
	 * Set template vars in editor
	 */
	public function posting_modify_template_vars(object $event): void
	{
		if ($event['post_data']['ptsl_can_add_list'])
		{
			$page_data = $event['page_data'];
			$page_data = array_merge($page_data, [
					'TOPIC_PTSL_DISP'		=> $event['post_data']['topic_ptsl_disp'],
					'PTSL_COLUMN_OPT1'		=> $this->select_struct((int) $event['post_data']['ptsl_column_opt1'] , [
						'PTSL_NONE'		=> 0,
						'PTSL_CHECKBOX'	=> 1,
						'PTSL_NUMBERS'	=> 2,
					]),
					'PTSL_COLUMN_OPT2'		=> $this->select_struct((int) $event['post_data']['ptsl_column_opt2'], [
						'PTSL_NONE'		=> 0,
						'PTSL_CHECKBOX'	=> 1,
						'PTSL_NUMBERS'	=> 2,
					]),
					'PTSL_COLUMN_OPT3'		=> $this->select_struct((int) $event['post_data']['ptsl_column_opt3'], [
						'PTSL_NONE'		=> 0,
						'PTSL_CHECKBOX'	=> 1,
						'PTSL_NUMBERS'	=> 2,
					]),
					'PTSL_COLUMN_OPT1_NAME'	=> $event['post_data']['ptsl_column_opt1_name'],
					'PTSL_COLUMN_OPT2_NAME'	=> $event['post_data']['ptsl_column_opt2_name'],
					'PTSL_COLUMN_OPT3_NAME'	=> $event['post_data']['ptsl_column_opt3_name'],
					'PTSL_COLUMN_OPT1_DESC'	=> $event['post_data']['ptsl_column_opt1_desc'],
					'PTSL_COLUMN_OPT2_DESC'	=> $event['post_data']['ptsl_column_opt2_desc'],
					'PTSL_COLUMN_OPT3_DESC'	=> $event['post_data']['ptsl_column_opt3_desc'],
				]);

			$event['page_data'] = $page_data;
		}
	}

	/**
	 * Set template vars in viewtopic
	 */
	public function set_template_vars(object $event): void
	{
		$ptsl_go_to_list = $this->auth->acl_get('u_imcger_ptsl_view') && $event['topic_data']['topic_ptsl_disp'];
		$ptsl_u_view	 = $ptsl_go_to_list && in_array($event['topic_data']['topic_first_post_id'], $event['post_list']);

		if ($ptsl_go_to_list)
		{
			$this->template->assign_vars([
				'S_PTSL_GO_TO_LIST' => true,
				'U_PTSL_GO_TO_LIST' => append_sid($this->phpbb_root_path . 'viewtopic.' . $this->phpEx, "t={$event['topic_id']}#ptsl-anchor"),
			]);
		}

		if ($ptsl_u_view)
		{
			$user_id		 = $this->user->data['user_id'];
			$user_inlist	 = false;
			$topic_id		 = $event['topic_id'];
			$ptsl_number_pts = $ptsl_number_opt1 = $ptsl_number_opt2 = $ptsl_number_opt3 = 0;
			$ptsl_m_edit	 = $this->auth->acl_get('m_edit', $event['forum_id']);
			$ptsl_m_delete	 = $this->auth->acl_get('m_delete', $event['forum_id']);
			$url_list_add	 = $this->helper->route('imcger_participantslist_list_controller', ['process' => 'add']);
			$url_list_edit	 = $this->helper->route('imcger_participantslist_list_controller', ['process' => 'edit']);
			$url_list_del	 = $this->helper->route('imcger_participantslist_list_controller', ['process' => 'delete']);

			$sql_array = [
				'SELECT'    => 'pd.*, u.username, u.user_colour',
				'FROM'      => [$this->table_prefix . ext::PTSL_DATA_TABLE => 'pd'],
				'LEFT_JOIN' => [
					[
						'FROM' => [USERS_TABLE => 'u'],
						'ON'   => 'pd.user_id = u.user_id',
					],
				],
				'WHERE'     => 'pd.topic_id = ' . (int) $topic_id,
			];

			$query_string = $this->user->page['query_string'];
			parse_str($query_string, $query_para);

			// Set default sort direction
			$alter_query_para = array_merge($query_para, ['u' => $user_id]);
			$sort_query_para  = array_merge($query_para, ['ptsl-sd' => 'a']);

			if ($this->sort_dir == 'a')
			{
				$sql_array += ['ORDER_BY'  => 'u.username_clean ASC'];
				$sort_query_para['ptsl-sd'] = 'd';
			}
			else if ($this->sort_dir == 'd')
			{
				$sql_array += ['ORDER_BY'  => 'u.username_clean DESC'];
				$sort_query_para['ptsl-sd'] = 'a';
			}

			$sql    = $this->db->sql_build_query('SELECT', $sql_array);
			$result = $this->db->sql_query($sql);

			$ptsl_table = [];
			$flags		= OPTION_FLAG_BBCODE + OPTION_FLAG_SMILIES;

			while ($row = $this->db->sql_fetchrow())
			{
				$comment = generate_text_for_display($row['ptsl_comment'], $row['bbcode_uid'], $row['bbcode_bitfield'], $flags, true);

				$ptsl_table[] = [
					'PTSL_ID'			 => $row['ptsl_id'],
					'PTSL_USER_ID'		 => $row['user_id'],
					'PTSL_USERNAME'		 => $row['username'],
					'PTSL_USERNAME_FULL' => get_username_string('full', $row['user_id'], $row['username'], $row['user_colour']),
					'PTSL_NUMBER'		 => $row['ptsl_number'],
					'PTSL_OPT1'			 => $row['ptsl_opt1'],
					'PTSL_OPT2'			 => $row['ptsl_opt2'],
					'PTSL_OPT3'			 => $row['ptsl_opt3'],
					'PTSL_COMMENT'		 => $comment,
					'U_PTSL_MOD_EDIT'	 => append_sid($url_list_edit, "t={$topic_id}&amp;id={$row['ptsl_id']}"),
					'U_PTSL_MOD_DEL'	 => append_sid($url_list_del, "t={$topic_id}&amp;id={$row['ptsl_id']}"),
				];

				$ptsl_number_pts  += $row['ptsl_number'];
				$ptsl_number_opt1 += $row['ptsl_opt1'];
				$ptsl_number_opt2 += $row['ptsl_opt2'];
				$ptsl_number_opt3 += $row['ptsl_opt3'];

				if ($row['user_id'] == $this->user->data['user_id'])
				{
					$user_inlist = true;
				}
			}
			$this->db->sql_freeresult($result);

			$sql = 'SELECT *
					FROM ' . $this->table_prefix . ext::PTSL_TABLE_DATA_TABLE . '
					WHERE topic_id = ' . (int) $topic_id;

			$result		= $this->db->sql_query($sql);
			$ptsl_data	= $this->db->sql_fetchrow($result);
			$this->db->sql_freeresult($result);

			$this->template->assign_vars([
				'ptsl_table'			=> $ptsl_table,
				'PTSL_TOPIC_ID'			=> $topic_id,
				'PTSL_NUMBER_PTS'		=> $ptsl_number_pts,
				'PTSL_NUMBER_OPT1'		=> $ptsl_number_opt1,
				'PTSL_NUMBER_OPT2'		=> $ptsl_number_opt2,
				'PTSL_NUMBER_OPT3'		=> $ptsl_number_opt3,
				'PTSL_NUMBER_OPT_DISP'	=> $ptsl_data['ptsl_column_opt1'] || $ptsl_data['ptsl_column_opt2'] || $ptsl_data['ptsl_column_opt3'],
				'PTSL_USERNAME'			=> $this->user->data['username'],
				'PTSL_COLUMN_OPT1'		=> $ptsl_data['ptsl_column_opt1'],
				'PTSL_COLUMN_OPT2'		=> $ptsl_data['ptsl_column_opt2'],
				'PTSL_COLUMN_OPT3'		=> $ptsl_data['ptsl_column_opt3'],
				'PTSL_COLUMN_OPT1_NAME'	=> $ptsl_data['ptsl_column_opt1_name'],
				'PTSL_COLUMN_OPT2_NAME'	=> $ptsl_data['ptsl_column_opt2_name'],
				'PTSL_COLUMN_OPT3_NAME'	=> $ptsl_data['ptsl_column_opt3_name'],
				'PTSL_SORT_DIRECTION'	=> $alter_query_para['ptsl-sd'] ?? '' ,
				'S_PTSL_GO_TO_LIST'		=> true,
				'S_PTSL_CAN_VIEW_LIST'	=> $ptsl_u_view,
				'S_PTSL_M_EDIT'			=> $ptsl_m_edit,
				'S_PTSL_M_DELETE'		=> $ptsl_m_delete,
				'S_PTSL_USER_IN_LIST'	=> $user_inlist,
				'U_PTSL_GO_TO_LIST'		=> append_sid($this->phpbb_root_path . 'viewtopic.' . $this->phpEx, $query_para) . '#ptsl-anchor',
				'U_PTSL_SORT_BY_NAME'	=> append_sid($this->phpbb_root_path . 'viewtopic.' . $this->phpEx, $sort_query_para) . '#ptsl-anchor',
				'U_PTSL_ADD_TO_LIST'	=> append_sid($url_list_add, $alter_query_para),
				'U_PTSL_EDIT_LIST'		=> append_sid($url_list_edit, $alter_query_para),
				'U_PTSL_DEL_FROM_LIST'	=> append_sid($url_list_del, $alter_query_para),
			]);
		}
	}

	/**
	 * Add language file
	 */
	public function user_setup_after(): void
	{
		$this->language->add_lang('ptsl_common', 'imcger/participantslist');
	}

	/**
	 * Check if participant list is in topic.
	 */
	public function handle_post_delete_conditions(object $event)
	{
		$this->post_delete_conditions = $event['post_data']['topic_ptsl_disp'];
	}

	/**
	 * Delete the participant list when the associated post is deleted.
	 */
	public function delete_post_after(object $event)
	{
		if (($this->post_delete_conditions || $this->auth->acl_get('f_imcger_ptsl_enable', $event['forum_id'])) && ($event['post_id'] == $event['data']['topic_first_post_id']))
		{
			$sql = 'DELETE FROM ' . $this->table_prefix . ext::PTSL_DATA_TABLE . '
					WHERE topic_id = ' . (int) $event['topic_id'];
			$this->db->sql_query($sql);

			$sql = 'DELETE FROM ' . $this->table_prefix . ext::PTSL_TABLE_DATA_TABLE . '
					WHERE topic_id = ' . (int) $event['topic_id'];
			$this->db->sql_query($sql);

			$sql = 'UPDATE ' . TOPICS_TABLE . '
					SET topic_ptsl_disp = 0
					WHERE topic_id = ' . (int) $event['topic_id'];
			$this->db->sql_query($sql);
		}
	}

	/*
	 * Creates an array of variables for the SelectBox macro
	 */
	public function select_struct(array|string $cfg_value, array $options): array
	{
		$options_tpl = [];

		foreach ($options as $opt_key => $opt_value)
		{
			if (!is_array($opt_value))
			{
				$opt_value = [$opt_value];
			}
			$options_tpl[] = [
				'label'		=> $opt_key,
				'value'		=> $opt_value[0],
				'bold'		=> $opt_value[1] ?? false,
				'selected'	=> is_array($cfg_value) ? in_array($opt_value[0], $cfg_value) : $opt_value[0] == $cfg_value,
			];
		}

		return $options_tpl;
	}
}
