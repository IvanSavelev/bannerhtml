<?php
/**
* 2007-2015 PrestaShop
*
* NOTICE OF LICENSE
*
* This source file is subject to the Academic Free License (AFL 3.0)
* that is bundled with this package in the file LICENSE.txt.
* It is also available through the world-wide-web at this URL:
* http://opensource.org/licenses/afl-3.0.php
* If you did not receive a copy of the license and are unable to
* obtain it through the world-wide-web, please send an email
* to license@prestashop.com so we can send you a copy immediately.
*
* DISCLAIMER
*
* Do not edit or add to this file if you wish to upgrade PrestaShop to newer
* versions in the future. If you wish to customize PrestaShop for your
* needs please refer to http://www.prestashop.com for more information.
*
*  @author PrestaShop SA <contact@prestashop.com>
*  @copyright  2007-2015 PrestaShop SA
*  @license    http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
*  International Registered Trademark & Property of PrestaShop SA
**/
if (!defined('_PS_VERSION_'))
    exit;
class Bannerhtml extends Module
{
    protected $config_form = false;
    public function __construct()
    {
        $this->name = 'bannerhtml';
        $this->tab = 'front_office_features';
        $this->version = '1.0.0';
        $this->author = 'Ivan Savelev';
        $this->need_instance = 0;
        $this->bootstrap = true;
        parent::__construct();
        $this->displayName = $this->l('Banner html');
        $this->description = $this->l('Displays html banner at the top of the store.');
        $this->ps_versions_compliancy = array('min' => '1.6', 'max' => _PS_VERSION_);
    }

    public function install()
    {
        return
            parent::install() &&
            $this->registerHook('displayBanner') &&
            $this->registerHook('displayHeader') &&
            $this->registerHook('displayTop') &&
            $this->registerHook('actionObjectLanguageAddAfter') &&
            $this->installFixtures() &&
            Configuration::updateValue('BLOCKBANNER_HTML_VIEW',1,false) &&
            $this->disableDevice(Context::DEVICE_MOBILE);
    }

    protected function installFixtures()
    {// Implemented multi-lingual, for each language a separate row in the database, the same internal mechanisms in line dobovlyaet ps_configuration_lang
        $languages = Language::getLanguages(false);
        foreach ($languages as $lang)
        {
            $this->installFixture((int)$lang['id_lang']);
        }
        return true;
    }

    protected function installFixture($id_lang)
    {
        $values=array();
        $values['BLOCKBANNER_HTML_TEXT'][(int)$id_lang];
        $values['BLOCKBANNER_HTML_LINK'][(int)$id_lang];
        $values['BLOCKBANNER_HTML_DESC'][(int)$id_lang];
        Configuration::updateValue('BLOCKBANNER_HTML_TEXT', $values['BLOCKBANNER_HTML_TEXT']);
        Configuration::updateValue('BLOCKBANNER_HTML_LINK', $values['BLOCKBANNER_HTML_LINK']);
        Configuration::updateValue('BLOCKBANNER_HTML_DESC', $values['BLOCKBANNER_HTML_DESC']);
    }

    public function hookActionObjectLanguageAddAfter($params)
    {
        return $this->installFixture((int)$params['object']->id);
    }

    public function uninstall()
    {
        Configuration::deleteByName('BLOCKBANNER_HTML_TEXT');
        Configuration::deleteByName('BLOCKBANNER_HTML_LINK');
        Configuration::deleteByName('BLOCKBANNER_HTML_DESC');
        Configuration::deleteByName('BLOCKBANNER_HTML_VIEW');
        return parent::uninstall();
    }

    public function hookDisplayTop($params)
    {// If there was no previous page, ie Nordston site just opened pollzovatelem
        if(!isset($_SERVER['HTTP_REFERER']))
        {// What do the default value (on)
            Configuration::updateValue('BLOCKBANNER_HTML_VIEW',1,false);
        }
        if(!Configuration::get('BLOCKBANNER_HTML_VIEW'))
        {// If the bank is closed, we do not derive anything
            return '';
        }
        if (!$this->isCached('bannerhtml.tpl', $this->getCacheId())) {
            $this->smarty->assign(array(
                'banner_text' => Configuration::get('BLOCKBANNER_HTML_TEXT',$this->context->language->id),
                'banner_link' => Configuration::get('BLOCKBANNER_HTML_LINK',$this->context->language->id),
                'banner_desc' => Configuration::get('BLOCKBANNER_HTML_DESC',$this->context->language->id),
                'blockbanner_html_controller_url' => $this->context->link->getModuleLink('blockbanner_html')
            ));
        }
       // return $this->display($this->local_path.'views/templates/front/bannerhtml.tpl', $this->getCacheId());
        return $this->display(__FILE__, 'views/templates/front/bannerhtml.tpl', $this->getCacheId());
    }

    public function hookDisplayBanner($params)
    {
        return $this->hookDisplayTop($params);
    }

    public function hookDisplayFooter($params)
    {
        return $this->hookDisplayTop($params);
    }

