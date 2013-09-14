<?php

class DevHelper_Listener
{
	const XENFORO_CONTROLLERADMIN_ADDON_SAVE = 'DevHelper_XenForo_ControllerAdmin_AddOn';
	
	public static function load_class($class, array &$extend)
	{
		static $classes = array(
			'XenForo_ControllerAdmin_AddOn',
			'XenForo_ControllerAdmin_CodeEventListener',

			'XenForo_DataWriter_AddOn',

			'XenForo_Model_AddOn',
		);

		if (in_array($class, $classes))
		{
			$extend[] = 'DevHelper_' . $class;
		}
	}

	public static function template_post_render($templateName, &$content, array &$containerData, XenForo_Template_Abstract $template)
	{
		switch ($templateName)
		{
			case 'PAGE_CONTAINER':
				DevHelper_Generator_File::minifyJs($template);
				break;
		}
	}

	public static function template_hook($hookName, &$contents, array $hookParams, XenForo_Template_Abstract $template)
	{
		switch ($hookName)
		{
			case 'devhelper_devhelper_helper_addon_unit':
				self::_filterDisabledAddOnOptions($contents);
				break;
		}
	}

	protected static function _injectHtml(&$target, $html, $offsetInTarget = 0, $mark = '<!-- search and replace -->', $revertMark = '<!-- revert all the thing! -->')
	{
		if ($offsetInTarget === false)
			return;
		// do nothing if invalid offset is given
		if (empty($html))
			return;
		// the html is empty

		$injected = false;
		$isRevert = (strpos($html, $revertMark) !== false);

		$startPos = strpos($html, $mark);
		if ($startPos !== false)
		{
			$endPos = strpos($html, $mark, $startPos + 1);
			if ($endPos !== false)
			{
				// found the two marks
				$markLen = strlen($mark);
				$marked = trim(substr($html, $startPos + $markLen, $endPos - $startPos - $markLen));

				if (!$isRevert)
				{
					// normal mode, look for the first occurence
					$markedPos = strpos($target, $marked, $offsetInTarget);
				}
				else
				{
					// revert mode, look for the last occurence
					$markedPos = strrpos($target, $marked, $offsetInTarget);
				}

				if ($markedPos !== false)
				{
					// the marked text has been found
					// start injecting our html in place
					$html = str_replace($mark, '', $html);
					$html = str_replace($revertMark, '', $html);

					$target = substr_replace($target, $html, $markedPos, strlen($marked));
				}

				$injected = true;
				// assume that it was injected
			}
		}

		if (!$injected)
		{
			$html = str_replace($mark, '', $html);
			$html = str_replace($revertMark, '', $html);

			if (!$isRevert)
			{
				//  normal mode, append the html
				$target .= $html;
			}
			else
			{
				// revert mode, insert instead of append
				$target = $html . $target;
			}
		}
	}

	protected static function _filterDisabledAddOnOptions(&$html)
	{
		$offset = 0;

		$addOns = XenForo_Application::get('addOns');

		while (true)
		{
			if (preg_match('/<option value="([^"]+)">[^<]+<\/option>/i', $html, $matches, PREG_OFFSET_CAPTURE, $offset))
			{
				$offset = $matches[0][1] + 1;
				$length = strlen($matches[0][0]);
				$addOnId = $matches[1][0];

				if (!isset($addOns[$addOnId]))
				{
					$html = substr_replace($html, '', $offset - 1, $length);
					$offset--;
				}
			}
			else
			{
				break;
			}
		}

		$groupOffset = 0;
		while (true)
		{
			if (preg_match('/<optgroup label=".+">\s+<\/optgroup>/i', $html, $matches, PREG_OFFSET_CAPTURE, $groupOffset))
			{
				$groupOffset = $matches[0][1] + 1;
				$length = strlen($matches[0][0]);

				$html = substr_replace($html, '', $groupOffset - 1, $length);
				$groupOffset--;
			}
			else
			{
				break;
			}
		}
	}

}
