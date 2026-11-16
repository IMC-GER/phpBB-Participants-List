<?php
/**
 * Participants List
 * An extension for the phpBB Forum Software package.
 *
 * @copyright (c) 2025, Thorsten Ahlers
 * @license GNU General Public License, version 2 (GPL-2.0)
 *
 */

namespace imcger\participantslist\controller;

use imcger\participantslist\ext;

class ptsl_list_controller
{
	private int $forum_id;
	private int $topic_id;
	private int $user_id;
	private int $ptsl_id;
	private bool $ptsl_is_mcp;
	private string $back_link;

	/**
	 * Constructor
	 */
	public function __construct
	(
		protected \phpbb\controller\helper $helper,
		protected \phpbb\user $user,
		protected \phpbb\auth\auth $auth,
		protected \phpbb\language\language $language,
		protected \phpbb\template\template $template,
		protected \phpbb\db\driver\driver_interface $db,
		protected \phpbb\request\request_interface $request,
		protected \phpbb\config\config $config,
		protected \phpbb\log\log_interface $phpbb_log,
		protected string $phpbb_root_path,
		protected string $phpEx,
		protected string $table_prefix,
	)
	{
		$this->forum_id	   = max(0, $this->request->variable('forum_id', 0));
		$this->topic_id	   = max(0, $this->request->variable('topic_id', 0));
		$this->user_id	   = max(0, $this->request->variable('user_id', 0));
		$this->forum_id	   = max(0, $this->forum_id ?: $this->request->variable('f', 0));
		$this->topic_id	   = max(0, $this->topic_id ?: $this->request->variable('t', 0));
		$this->user_id	   = max(0, $this->user_id  ?: $this->request->variable('u', 0));
		$this->ptsl_id	   = max(0, $this->request->variable('ptsl_id', 0));
		$this->ptsl_is_mcp = $this->request->variable('ptsl_mode', '') == 'mcp';
	}