    public function hookDisplayHeader($params)
    { //$this->local_path.'views/templates/admin/configure.tpl'
        $this->context->controller->addJs($this->_path.'views/js/bannerhtml.js');
        $this->context->controller->addCSS($this->_path.'views/css/bannerhtml.css'); //Почему то работает только тут
    }



    public function getContent() //Вызывается при нажатии Сохранить в админке
    {
        $html = '';
        // If we try to update the settings  //Если сохраняем из админки изменения то сохраняем изменения в БД и отчищаем кеш
        if (Tools::isSubmit('submitModule')) {

            $languages = Language::getLanguages(false);
            foreach ($languages as $lang)
            {
                $values=array();
                $values['BLOCKBANNER_HTML_TEXT'][$lang['id_lang']] = Tools::getValue('BLOCKBANNER_HTML_TEXT_'.$lang['id_lang']);
                $values['BLOCKBANNER_HTML_LINK'][$lang['id_lang']] = Tools::getValue('BLOCKBANNER_HTML_LINK_'.$lang['id_lang']);
                $values['BLOCKBANNER_HTML_DESC'][$lang['id_lang']] = Tools::getValue('BLOCKBANNER_HTML_DESC_'.$lang['id_lang']);

                Configuration::updateValue('BLOCKBANNER_HTML_TEXT', $values['BLOCKBANNER_HTML_TEXT'],true);
                Configuration::updateValue('BLOCKBANNER_HTML_LINK', $values['BLOCKBANNER_HTML_LINK']);
                Configuration::updateValue('BLOCKBANNER_HTML_DESC', $values['BLOCKBANNER_HTML_DESC']);

            }
            $this->_clearCache('bannerhtml.tpl');
            $html .= $this->displayConfirmation($this->l('Configuration updated'));
        }
        $html .= $this->renderForm();
        return $html;
    }

    public function renderForm()
    {
        //Администроаторская часть
        $fields_form = array(
            'form' => array(
                'legend' => array(
                    'title' => $this->l('Settings'),
                    'icon' => 'icon-cogs'
                ),
                'description' => $this->l('This block shows from above a banner.'),
                'input' => array(
                    array(
                        'type' => 'textarea',
                        'label' => $this->l('Text div'),
                        'lang' => true,
                        'name' => 'BLOCKBANNER_HTML_TEXT',
                        'desc' => $this->l('Enter the text here, and it will be displayed on a banara of your website, also here it is possible to enter references, images, etc.'),
                        'autoload_rte' => true,
                        'hint' => $this->l('Banner html')
                    ),
                    array(
                        'type' => 'text',
                        'lang' => true,
                        'label' => $this->l('Banner Link'),
                        'name' => 'BLOCKBANNER_HTML_LINK',
                        'desc' => $this->l('Enter the link associated to your banner. When clicking on the banner, the link opens in the same window. If no link is entered, it redirects to the homepage.')
                    ),
                    array(
                        'type' => 'text',
                        'lang' => true,
                        'label' => $this->l('Banner description'),
                        'name' => 'BLOCKBANNER_HTML_DESC',
                        'desc' => $this->l('Please enter a short but meaningful description for the banner.')
                    )
                ),

                'submit' => array(
                    'title' => $this->l('Save'),
                )
            ),
        );
        $helper = new HelperForm();
        $helper->show_toolbar = false;
        $helper->table = $this->table;
        $lang = new Language((int)Configuration::get('PS_LANG_DEFAULT'));
        $helper->default_form_language = $lang->id;
        $helper->allow_employee_form_lang = Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG') ? Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG') : 0;
        $this->fields_form = array();

        $helper->identifier = $this->identifier;
        $helper->submit_action = 'submitModule';
        $helper->currentIndex = $this->context->link->getAdminLink('AdminModules', false) . '&configure=' . $this->name . '&tab_module=' . $this->tab . '&module_name=' . $this->name;
        $helper->token = Tools::getAdminTokenLite('AdminModules');
        $helper->tpl_vars = array(
            'fields_value' => $this->getConfigFieldsValues(),
            'languages' => $this->context->controller->getLanguages(),
            'id_language' => $this->context->language->id
        );
        return $helper->generateForm(array($fields_form));
    }

    public function getConfigFieldsValues()
    { //Берет данные для админки
        $languages = Language::getLanguages(false);
        $fields = array();
        foreach ($languages as $lang)
        {
            $fields['BLOCKBANNER_HTML_TEXT'][$lang['id_lang']] = Tools::getValue('BLOCKBANNER_HTML_TEXT_'.$lang['id_lang'], Configuration::get('BLOCKBANNER_HTML_TEXT', $lang['id_lang']));
            $fields['BLOCKBANNER_HTML_LINK'][$lang['id_lang']] = Tools::getValue('BLOCKBANNER_HTML_LINK_'.$lang['id_lang'], Configuration::get('BLOCKBANNER_HTML_LINK', $lang['id_lang']));
            $fields['BLOCKBANNER_HTML_DESC'][$lang['id_lang']] = Tools::getValue('BLOCKBANNER_HTML_DESC_'.$lang['id_lang'], Configuration::get('BLOCKBANNER_HTML_DESC', $lang['id_lang']));
        }
        return $fields;
    }
}
?>
