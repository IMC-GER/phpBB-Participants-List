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

class ptsl_mcp_listener implements EventSubscriberInterface
{
	protected string $sort_dir;
	protected array  $post_ids;

	public function __construct
	(
		protected \phpbb\user $user,
		protected \phpbb\auth\auth $auth,
		protected \phpbb\language\language $language,
		protected \phpbb\template\template $template,
		protected \phpbb\db\driver\driver_interface $db,
		protected \phpbb\request\request_interface $request,
		protected \phpbb\controller\helper $helper,
		protected string $root_path,
		protected string $php_ext,
		protected string $table_prefix,
	)
	{
		$this->sort_dir = $this->request->variable('ptsl-sd', '');
		$this->post_ids = [];
	}

	/**
	 * Assign functions defined in this class to event listeners in the core
	 */
	public static function getSubscribedEvents(): array
	{
		return [
			'core.user_setup_after'					 => 'user_setup_after',
			'core.mcp_topic_review_modify_row'		 => 'get_post_ids',
			'core.mcp_topic_review_modify_topic_row' => 'set_template_vars',
		];
	}

	/**
	 * Add language file
	 */
	public function user_setup_after(): void
	{
		$this->language->add_lang('ptsl_common', 'imcger/participantslist');
	}

	/**
	 * Get post ids
	 */
	public function get_post_ids(object $event): void
	{
		$this->post_ids[] = $event['post_row']['POST_ID'];
	}

	/**
	 * Set template vars in mcp topic view
	 */
	public function set_template_vars(object $event): void
	{
		$sql = 'SELECT *
				FROM ' . $this->table_prefix . ext::PTSL_TABLE_DATA_TABLE . '
				WHERE topic_id = ' . (int) $event['topic_id'];

		$result			 = $this->db->sql_query_limit($sql, 1);
		$ptsl_table_data = $this->db->sql_fetchrow($result);
		$this->db->sql_freeresult($result);

		// Don't display list when topic first post is soft delete
		if ($ptsl_table_data == false || $ptsl_table_data['post_not_visibility'])
		{
			return;
		}

		$ptsl_go_to_list = $this->auth->acl_get('u_imcger_ptsl_view') && $event['topic_info']['topic_ptsl_disp'];
		$ptsl_u_view	 = $ptsl_go_to_list && in_array($event['topic_info']['topic_first_post_id'], $this->post_ids);

		$this->template->assign_vars([
				'S_PTSL_GO_TO_LIST' => false,
			]);

		if ($ptsl_u_view)
		{
			$user_id		 = $this->user->data['user_id'];
			$topic_id		 = $event['topic_id'];
			$ptsl_number_pts = $ptsl_number_opt1 = $ptsl_number_opt2 = $ptsl_number_opt3 = 0;
			$ptsl_m_edit	 = $this->auth->acl_get('m_edit', $event['topic_info']['forum_id']);
			$ptsl_m_delete	 = $this->auth->acl_get('m_delete', $event['topic_info']['forum_id']);
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

			$append_params = $query_para = [];
			parse_str($this->user->page['query_string'], $append_params);

			// Encoding the query parameters of the URL
			foreach ($append_params as $key => $value)
			{
				$query_para[urlencode($key)] = urlencode($this->request->variable($key, '1'));
			}

			$query_para['t']	= (int) $topic_id;
			$query_para['f']	= (int) $event['topic_info']['forum_id'];
			$query_para['i']	= 'mcp_main';
			$query_para['mode'] = 'topic_view';

			// Set default sort direction
			$sort_query_para  = array_merge($query_para, ['ptsl-sd' => 'a']);
			$alter_query_para = array_merge($query_para, ['u' => $user_id]);

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

			while ($row = $this->db->sql_fetchrow($result))
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
					'U_PTSL_MOD_EDIT'	 => append_sid($url_list_edit, "t={$topic_id}&amp;ptsl_id={$row['ptsl_id']}&amp;ptsl_mode=mcp"),
					'U_PTSL_MOD_DEL'	 => append_sid($url_list_del, "t={$topic_id}&amp;ptsl_id={$row['ptsl_id']}&amp;ptsl_mode=mcp"),
				];

				$ptsl_number_pts  += $row['ptsl_number'];
				$ptsl_number_opt1 += $row['ptsl_opt1'];
				$ptsl_number_opt2 += $row['ptsl_opt2'];
				$ptsl_number_opt3 += $row['ptsl_opt3'];
			}
			$this->db->sql_freeresult($result);

			add_form_key('imcger/participantslist');

			$this->template->assign_vars([
				'ptsl_table'			=> $ptsl_table,
				'PTSL_TOPIC_ID'			=> $topic_id,
				'PTSL_NUMBER_PTS'		=> $ptsl_number_pts,
				'PTSL_NUMBER_OPT1'		=> $ptsl_number_opt1,
				'PTSL_NUMBER_OPT2'		=> $ptsl_number_opt2,
				'PTSL_NUMBER_OPT3'		=> $ptsl_number_opt3,
				'PTSL_USERNAME'			=> $this->user->data['username'],
				'PTSL_COLUMN_OPT1'		=> $ptsl_table_data['ptsl_column_opt1'],
				'PTSL_COLUMN_OPT2'		=> $ptsl_table_data['ptsl_column_opt2'],
				'PTSL_COLUMN_OPT3'		=> $ptsl_table_data['ptsl_column_opt3'],
				'PTSL_COLUMN_OPT1_NAME'	=> censor_text($ptsl_table_data['ptsl_column_opt1_name']),
				'PTSL_COLUMN_OPT2_NAME'	=> censor_text($ptsl_table_data['ptsl_column_opt2_name']),
				'PTSL_COLUMN_OPT3_NAME'	=> censor_text($ptsl_table_data['ptsl_column_opt3_name']),
				'PTSL_SORT_DIRECTION'	=> $alter_query_para['ptsl-sd'] ?? '' ,
				'S_PTSL_GO_TO_LIST'		=> false,
				'S_PTSL_CAN_VIEW_LIST'	=> $ptsl_u_view,
				'S_PTSL_M_EDIT'			=> $ptsl_m_edit,
				'S_PTSL_M_DELETE'		=> $ptsl_m_delete,
				'S_PTSL_REGISTERED_USER' => false,
				'S_PTSL_USER_IN_LIST'	=> false,
				'U_PTSL_SORT_BY_NAME'	=> append_sid($this->root_path . 'mcp.' . $this->php_ext, $sort_query_para) . '#ptsl-anchor',
			]);
		}
	}
}
