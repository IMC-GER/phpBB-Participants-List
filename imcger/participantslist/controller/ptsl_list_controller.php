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
	private int $topic_id;
	private int $user_id;
	private int $ptsl_id;

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
		protected string $phpbb_root_path,
		protected string $phpEx,
		protected string $table_prefix,
	)
	{
		$this->topic_id	= $this->request->variable('t', 0);
		$this->user_id	= $this->request->variable('u', 0);
		$this->ptsl_id	= $this->request->variable('id', 0);
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
		$this->language->add_lang('posting');
		$title = $this->language->lang('PTSL_TITLE');

		switch ($process)
		{
			case 'add':
			case 'edit':
				$data = [];
				$edit_page = '@imcger_participantslist/ptsl_edit_list_body.html';
				add_form_key('imcger\participantslist');

				if (!class_exists('parse_message'))
				{
					include($this->phpbb_root_path . 'includes/message_parser.' . $this->phpEx);
				}

				// Is the form being submitted to us?
				if ($this->request->is_set_post('submit'))
				{
					if (!check_form_key('imcger\participantslist'))
					{
						trigger_error($this->language->lang('FORM_INVALID') . '<br><br><a href="' . append_sid($this->phpbb_root_path . 'viewtopic.' . $this->phpEx, "t={$this->topic_id}#ptsl_anchor") . '">&laquo; ' . $this->language->lang('BACK_TO_PREV') . '</a>', E_USER_WARNING);
					}

					$comment  = $this->db->sql_escape(censor_text($this->request->variable('ptsl_comment', '', true)));
					$comment  = str_replace("\\n", " ", $comment);
					$bitfield = $uid = '';
					$flags	  = 0;

					$warn_msg = generate_text_for_storage($comment, $uid, $bitfield, $flags, false, false, false, false, false, false, false, 'post');

					if (count($warn_msg))
					{
						$message = implode('<br>', $warn_msg);
						trigger_error($message . '<br><br><a href="' . append_sid($this->phpbb_root_path . 'viewtopic.' . $this->phpEx, "t={$this->topic_id}#ptsl_anchor") . '">&laquo; ' . $this->language->lang('BACK_TO_PREV') . '</a>', E_USER_WARNING);
					}

					$data['bbcode_bitfield'] = $bitfield;
					$data['bbcode_uid']		 = $uid;
					$data['ptsl_comment']	 = $comment;
					$data['user_id']		 = $this->request->variable('user_id', 0);
					$data['topic_id']	 	 = $this->request->variable('topic_id', 0);
					$data['ptsl_number'] 	 = $this->request->variable('ptsl_number', 1);

					$this->check_permission($process, $this->get_list_data($process, $data['user_id'], $data['topic_id']));

					$this->set_list_data($process, $data);
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
						trigger_error($this->language->lang('FORM_INVALID') . '<br><br><a href="' . append_sid($this->phpbb_root_path . 'viewtopic.' . $this->phpEx, "t={$this->topic_id}#ptsl_anchor") . '">&laquo; ' . $this->language->lang('BACK_TO_PREV') . '</a>', E_USER_WARNING);
					}

					$this->check_permission($process, $data);

					if ($process == 'edit')
					{
						$comment = generate_text_for_edit($data['ptsl_comment'], $data['bbcode_uid'], 0)['text'];
					}

					$this->template->assign_vars([
						'PTSL_ID'			=> $data['ptsl_id'] ?? 0,
						'PTSL_TOPIC_ID'		=> $data['topic_id'] ?? 0,
						'PTSL_TOPIC_TITLE'	=> $data['topic_title'] ?? '',
						'PTSL_USER_ID'		=> $data['user_id'] ?? 0,
						'PTSL_USERNAME'		=> $data['username'] ?? '',
						'PTSL_NUMBER'		=> $data['ptsl_number'] ?? 1,
						'PTSL_COMMENT'		=> $comment ?? '',
					]);

					$this->set_breadcrumb($data);

					return $this->helper->render($edit_page, $title);
				}

			break;

			case 'delete':
				$data = $this->get_list_data('edit', $this->user_id, $this->topic_id, $this->ptsl_id);

				if ($data === false)
				{
					trigger_error($this->language->lang('FORM_INVALID') . '<br><br><a href="' . append_sid($this->phpbb_root_path . 'viewtopic.' . $this->phpEx, "t={$this->topic_id}#ptsl_anchor") . '">&laquo; ' . $this->language->lang('BACK_TO_PREV') . '</a>', E_USER_WARNING);
				}

				if ($this->check_permission($process, $data))
				{
					$ptsl_id = $this->ptsl_id ?: $data['ptsl_id'];
					$sql	 = 'DELETE FROM ' . $this->table_prefix . ext::PTSL_DATA_TABLE . ' WHERE ptsl_id = ' . (int) $ptsl_id;

					$this->db->sql_query($sql);
				}

			break;

			default:
				// Displays the start page of phpBB
				redirect($this->phpbb_root_path . 'index.' . $this->phpEx);
			break;
		}

		// Displays the participant list
		redirect(append_sid($this->phpbb_root_path . 'viewtopic.' . $this->phpEx, "t={$this->topic_id}#ptsl_anchor"));
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
		$sql_array = [];

		$sql_array['edit'] = [
			'SELECT'    => 'pd.*, u.username, t.topic_id, t.topic_title, t.forum_id',
			'FROM'      => [$this->table_prefix . ext::PTSL_DATA_TABLE => 'pd'],
			'LEFT_JOIN' => [
				[
					'FROM' => [USERS_TABLE => 'u'],
					'ON'   => 'pd.user_id = u.user_id',
				],
				[
					'FROM' => [TOPICS_TABLE => 't'],
					'ON'   => 'pd.topic_id = t.topic_id',
				],
			],
			'WHERE'     => 'pd.topic_id = ' . (int) $topic_id . '
						AND pd.user_id = ' . (int) $user_id . '
						OR pd.ptsl_id = ' . (int) $ptsl_id,
		];

		$sql_array['add'] = [
			'SELECT'    => 'u.user_id, u.username, t.topic_id, t.topic_title, t.forum_id',
			'FROM'      => [USERS_TABLE => 'u'],
			'LEFT_JOIN' => [
				[
					'FROM' => [TOPICS_TABLE => 't'],
					'ON'   => 't.topic_id = ' . (int) $topic_id,
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
			trigger_error($this->language->lang('PTSL_HAS_NO_PERMISSION') . '<br><br><a href="' . $this->phpbb_root_path . 'index.' . $this->phpEx . '">&laquo; ' . $this->language->lang('BACK_TO_PREV') . '</a>', E_USER_WARNING);

			return false;
		}

		return true;
	}

	/**
	 * Set breadcrumb menue for participant form
	 */
	private function set_breadcrumb(array $data): void
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

		$plst_navlinks	= [];

		foreach ($rows as $row)
		{
			$plst_navlinks[] = [
				'BREADCRUMB_NAME'	=> $row['forum_name'],
				'U_BREADCRUMB'		=> append_sid("{$this->phpbb_root_path}viewforum.{$this->phpEx}", 'f=' . $row['forum_id']),
			];
		}

		$plst_navlinks[] = [
			'BREADCRUMB_NAME'	=> $data['topic_title'],
			'U_BREADCRUMB'		=> append_sid(($this->phpbb_root_path . 'viewtopic.' . $this->phpEx), "t={$data['topic_id']}"),
		];

		$this->template->assign_vars(['navlinks' => $plst_navlinks, ]);
	}
}
