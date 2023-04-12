<?php
/**
 * @package     WT Revars insert
 * @copyright   Copyright (C) 2023-2023 Sergey Tolkachyov. All rights reserved.
 * @author      Sergey Tolkachyov - https://web-tolk.ru
 * @link 		https://web-tolk.ru
 * @version 	1.1.0
 * @license     GNU General Public License version 2 or later
 */

use Joomla\CMS\Application\CMSApplication;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Object\CMSObject;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\Registry\Registry;
use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\CMS\Session\Session;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

/**
 * Editor Article button
 *
 * @since  1.5
 */
class PlgButtonWtrevarsinsert extends CMSPlugin
{
	/**
	 * Application object
	 *
	 * @var    CMSApplication
	 * @since  1.0.0
	 */
	protected $app;


	/**
	 * Affects constructor behavior. If true, language files will be loaded automatically.
	 *
	 * @var    boolean
	 * @since  1.0.0
	 */
	protected $autoloadLanguage = true;

	/**
	 * Display the button
	 *
	 * @param   string  $name  The name of the button to add
	 *
	 * @return  CMSObject|void  The button options as CMSObject, void if ACL check fails.
	 *
	 * @since   1.5
	 */
	public function onDisplay($name)
	{
		$user = Factory::getApplication()->getIdentity();

		// Can create in any category (component permission) or at least in one category
		$canCreateRecords = $user->authorise('core.create', 'com_content')
			|| count($user->getAuthorisedCategories('com_content', 'core.create')) > 0;

		// Instead of checking edit on all records, we can use **same** check as the form editing view
		$values           = (array) Factory::getApplication()->getUserState('com_content.edit.article.id');
		$isEditingRecords = count($values);

		// This ACL check is probably a double-check (form view already performed checks)
		$hasAccess = $canCreateRecords || $isEditingRecords;
		if (!$hasAccess)
		{
			return;
		}

		$link = 'index.php?option=com_ajax&plugin=wtrevarsinsert&group=editors-xtd&format=html&tmpl=component&action=showform&'
			. Session::getFormToken() . '=1&amp;editor=' . $name;

		$button          = new CMSObject();
		$button->modal   = true;
		$button->link    = $link;
		$button->text    = Text::_('PLG_EDITORS-XTD_WTREVARSINSERT_BUTTON_NAME');
		$button->name    = $this->_type . '_' . $this->_name;
		$button->icon    = 'fas fa-code';
		$button->iconSVG = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 512"><!-- Font Awesome Pro 5.15.4 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license (Commercial License) --><path d="M278.9 511.5l-61-17.7c-6.4-1.8-10-8.5-8.2-14.9L346.2 8.7c1.8-6.4 8.5-10 14.9-8.2l61 17.7c6.4 1.8 10 8.5 8.2 14.9L293.8 503.3c-1.9 6.4-8.5 10.1-14.9 8.2zm-114-112.2l43.5-46.4c4.6-4.9 4.3-12.7-.8-17.2L117 256l90.6-79.7c5.1-4.5 5.5-12.3.8-17.2l-43.5-46.4c-4.5-4.8-12.1-5.1-17-.5L3.8 247.2c-5.1 4.7-5.1 12.8 0 17.5l144.1 135.1c4.9 4.6 12.5 4.4 17-.5zm327.2.6l144.1-135.1c5.1-4.7 5.1-12.8 0-17.5L492.1 112.1c-4.8-4.5-12.4-4.3-17 .5L431.6 159c-4.6 4.9-4.3 12.7.8 17.2L523 256l-90.6 79.7c-5.1 4.5-5.5 12.3-.8 17.2l43.5 46.4c4.5 4.9 12.1 5.1 17 .6z"/></svg>';
		$button->options = [
			'height'     => '300px',
			'width'      => '800px',
			'bodyHeight' => '70',
			'modalWidth' => '80',
		];

		return $button;
	}

	public function onAjaxWtrevarsinsert()
	{
		$app = Factory::getApplication();

		if ($app->isClient('site'))
		{
			Session::checkToken('get') or die(Text::_('JINVALID_TOKEN'));
		}


		$this->showRevarsVarsForm();

	}

