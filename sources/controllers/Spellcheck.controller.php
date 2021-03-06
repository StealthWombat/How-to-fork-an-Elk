<?php

/**
 * Just show the spellchecker.
 *
 * @name      ElkArte Forum
 * @copyright ElkArte Forum contributors
 * @license   BSD http://opensource.org/licenses/BSD-3-Clause
 *
 * This software is a derived product, based on:
 *
 * Simple Machines Forum (SMF)
 * copyright:	2011 Simple Machines (http://www.simplemachines.org)
 * license:		BSD, See included LICENSE.TXT for terms and conditions.
 *
 * @version 1.1 dev
 *
 */

if (!defined('ELK'))
	die('No access...');

/**
 * Spellcheck Controller
 */
class Spellcheck_Controller extends Action_Controller
{
	/**
	 * Spell checks the post for typos ;).
	 * It uses the pspell library, which MUST be installed.
	 * It has problems with internationalization.
	 * It is accessed via ?action=spellcheck.
	 */
	public function action_index()
	{
		global $txt, $context;

		// A list of "words" we know about but pspell doesn't.
		$known_words = array('elkarte', 'php', 'mysql', 'www', 'gif', 'jpeg', 'png', 'http');

		$this->_events->trigger('prepare_spellcheck', array('known_words' => &$known_words));

		loadLanguage('Post');
		loadTemplate('Post');

		// Okay, this looks funny, but it actually fixes a weird bug.
		ob_start();
		$old = error_reporting(0);

		// See, first, some windows machines don't load pspell properly on the first try.  Dumb, but this is a workaround.
		pspell_new('en');

		// Next, the dictionary in question may not exist. So, we try it... but...
		$pspell_link = pspell_new($txt['lang_dictionary'], $txt['lang_spelling'], '', 'utf-8', PSPELL_FAST | PSPELL_RUN_TOGETHER);

		// Most people don't have anything but English installed... So we use English as a last resort.
		if (!$pspell_link)
			$pspell_link = pspell_new('en', '', '', '', PSPELL_FAST | PSPELL_RUN_TOGETHER);

		error_reporting($old);
		@ob_end_clean();

		if (!isset($_POST['spellstring']) || !$pspell_link)
			die;

		// Get all the words (Javascript already separated them).
		$alphas = explode("\n", strtr($_POST['spellstring'], array("\r" => '')));

		// Construct a bit of Javascript code.
		$context['spell_js'] = '
			var txt = {"done": "' . $txt['spellcheck_done'] . '"},
				mispstr = ' . ($_POST['fulleditor'] === 'true' ? 'window.opener.spellCheckGetText(spell_fieldname)' : 'window.opener.document.forms[spell_formname][spell_fieldname].value') . ',
				misps = ' . $this->_build_misps_array($alphas) . '
			);';

		// And instruct the template system to just show the spellcheck sub template.
		$this->_template_layers->removeAll();
		$context['sub_template'] = 'spellcheck';
	}

	protected function _build_misps_array($alphas)
	{
		$array = 'Array(';

		$found_words = false;
		foreach ($alphas as $alpha)
		{
			// Words are sent like 'word|offset_begin|offset_end'.
			$check_word = explode('|', $alpha);

			// If the word is a known word, or spelled right...
			if (in_array(Util::strtolower($check_word[0]), $known_words) || pspell_check($pspell_link, $check_word[0]) || !isset($check_word[2]))
				continue;

			// Find the word, and move up the "last occurrence" to here.
			$found_words = true;

			// Add on the javascript for this misspelling.
			$array .= '
				new misp("' . strtr($check_word[0], array('\\' => '\\\\', '"' => '\\"', '<' => '', '&gt;' => '')) . '", ' . (int) $check_word[1] . ', ' . (int) $check_word[2] . ', [';

			// If there are suggestions, add them in...
			$suggestions = pspell_suggest($pspell_link, $check_word[0]);
			if (!empty($suggestions))
			{
				// But first check they aren't going to be censored - no naughty words!
				foreach ($suggestions as $k => $word)
					if ($suggestions[$k] != censorText($word))
						unset($suggestions[$k]);

				if (!empty($suggestions))
					$array .= '"' . implode('", "', $suggestions) . '"';
			}

			$array .= '])';
		}

		// If words were found, take off the last comma.
		if ($found_words)
			$array = substr($array, 0, -1);

		return $array;
	}
}
