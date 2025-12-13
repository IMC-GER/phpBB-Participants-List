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
	protected string $post_delete_conditions;

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
	}

	/**
	 * Assign functions defined in this class to event listeners in the core
	 */
	public static function getSubscribedEvents(): array
	{
		return [
			'core.posting_modify_post_data'				 => 'posting_modify_post_data',
			'core.posting_modify_submission_errors'		 => 'posting_modify_submission_errors',
			'core.posting_modify_submit_post_after'		 => 'posting_modify_submit_post_after',
			'core.posting_modify_template_vars'			 => 'posting_modify_template_vars',
			'core.viewtopic_assign_template_vars_before' => 'set_template_vars',
			'core.user_setup_after'						 => 'user_setup_after',
			'core.delete_post_after'					 => 'delete_post_after',
		];
	}

	/**
	 * Display checkbox in editor only on first post
	 */
	public function posting_modify_post_data($event): void
	{
		if (!isset($event['post_data']['topic_ptsl_disp']))
		{
			$post_data = $event['post_data'];
			$post_data += ['topic_ptsl_disp' => 0];
			$event['post_data'] = $post_data;
		}

		// Only show checkbox when editing the first post in topic
		if ($event['mode'] == 'post' || ($event['mode'] == 'edit' && $event['post_id'] == $event['post_data']['topic_first_post_id']))
		{
			$this->template->assign_vars([
				'S_PTSL_TOPIC_HAS_LIST'	=> true,
				'TOPIC_PTSL_DISP'		=> $event['post_data']['topic_ptsl_disp'],
			]);
		}
	}

	/**
	 * Get chechbox state and update post data
	 */
	public function posting_modify_submission_errors($event): void
	{
		$topic_ptsl_disp = $this->request->variable('topic_ptsl_disp', '') ? 1 : 0;

		$post_data = $event['post_data'];
		$post_data['topic_ptsl_disp'] = $topic_ptsl_disp;
		$event['post_data'] = $post_data;
	}


	/**
	 * Update topic table after submit
	 */
	public function posting_modify_submit_post_after($event): void
	{
		if (in_array($event['mode'], ['post', 'edit', ]))
		{
			$topic_id		 = $event['topic_id'];
			$topic_ptsl_disp = $event['post_data']['topic_ptsl_disp'] ?? 0;

			if ($topic_id < 1)
			{
				// Get ID from new topic
				$parts = parse_url($event['redirect_url']);
				parse_str($parts['query'], $query);
				$topic_id = (int) $query['t'];
			}

			// Only update first post in topic
			if ($event['mode'] == 'post' || ($event['mode'] == 'edit' && $event['post_id'] == $event['post_data']['topic_first_post_id']))
			{
				$sql = 'UPDATE ' . TOPICS_TABLE . '
						SET topic_ptsl_disp = ' . $topic_ptsl_disp . '
						WHERE topic_id = ' . (int) $topic_id;

				$this->db->sql_query($sql);
			}
		}
	}

	/**
	 * Set template vars in editor
	 */
	public function posting_modify_template_vars($event): void
	{
		$page_data = $event['page_data'];
		$page_data = array_merge($page_data, [
				'TOPIC_PTSL_DISP' => $event['post_data']['topic_ptsl_disp'],
			]);

		$event['page_data'] = $page_data;
	}

	/**
	 * Set template vars in viewtopic
	 */
	public function set_template_vars($event): void
	{
		$user_id		= $this->user->data['user_id'];
		$user_inlist	= false;
		$topic_id		= $event['topic_id'];
		$ptsl_enable	= $this->auth->acl_get('f_imcger_ptsl_enable', $event['forum_id'])	&& $this->user->data['is_registered'] && $event['topic_data']['topic_ptsl_disp'];
		$ptsl_m_edit	= $this->auth->acl_get('m_edit', $event['forum_id']);
		$ptsl_m_delete	= $this->auth->acl_get('m_delete', $event['forum_id']);
		$url_list_add	= $this->helper->route('imcger_participantslist_list_controller', ['process' => 'add']);
		$url_list_edit	= $this->helper->route('imcger_participantslist_list_controller', ['process' => 'edit']);
		$url_list_del	= $this->helper->route('imcger_participantslist_list_controller', ['process' => 'delete']);

		if ($ptsl_enable)
		{
			$ptsl_number_sum = 0;

			$sql_array = [
				'SELECT'    => 'pd.*, u.username',
				'FROM'      => [$this->table_prefix . ext::PTSL_DATA_TABLE => 'pd'],
				'LEFT_JOIN' => [
					[
						'FROM' => [USERS_TABLE => 'u'],
						'ON'   => 'pd.user_id = u.user_id',
					],
				],
				'WHERE'     => 'pd.topic_id = ' . (int) $topic_id,
			];

			$sql    = $this->db->sql_build_query('SELECT', $sql_array);
			$result = $this->db->sql_query($sql);

			while ($row = $this->db->sql_fetchrow())
			{
				$this->template->assign_block_vars('ptsl_table', [
					'PTSL_ID'			=> $row['ptsl_id'],
					'PTSL_USER_ID'		=> $row['user_id'],
					'PTSL_USERNAME'		=> $row['username'],
					'PTSL_NUMBER'		=> $row['ptsl_number'],
					'PTSL_COMMENT'		=> $row['ptsl_comment'],
					'U_PTSL_MOD_EDIT'	=> append_sid($url_list_edit, "t={$topic_id}&amp;id={$row['ptsl_id']}"),
					'U_PTSL_MOD_DEL'	=> append_sid($url_list_del, "t={$topic_id}&amp;id={$row['ptsl_id']}"),
				]);

				$ptsl_number_sum += $row['ptsl_number'];

				if ($row['user_id'] == $this->user->data['user_id'])
				{
					$user_inlist = true;
				}
			}
			$this->db->sql_freeresult($result);

			$this->template->assign_vars([
				'PTSL_TOPIC_ID'			=> $topic_id,
				'PTSL_NUMBER_SUM'		=> $this->language->lang('PTSL_PTS_SUM_STRING', $ptsl_number_sum),
				'PTSL_USERNAME'			=> $this->user->data['username'],
				'S_PTSL_TOPIC_HAS_LIST'	=> $ptsl_enable,
				'S_PTSL_M_EDIT'			=> $ptsl_m_edit,
				'S_PTSL_M_DELETE'		=> $ptsl_m_delete,
				'S_PTSL_USER_IN_LIST'	=> $user_inlist,
				'U_PTSL_GO_TO_LIST'		=> append_sid($this->phpbb_root_path . 'viewtopic.' . $this->phpEx, "t={$topic_id}#ptsl_anchor"),
				'U_PTSL_ADD_TO_LIST'	=> append_sid($url_list_add, "t={$topic_id}&amp;u={$user_id}"),
				'U_PTSL_EDIT_LIST'		=> append_sid($url_list_edit, "t={$topic_id}&amp;u={$user_id}"),
				'U_PTSL_DEL_FROM_LIST'	=> append_sid($url_list_del, "t={$topic_id}&amp;u={$user_id}"),
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
	 * Delete the participant list when the associated post is deleted.
	 */
	public function delete_post_after($event)
	{
		// if ($this->post_delete_conditions && ($event['post_id'] == $event['data']['topic_first_post_id']))
		if ($event['post_id'] == $event['data']['topic_first_post_id'])
		{
			$sql = 'DELETE FROM ' . $this->table_prefix . ext::PTSL_DATA_TABLE . '
					WHERE topic_id = ' . (int) $event['topic_id'];
			$this->db->sql_query($sql);

			$sql = 'UPDATE ' . TOPICS_TABLE . '
					SET topic_ptsl_disp = 0
					WHERE topic_id = ' . (int) $event['topic_id'];
			$this->db->sql_query($sql);
		}
	}
}