	/**
	 * Display the page app.php/ptsl/{process}
	 */
	public function processing(string $process)
	{
		if (!($this->user->data['is_registered'] && $this->auth->acl_get('u_imcger_ptsl_view')))
		{
			redirect($this->phpbb_root_path . 'index.' . $this->phpEx);
		}

		$this->language->add_lang('ptsl_common', 'imcger/participantslist');
		$this->language->add_lang(['posting', 'mcp']);
		$title = $this->language->lang('PTSL_TITLE');
		$data  = [];

		add_form_key('imcger/participantslist');

		if ($this->ptsl_is_mcp)
		{
			$this->back_link = append_sid($this->phpbb_root_path . 'mcp.' . $this->phpEx, "f={$this->forum_id}&amp;t={$this->topic_id}&amp;i=mcp_main&amp;mode=topic_view#ptsl-anchor");
		}
		else
		{
			$this->back_link = append_sid($this->phpbb_root_path . 'viewtopic.' . $this->phpEx, "t={$this->topic_id}#ptsl-anchor");
		}

		switch ($process)
		{
			case 'add':
			case 'edit':
				$edit_page = '@imcger_participantslist/ptsl_edit_list_body.html';

				if (!function_exists('generate_text_for_storage'))
				{
					include($this->phpbb_root_path . 'includes/functions_content.' . $this->phpEx);
				}

				// Is the form being submitted to us?
				if ($this->request->is_set_post('submit'))
				{
					if (!check_form_key('imcger/participantslist'))
					{
						trigger_error($this->language->lang('FORM_INVALID') . $this->adm_back_link($this->back_link), E_USER_WARNING);
					}

					$comment  = $this->db->sql_escape(strip_tags($this->request->variable('ptsl_comment', '', true)));
					$comment  = str_replace(['\\t', '\\n', '\\r', '\\r\\n'], ' ', $comment);
					$bitfield = $uid = $flags = '';

					$warn_msg = generate_text_for_storage($comment, $uid, $bitfield, $flags, true, false, true, false, false, false, false, 'ptsl_comment');

					if (count($warn_msg))
					{
						$message = implode('<br>', $warn_msg);
						trigger_error($message . $this->adm_back_link($this->back_link), E_USER_WARNING);
					}

					$data['bbcode_bitfield'] = $bitfield;
					$data['bbcode_uid']		 = $uid;
					$data['ptsl_comment']	 = $comment;
					$data['user_id']		 = $this->user_id;
					$data['topic_id']	 	 = $this->topic_id;
					$data['ptsl_number'] 	 = max(1, min(100, $this->request->variable('ptsl_number', 1)));
					$data['ptsl_opt1'] 		 = max(0, min(100, $this->request->variable('ptsl_opt1', 0)));
					$data['ptsl_opt2'] 		 = max(0, min(100, $this->request->variable('ptsl_opt2', 0)));
					$data['ptsl_opt3'] 		 = max(0, min(100, $this->request->variable('ptsl_opt3', 0)));

					$this->check_permission($process, $this->get_list_data($process, $data['user_id'], $data['topic_id']));

					$this->set_list_data($process, $data);

					if ($this->ptsl_is_mcp)
					{
						$data = $this->get_list_data('edit', $this->user_id, $this->topic_id, $this->ptsl_id);

						$this->phpbb_log->add('mod', $this->user->data['user_id'], $this->user->ip, 'PTSL_LOG_EDIT_LIST_ENTRY', false, [
							'forum_id' => $data['forum_id'],
							'topic_id' => $data['topic_id'],
							$data['topic_title'],
							$data['username']
						]);
					}
				}
				else if ($this->request->is_set_post('cancel'))
				{
					break;
				}
				else
				{
					$data = $this->get_list_data($process, $this->user_id, $this->topic_id, $this->ptsl_id);

					if ($data === false)
					{
						trigger_error($this->language->lang('FORM_INVALID') . $this->adm_back_link($this->back_link), E_USER_WARNING);
					}

					$this->check_permission($process, $data);

					if ($process == 'edit')
					{
						$flags	 = OPTION_FLAG_BBCODE + OPTION_FLAG_SMILIES;
						$comment = generate_text_for_edit($data['ptsl_comment'], $data['bbcode_uid'], $flags)['text'];
					}

					$this->template->assign_vars([
						'U_PTSL_ACTION'		=> $this->helper->route('imcger_participantslist_list_controller', ['process' => $process]),
						'S_PTSL_IS_MCP'		=> $this->ptsl_is_mcp,
						'PTSL_ID'			=> $data['ptsl_id'] ?? 0,
						'PTSL_FORUM_ID'		=> $data['forum_id'] ?? 0,
						'PTSL_TOPIC_ID'		=> $data['topic_id'] ?? 0,
						'PTSL_TOPIC_TITLE'	=> $data['topic_title'] ?? '',
						'PTSL_USER_ID'		=> $data['user_id'] ?? 0,
						'PTSL_USERNAME'		=> $data['username'] ?? '',
						'PTSL_NUMBER'		=> $data['ptsl_number'] ?? 1,
						'PTSL_COMMENT'			 => censor_text($comment ?? ''),
						'PTSL_COMMENT_MAX_CHARS' => (int) $this->config['max_ptsl_comment_chars'],
						'PTSL_OPT1'				 => $data['ptsl_opt1'] ?? 0,
						'PTSL_COLUMN_OPT1'		 => $data['ptsl_column_opt1'] ?? 0,
						'PTSL_COLUMN_OPT1_NAME'	 => censor_text($data['ptsl_column_opt1_name'] ?? ''),
						'PTSL_COLUMN_OPT1_DESC'	 => censor_text($data['ptsl_column_opt1_desc'] ?? ''),
						'PTSL_OPT2'				 => $data['ptsl_opt2'] ?? 0,
						'PTSL_COLUMN_OPT2'		 => $data['ptsl_column_opt2'] ?? 0,
						'PTSL_COLUMN_OPT2_NAME'	 => censor_text($data['ptsl_column_opt2_name'] ?? ''),
						'PTSL_COLUMN_OPT2_DESC'	 => censor_text($data['ptsl_column_opt2_desc'] ?? ''),
						'PTSL_OPT3'				 => $data['ptsl_opt3'] ?? 0,
						'PTSL_COLUMN_OPT3'		 => $data['ptsl_column_opt3'] ?? 0,
						'PTSL_COLUMN_OPT3_NAME'	 => censor_text($data['ptsl_column_opt3_name'] ?? ''),
						'PTSL_COLUMN_OPT3_DESC'	 => censor_text($data['ptsl_column_opt3_desc'] ?? ''),
					]);

					$this->set_breadcrumb($data);

					return $this->helper->render($edit_page, $title);
				}

			break;

			case 'delete':
				if (!$this->check_form_key('imcger/participantslist'))
				{
					trigger_error($this->language->lang('FORM_INVALID') . $this->adm_back_link($this->back_link), E_USER_WARNING);
				}

				$data = $this->get_list_data('edit', $this->user_id, $this->topic_id, $this->ptsl_id);

				if ($data === false)
				{
					trigger_error($this->language->lang('FORM_INVALID') . $this->adm_back_link($this->back_link), E_USER_WARNING);
				}

				if ($this->check_permission($process, $data))
				{
					$sql = 'DELETE FROM ' . $this->table_prefix . ext::PTSL_DATA_TABLE . '
							WHERE ptsl_id = ' . (int) $data['ptsl_id'];

					$this->db->sql_query($sql);
				}

				if ($this->ptsl_is_mcp)
				{
					$this->phpbb_log->add('mod', $this->user->data['user_id'], $this->user->ip, 'PTSL_LOG_DEL_LIST_ENTRY', false, [
						'forum_id' => $data['forum_id'],
						'topic_id' => $data['topic_id'],
						$data['topic_title'],
						$data['username']
					]);
				}

			break;

			default:
				// Displays the start page of phpBB
				redirect($this->phpbb_root_path . 'index.' . $this->phpEx);
			break;
		}

		// Displays the participant list in MCP or topic view
		redirect($this->back_link);
	}

