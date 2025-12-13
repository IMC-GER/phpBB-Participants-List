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
	 *
	 */
	public function processing(string $process)
	{
		$this->language->add_lang('ptsl_common', 'imcger/participantslist');
		$title = $this->language->lang('PTSL_TITLE');

		switch ($process)
		{
			case 'add':
			case 'edit':
				$data = [];
				$edit_page = '@imcger_participantslist/ptsl_edit_list_body.html';
				add_form_key('imcger\participantslist');

				// Is the form being submitted to us?
				if ($this->request->is_set_post('submit'))
				{
					if (!check_form_key('imcger\participantslist'))
					{
						trigger_error($this->language->lang('FORM_INVALID') . '<br><br><a href="' . append_sid($this->phpbb_root_path . 'viewtopic.' . $this->phpEx, "t={$this->topic_id}#ptsl_anchor") . '">&laquo; ' . $this->language->lang('BACK_TO_PREV') . '</a>', E_USER_WARNING);
					}

					$data['user_id']		= $this->request->variable('user_id', 0, true);
					$data['topic_id']		= $this->request->variable('topic_id', 0);
					$data['ptsl_number']	= $this->request->variable('ptsl_number', '');
					$data['ptsl_comment']	= censor_text($this->request->variable('ptsl_comment', '', true));

					$this->check_permission($this->get_list_data($process, $data['user_id'], $data['topic_id']));

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

					$this->check_permission($data);

					$this->template->assign_vars([
						'PTSL_ID'			=> $data['ptsl_id'] ?? 0,
						'PTSL_TOPIC_ID'		=> $data['topic_id'] ?? 0,
						'PTSL_TOPIC_TITLE'	=> $data['topic_title'] ?? '',
						'PTSL_USER_ID'		=> $data['user_id'] ?? 0,
						'PTSL_USERNAME'		=> $data['username'] ?? '',
						'PTSL_NUMBER'		=> $data['ptsl_number'] ?? 1,
						'PTSL_COMMENT'		=> $data['ptsl_comment'] ?? '',
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

				if ($this->check_permission($data))
				{
					$ptsl_id = $this->ptsl_id ?: $data['ptsl_id'];
					$sql	 = 'DELETE FROM ' . $this->table_prefix . ext::PTSL_DATA_TABLE . ' WHERE ptsl_id = ' . (int) $ptsl_id;

					$this->db->sql_query($sql);
				}

				redirect(append_sid($this->phpbb_root_path . 'viewtopic.' . $this->phpEx, "t={$data['topic_id']}#ptsl_anchor"));

			break;

			// Displays the start page of phpBB
			default:
				redirect($this->phpbb_root_path . 'index.' . $this->phpEx);
			break;
		}

		redirect(append_sid($this->phpbb_root_path . 'viewtopic.' . $this->phpEx, "t={$this->topic_id}#ptsl_anchor"));
	}

	/**
	 * Update and insert participant data in DB
	 */
	private function set_list_data(string $mode, array $data)
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
	private function get_list_data(string $mode, int $user_id = 0, int $topic_id = 0, $ptsl_id = 0)
	{
		$sql_array = [];

		$sql_array['edit'] = [
			'SELECT'    => 'pd.*, pd.user_id, u.username, t.topic_id, t.topic_title, t.forum_id',
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
	 * Check moderator amd user permission
	 */
	private function check_permission(array $data)
	{
		if ((!$this->auth->acl_get('m_delete', $data['forum_id']) &&  $this->ptsl_id) && ($data['user_id'] != $this->user->data['user_id']))
		{
			trigger_error($this->language->lang('PTSL_HAS_NO_PERMISSION') . '<br><br><a href="' . $this->phpbb_root_path . 'index.' . $this->phpEx . '">&laquo; ' . $this->language->lang('BACK_TO_PREV') . '</a>', E_USER_WARNING);
		}

		return true;
	}

	/**
	 * Set breadcrumb menue for participant form
	 */
	private function set_breadcrumb(array $data)
	{
		$plst_navlinks	= [];
		$topic_forum_id = $data['forum_id'];

		$plst_navlinks[] = [
			'BREADCRUMB_NAME'	=> $data['topic_title'],
			'U_BREADCRUMB'		=> append_sid(($this->phpbb_root_path . 'viewtopic.' . $this->phpEx), "t={$data['topic_id']}"),
		];

		do
		{
			$sql = 'SELECT forum_name, parent_id
					FROM ' . FORUMS_TABLE . '
					WHERE forum_id = ' . (int) $topic_forum_id;

			$result = $this->db->sql_query($sql);
			$row = $this->db->sql_fetchrow($result);
			$this->db->sql_freeresult($result);

			$u_view_forum = append_sid("{$this->phpbb_root_path}viewforum.{$this->phpEx}", 'f=' . $topic_forum_id);

			$plst_navlinks[]	= [
				'BREADCRUMB_NAME'	=> $row['forum_name'],
				'U_BREADCRUMB'		=> $u_view_forum,
			];

			$topic_forum_id = $row['parent_id'];

		} while ($row['parent_id'] != 0 && $row['parent_id'] != $data['forum_id']);

		krsort($plst_navlinks);
		$this->template->assign_block_vars_array('navlinks', $plst_navlinks);
	}
}