	/**
	 * Показывает форму для модального окна, вызываемого кнопкой редактора.
	 *
	 * @throws Exception
	 * @since 1.0.0
	 */
	public function showRevarsVarsForm()
	{
		$app    = Factory::getApplication();
		$editor = $app->input->getCmd('editor', '');
		if (!empty($editor))
		{
			// This view is used also in com_menus. Load the xtd script only if the editor is set!
			$app->getDocument()->addScriptOptions('xtd-wtrevarsinsert', ['editor' => $editor]);
		}

		/** @var Joomla\CMS\WebAsset\WebAssetManager $wa */
		$wa = $app->getDocument()->getWebAssetManager();
		$wa->useStyle('bootstrap.css')->useScript('core');
		$wa->registerAndUseScript('admin-wtrevarsinsert-modal', 'plg_editors-xtd_wtrevarsinsert/admin-wtrevarsinsert-modal.js');
		$revars = PluginHelper::getPlugin('system', 'revars');

		if (!$revars || !PluginHelper::isEnabled('system', 'revars'))
		{
			echo '<div class="alert alert-danger">
					<p>' . Text::_('PLG_EDITORS-XTD_WTREVARSINSERT_NO_REVARS_PLUGIN_INSTALLED_OR_ENABLED') . '</p>
				</div>';

			return;
		}

		$revars_params = new Registry($revars->params);

		$revars_variables = (array) $revars_params->get('variables');

		// получаем переменные от сторонних плагинов
		PluginHelper::importPlugin('revars');
		$results = $this->app->triggerEvent('onRevarsAddVariables');
		$additional_plugins_variables = [];
		if (is_array($results))
		{
			foreach ($results as $result)
			{
				if (is_array($result))
				{
					foreach ($result as $r){
						$r->thirddparty = true;
					}
					$additional_plugins_variables = array_merge($result, $additional_plugins_variables);
				}
			}
		}


		if(!empty($additional_plugins_variables))
		{
			foreach ($additional_plugins_variables as $variable)
			{
				$revars_variables[] = (object) $variable;
			}
		}

		$revars_variables = (object)$revars_variables;

		if (count((array) $revars_variables) == 0)
		{
			echo '<div class="alert alert-danger">
					<h3>' . Text::_('PLG_EDITORS-XTD_WTREVARSINSERT_NO_REVARS_VARIABLES_FOUND_HEADER') . '</h3>
					<p>' . Text::_('PLG_EDITORS-XTD_WTREVARSINSERT_NO_REVARS_VARIABLES_FOUND_TEXT') . '</p>
				</div>';

			return;
		}

		$value_text_label = Text::_('PLG_EDITORS-XTD_WTREVARSINSERT_TABLE_HEADER_VALUE');
		$insert_button_text        = Text::_('PLG_EDITORS-XTD_WTREVARSINSERT_TABLE_INSERT_BUTTON');
		$html                  = '<table class="table table-sm table-hover"><thead><tr>
		<th>' . Text::_('PLG_EDITORS-XTD_WTREVARSINSERT_TABLE_HEADER_VARIABLE') . '</th>
		<th class="w-25">'.$insert_button_text.'</th>
		</tr></thead><tbody>';
		$joomla_script_options = [];
		$i                     = 0;
		foreach ($revars_variables as $variable)
		{
			$joomla_script_options[$i] = $variable->variable;
			$insert_button_text        = Text::_('PLG_EDITORS-XTD_WTREVARSINSERT_TABLE_INSERT_BUTTON');
			$third_party_variable = (isset($variable->thirddparty) && !empty($variable->thirddparty)) ? '<span class="badge bg-light text-dark"><i class="fas fa-shapes"></i> 3d-party</span>' : '';

			$html                      .= <<<HTML
						<tr>
								<td class="p-2"><a href="#" class="WtRevarsInsertBtn h4" data-wtrevars-variable="$i">$variable->variable</a><br/><small class="text-muted">$third_party_variable $variable->comment</small>
								<details>
								<summary>$value_text_label</summary>
								<div class="p-2">$variable->value</div>
								</details>
								</td>
								<td class="w-25"><button type="button" class="WtRevarsInsertBtn btn btn-sm btn-primary my-auto" data-wtrevars-variable="$i">$insert_button_text</button></td>
						</tr>

						HTML;

			$i++;
		}

		$app->getDocument()->addScriptOptions('wt_revars_insert', $joomla_script_options);
		$html .= '</tbody>


		</table>';
		$html .= '<div class="fixed-bottom py-2 bg-white border-top d-flex justify-content-end">           
					<a href="https://hika.su/" target="_blank" class="btn btn-sm" >
						<img src="https://hika.su/images/favicon.png" height="18">
						Hika SU
					</a>
					<a href="https://web-tolk.ru" target="_blank" class="btn btn-sm d-inline-flex align-items-center">
						<svg width="85" height="18" xmlns="http://www.w3.org/2000/svg">
							 <g>
							  <title>Go to https://web-tolk.ru</title>
							  <text font-weight="bold" xml:space="preserve" text-anchor="start" font-family="Helvetica, Arial, sans-serif" font-size="18" id="svg_3" y="18" x="8.152073" stroke-opacity="null" stroke-width="0" stroke="#000" fill="#0fa2e6">Web</text>
							  <text font-weight="bold" xml:space="preserve" text-anchor="start" font-family="Helvetica, Arial, sans-serif" font-size="18" id="svg_4" y="18" x="45" stroke-opacity="null" stroke-width="0" stroke="#000" fill="#384148">Tolk</text>
							 </g>
						</svg>
					</a>
 				</div>
            ';
		echo $html;
	}

}