	/**
	 * Update and insert participant data in DB
	 */
	private function set_list_data(string $mode, array $data): void
	{
		if ($mode == 'edit')
		{
			$sql = 'UPDATE ' . $this->table_prefix . ext::PTSL_DATA_TABLE . '
					SET ' . $this->db->sql_build_array('UPDATE', $data) . '
					WHERE user_id = ' . (int) $data['user_id'] . '
						AND topic_id = ' . (int) $data['topic_id'];

			$this->db->sql_query($sql);
		}
		else if ($mode == 'add')
		{
			$sql = 'INSERT INTO ' . $this->table_prefix . ext::PTSL_DATA_TABLE . ' ' .
					$this->db->sql_build_array('INSERT', $data);

			$this->db->sql_query($sql);
		}
	}

	/**
	 * Get participant data from DB
	 */
	private function get_list_data(string $mode, int $user_id = 0, int $topic_id = 0, $ptsl_id = 0): array|bool
	{
		if (!(($user_id && $topic_id) || $ptsl_id))
		{
			return false;
		}

		$sql_array = [];

		$sql_array['edit'] = [
			'SELECT'    => 'pd.*, pt.*, u.username, t.topic_id, t.topic_title, t.forum_id',
			'FROM'      => [$this->table_prefix . ext::PTSL_DATA_TABLE => 'pd'],
			'LEFT_JOIN' => [
				[
					'FROM' => [USERS_TABLE => 'u'],
					'ON'   => 'pd.user_id = u.user_id',
				],
				[
					'FROM' => [TOPICS_TABLE => 't'],
					'ON'   => 't.topic_id = ' . (int) $topic_id,
				],
				[
					'FROM' => [$this->table_prefix . ext::PTSL_TABLE_DATA_TABLE => 'pt'],
					'ON'   => 'pt.topic_id = ' . (int) $topic_id,
				],
			],
			'WHERE'     => '(pd.topic_id = ' . (int) $topic_id . '
						AND pd.user_id = ' . (int) $user_id . ')
						OR pd.ptsl_id = ' . (int) $ptsl_id,
		];

		$sql_array['add'] = [
			'SELECT'    => 'pt.*, u.user_id, u.username, t.topic_id, t.topic_title, t.forum_id',
			'FROM'      => [USERS_TABLE => 'u'],
			'LEFT_JOIN' => [
				[
					'FROM' => [TOPICS_TABLE => 't'],
					'ON'   => 't.topic_id = ' . (int) $topic_id,
				],
				[
					'FROM' => [$this->table_prefix . ext::PTSL_TABLE_DATA_TABLE => 'pt'],
					'ON'   => 'pt.topic_id = ' . (int) $topic_id,
				],
			],
			'WHERE'     => 'u.user_id = ' . (int) $user_id,
		];

		$sql	= $this->db->sql_build_query('SELECT', $sql_array[$mode]);
		$result = $this->db->sql_query_limit($sql, 1);
		$data	= $this->db->sql_fetchrow($result);
		$this->db->sql_freeresult($result);

