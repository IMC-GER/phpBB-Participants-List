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

class ptsl_acp_listener implements EventSubscriberInterface
{
	/**
	 * Assign functions defined in this class to event listeners in the core
	 */
	public static function getSubscribedEvents(): array
	{
		return [
			'core.permissions' => 'add_permissions',
		];
	}

	/**
	 * Add permissions
	 */
	public function add_permissions(object $event): void
	{
		$event->update_subarray('permissions', 'f_imcger_ptsl_enable', ['lang' => 'ACL_F_IMCGER_PTSL_ENABLE', 'cat' => 'content']);
	}
}
