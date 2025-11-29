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

class ptsl_main_listener implements EventSubscriberInterface
{
	/**
	 * Constructor
	 */
	public function __construct
	(
		protected \phpbb\user $user,
		protected \phpbb\auth\auth $auth,
		protected \phpbb\language\language $language,
		protected \phpbb\template\template $template,
		protected \phpbb\db\driver\driver_interface $db,
	)
	{

	}

	/**
	 * Assign functions defined in this class to event listeners in the core
	 */
	public static function getSubscribedEvents(): array
	{
		return [
			'core.viewtopic_assign_template_vars_before' => 'set_template_vars',
			'core.user_setup_after'						 => 'user_setup_after',
		];
	}

	/**
	 * Set template vars
	 */
	public function set_template_vars(object $event): void
	{
		$ptsl_enable = $this->auth->acl_get('f_imcger_ptsl_enable', $event['forum_id']) && $this->user['is_registered'];

		$this->template->assign_vars([
			'S_HAS_PTSL_LIST'	=> $ptsl_enable,
		]);

		if ($ptsl_enable)
		{

		}
	}

	/**
	 * Add External Links language file
	 */
	public function user_setup_after(): void
	{
		// $this->language->add_lang('ptsl_lang', 'imcger/participantslist');
	}
}