		return $data;
	}

	/**
	 * Check moderator and user permission
	 */
	private function check_permission(string $mode, array $data): bool
	{
		if (!(($this->auth->acl_get('m_' . $mode, $data['forum_id']) &&  $this->ptsl_id) || $data['user_id'] == $this->user->data['user_id']))
		{
			trigger_error($this->language->lang('PTSL_HAS_NO_PERMISSION') . $this->adm_back_link($this->back_link), E_USER_WARNING);

			return false;
		}

		return true;
	}

	/**
	 * Set breadcrumb menue for participant form
	 */
	private function set_breadcrumb(array $data): void
	{
		$plst_navlinks	= [];

		if ($this->ptsl_is_mcp)
		{
			$plst_navlinks	= [
				[
					'BREADCRUMB_NAME' => $this->language->lang('MCP'),
					'U_BREADCRUMB'	  => append_sid($this->phpbb_root_path . 'mcp.' . $this->phpEx, ''),
				],
				[
					'BREADCRUMB_NAME' => $this->language->lang('MCP_MAIN'),
					'U_BREADCRUMB'	  => append_sid($this->phpbb_root_path . 'mcp.' . $this->phpEx, "f={$data['forum_id']}&amp;t={$data['topic_id']}&amp;i=mcp_main"),
				],
				[
					'BREADCRUMB_NAME' => $this->language->lang('VIEW_TOPIC'),
					'U_BREADCRUMB'	  => append_sid($this->phpbb_root_path . 'mcp.' . $this->phpEx, "f={$data['forum_id']}&amp;t={$data['topic_id']}&amp;i=mcp_main&amp;mode=topic_view#ptsl-anchor"),
				],
			];

		}
		else
		{
			$sql_array = [
				'SELECT'    => 'f.forum_name, f.forum_id',
				'FROM'      => [FORUMS_TABLE => 'f'],
				'LEFT_JOIN' => [
					[
						'FROM' => [FORUMS_TABLE => 'ft'],
						'ON'   => 'ft.forum_id = ' . (int) $data['forum_id'],
					],
				],
				'WHERE'     => 'f.forum_id = ' . (int) $data['forum_id'] . '
						OR f.left_id < ft.left_id
						AND f.right_id > ft.right_id',
				'ORDER_BY'  => 'f.left_id ASC',
			];

			$sql    = $this->db->sql_build_query('SELECT', $sql_array);
			$result = $this->db->sql_query($sql);
			$rows	= $this->db->sql_fetchrowset($result);
			$this->db->sql_freeresult($result);

			foreach ($rows as $row)
			{
				$plst_navlinks[] = [
					'BREADCRUMB_NAME' => $row['forum_name'],
					'U_BREADCRUMB'	  => append_sid($this->phpbb_root_path . 'viewforum.' . $this->phpEx, 'f=' . $row['forum_id']),
				];
			}

			$plst_navlinks[] = [
				'BREADCRUMB_NAME' => $data['topic_title'],
				'U_BREADCRUMB'	  => append_sid($this->phpbb_root_path . 'viewtopic.' . $this->phpEx, "t={$data['topic_id']}"),
			];
		}

		$this->template->assign_vars(['navlinks' => $plst_navlinks, ]);
	}

	/**
	 * Form key check, if no “post” variables are available.
	 */
	function check_form_key(string $form_name, int|bool $timespan = false): bool
	{
		if ($timespan === false)
		{
			// we enforce a minimum value of half a minute here.
			$timespan = ($this->config['form_token_lifetime'] == -1) ? -1 : max(30, $this->config['form_token_lifetime']);
		}

		$creation_time	= abs($this->request->variable('creation_time', 0));
		$token			= $this->request->variable('form_token', '');

		if ($creation_time && $token)
		{
			$diff = time() - $creation_time;

			// If creation_time and the time() now is zero we can assume it was not a human doing this (the check for if ($diff)...
			if (defined('DEBUG_TEST') || $diff && ($diff <= $timespan || $timespan === -1))
			{
				$token_sid = ($this->user->data['user_id'] == ANONYMOUS && !empty($this->config['form_token_sid_guests'])) ? $this->user->session_id : '';
				$key = sha1($creation_time . $this->user->data['user_form_salt'] . $form_name . $token_sid);

				if ($key === $token)
				{
					return true;
				}
			}
		}

		return false;
	}

	/**
	* Generate back link
	*/
	function adm_back_link(string $link): string
	{
		return '<br><br><a href="' . $link . '">&laquo; ' . $this->language->lang('BACK_TO_PREV') . '</a>';
	}
}
