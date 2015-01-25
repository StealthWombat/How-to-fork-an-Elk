<?php

/**
 * Integration system for drafts into Profile controller
 *
 * @name      ElkArte Forum
 * @copyright ElkArte Forum contributors
 * @license   BSD http://opensource.org/licenses/BSD-3-Clause
 *
 * This software is a derived product, based on:
 *
 * Simple Machines Forum (SMF)
 * copyright:	2011 Simple Machines (http://www.simplemachines.org)
 * license:  	BSD, See included LICENSE.TXT for terms and conditions.
 *
 * @version 1.0
 *
 */

if (!defined('ELK'))
	die('No access...');

class Drafts_Profile_Module
{
	public static function hooks()
	{
		global $modSettings;

		if (!empty($modSettings['drafts_enabled']) && !empty($modSettings['drafts_post_enabled']))
		{
			add_integration_function('integrate_profile_areas', 'Drafts_Profile_Module::profile_areas', '', false);
			return array(
				array('pre_load', array('Drafts_Display_Module', 'pre_load'), array('post_errors')),
			);
		}
		else
			return array();
	}

	public static function profile_areas(&$profile_areas)
	{
		global $txt, $context;

		$profile_areas['info']['areas'] = elk_array_insert($profile_areas['info']['areas'], 'showposts', array(
			'showdrafts' => array(
				'label' => $txt['drafts_show'],
				'controller' => 'Draft_Controller',
				'function' => 'action_showProfileDrafts',
				'enabled' => $context['user']['is_owner'],
				'permission' => array(
					'own' => 'profile_view_own',
					'any' => array(),
				),
			)), 'after');
	}

	public function pre_load($post_errors)
	{
		if (empty($post_errors))
			loadLanguage('Drafts');
	}
}