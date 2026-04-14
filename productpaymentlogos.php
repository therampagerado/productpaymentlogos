<?php
/**
 * Copyright (C) 2017-2024 thirty bees
 * Copyright (C) 2007-2016 PrestaShop SA
 *
 * thirty bees is an extension to the PrestaShop software by PrestaShop SA.
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Academic Free License (AFL 3.0)
 * that is bundled with this package in the file LICENSE.md.
 * It is also available through the world-wide-web at this URL:
 * https://opensource.org/licenses/afl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@thirtybees.com so we can send you a copy immediately.
 *
 * @author    thirty bees <modules@thirtybees.com>
 * @author    PrestaShop SA <contact@prestashop.com>
 * @copyright 2017-2024 thirty bees
 * @copyright 2007-2016 PrestaShop SA
 * @license   Academic Free License (AFL 3.0)
 * PrestaShop is an internationally registered trademark of PrestaShop SA.
 */


if (!defined('_TB_VERSION_')) {
    exit;
}

class ProductPaymentLogos extends Module
{
    public const CONFIG_IMAGE = 'PRODUCTPAYMENTLOGOS_IMG';
    public const CONFIG_IMAGE_WIDTH = 'PRODUCTPAYMENTLOGOS_IMG_WIDTH';
    public const CONFIG_IMAGE_HEIGHT = 'PRODUCTPAYMENTLOGOS_IMG_HEIGHT';
    public const CONFIG_LINK = 'PRODUCTPAYMENTLOGOS_LINK';
    public const CONFIG_TITLE = 'PRODUCTPAYMENTLOGOS_TITLE';
    public const CONFIG_ALT = 'PRODUCTPAYMENTLOGOS_ALT';
    public const DEFAULT_IMAGE = 'payment-logo.png';
    public const DEFAULT_IMAGE_WIDTH = 233;
    public const DEFAULT_IMAGE_HEIGHT = 60;

    /**
     * @throws PrestaShopException
     */
    public function __construct()
    {
        $this->name = 'productpaymentlogos';
        $this->tab = 'front_office_features';
        $this->version = '2.1.1';
        $this->author = 'thirty bees';
        $this->need_instance = 0;
        $this->bootstrap = true;

        parent::__construct();

        $this->displayName = $this->l('Block Product Payment Logos');
        $this->description = $this->l('Displays the logos of the available payment systems on the product page.');
        $this->tb_versions_compliancy = '> 1.0.0';
        $this->tb_min_version = '1.0.0';
        $this->ps_versions_compliancy = ['min' => '1.6', 'max' => '1.6.99.99'];
    }

    /**
     * @return bool
     *
     * @throws PrestaShopException
     */
    public function install()
    {
        Configuration::updateValue(static::CONFIG_IMAGE, static::DEFAULT_IMAGE);
        Configuration::updateValue(static::CONFIG_IMAGE_WIDTH, static::DEFAULT_IMAGE_WIDTH);
        Configuration::updateValue(static::CONFIG_IMAGE_HEIGHT, static::DEFAULT_IMAGE_HEIGHT);
        Configuration::updateValue(static::CONFIG_LINK, $this->getDefaultTranslatedValues(''));
        Configuration::updateValue(static::CONFIG_TITLE, $this->getDefaultTranslatedValues(''));
        Configuration::updateValue(static::CONFIG_ALT, $this->getDefaultTranslatedValues(''));

        $this->_clearCache('productpaymentlogos.tpl');

        return parent::install() && $this->registerHook('displayProductButtons') && $this->registerHook('header');
    }

    /**
     * @return bool
     *
     * @throws PrestaShopException
     */
    public function uninstall()
    {
        Configuration::deleteByName(static::CONFIG_IMAGE);
        Configuration::deleteByName(static::CONFIG_IMAGE_WIDTH);
        Configuration::deleteByName(static::CONFIG_IMAGE_HEIGHT);
        Configuration::deleteByName(static::CONFIG_LINK);
        Configuration::deleteByName(static::CONFIG_TITLE);
        Configuration::deleteByName(static::CONFIG_ALT);

        return parent::uninstall();
    }

    /**
     * @return string|null
     *
     * @throws PrestaShopException
     * @throws SmartyException
     */
    public function hookDisplayProductButtons($params)
    {
        if (Configuration::get('PS_CATALOG_MODE')) {
            return null;
        }

        if (!$this->isCached('productpaymentlogos.tpl', $this->getCacheId())) {
            $bannerData = $this->getBannerData();
            $this->smarty->assign([
                'banner_img' => 'img/' . $bannerData['image'],
                'banner_link' => $bannerData['link'],
                'banner_title' => $bannerData['title'],
                'banner_alt' => $bannerData['alt'],
                'banner_width' => $bannerData['width'],
                'banner_height' => $bannerData['height'],
            ]);
        }

        return $this->display(__FILE__, 'productpaymentlogos.tpl', $this->getCacheId());
    }

    /**
     * @throws PrestaShopException
     */
    public function hookHeader($params)
    {
        if (Configuration::get('PS_CATALOG_MODE')) {
            return;
        }

        $this->context->controller->addCSS($this->_path . 'views/css/productpaymentlogos.css', 'all');
    }

    /**
     * @throws SmartyException
     * @throws PrestaShopException
     */
    public function getContent()
    {
        return $this->postProcess() . $this->renderForm();
    }

    /**
     * @throws PrestaShopException
     */
    public function postProcess()
    {
        if (Tools::isSubmit('submitStoreConf')) {
            $uploadedFile = $_FILES['PRODUCTPAYMENTLOGOS_IMG'] ?? null;
            $newFileName = null;
            $newDimensions = null;
            $oldFileName = $this->getConfiguredImageName();

            if ($uploadedFile && !empty($uploadedFile['tmp_name'])) {
                $fileInfo = pathinfo($uploadedFile['name']);
                $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'avif'];
                $extension = isset($fileInfo['extension']) ? Tools::strtolower((string) $fileInfo['extension']) : '';

                if (!$extension || !in_array($extension, $allowedExtensions, true)) {
                    return $this->displayError($this->l('Invalid image format. Supported formats: JPG, JPEG, PNG, GIF, WebP, AVIF.'));
                }

                $newFileName = sha1($uploadedFile['name'] . '-' . uniqid('', true)) . '.' . $extension;
                $filePath = dirname(__FILE__) . '/img/' . $newFileName;

                if (!move_uploaded_file($uploadedFile['tmp_name'], $filePath)) {
                    return $this->displayError($this->l('An error occurred while attempting to upload the file.'));
                }

                $newDimensions = $this->detectImageDimensionsFromFile($filePath);
            }

            Configuration::updateValue(static::CONFIG_LINK, $this->getTranslatedValuesFromRequest(static::CONFIG_LINK));
            Configuration::updateValue(static::CONFIG_TITLE, $this->getTranslatedValuesFromRequest(static::CONFIG_TITLE));
            Configuration::updateValue(static::CONFIG_ALT, $this->getTranslatedValuesFromRequest(static::CONFIG_ALT));

            if ($newFileName !== null) {
                Configuration::updateValue(static::CONFIG_IMAGE, $newFileName);
                Configuration::updateValue(static::CONFIG_IMAGE_WIDTH, (int) $newDimensions['width']);
                Configuration::updateValue(static::CONFIG_IMAGE_HEIGHT, (int) $newDimensions['height']);
                $this->deleteImageFileIfUnused($oldFileName, $newFileName);
            }

            $this->_clearCache('productpaymentlogos.tpl');
            Tools::redirectAdmin('index.php?tab=AdminModules&conf=6&configure=' . $this->name . '&token=' . Tools::getAdminTokenLite('AdminModules'));
        }

        return '';
    }

    /**
     * @throws PrestaShopException
     * @throws SmartyException
     */
    public function renderForm()
    {
        $fieldsForm = [
            'form' => [
                'legend' => [
                    'title' => $this->l('Settings'),
                    'icon' => 'icon-cogs'
                ],
                'input' => [
                    [
                        'type' => 'text',
                        'label' => $this->l('Block heading'),
                        'name' => static::CONFIG_TITLE,
                        'lang' => true,
                        'maxlength' => 120,
                        'desc' => $this->l('You can choose to add a heading above the logos.')
                    ],
                    [
                        'type' => 'file',
                        'label' => $this->l('Block image'),
                        'name' => static::CONFIG_IMAGE,
                        'thumb' => '../modules/' . $this->name . '/img/' . $this->getConfiguredImageName(),
                    ],
                    [
                        'type' => 'text',
                        'label' => $this->l('Image width'),
                        'name' => static::CONFIG_IMAGE_WIDTH,
                        'readonly' => true,
                        'class' => 'fixed-width-sm',
                        'suffix' => 'px',
                        'desc' => $this->l('Detected image width in pixels.')
                    ],
                    [
                        'type' => 'text',
                        'label' => $this->l('Image height'),
                        'name' => static::CONFIG_IMAGE_HEIGHT,
                        'readonly' => true,
                        'class' => 'fixed-width-sm',
                        'suffix' => 'px',
                        'desc' => $this->l('Detected image height in pixels.')
                    ],
                    [
                        'type' => 'text',
                        'label' => $this->l('Image alt text'),
                        'name' => static::CONFIG_ALT,
                        'lang' => true,
                        'maxlength' => 120,
                        'desc' => $this->l('Describe the uploaded logos for screen readers. If left empty, the block heading is used.')
                    ],
                    [
                        'type' => 'text',
                        'label' => $this->l('Image link'),
                        'name' => static::CONFIG_LINK,
                        'lang' => true,
                        'maxlength' => 255,
                        'desc' => $this->l('Set a different destination URL for each language if needed.')
                    ]
                ],
                'submit' => [
                    'title' => $this->l('Save'),
                ]
            ],
        ];

        /** @var AdminController $controller */
        $controller = $this->context->controller;
        $helper = new HelperForm();
        $helper->show_toolbar = false;
        $helper->table = $this->table;
        $lang = new Language((int)Configuration::get('PS_LANG_DEFAULT'));
        $helper->default_form_language = $lang->id;
        $helper->allow_employee_form_lang = Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG') ? Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG') : 0;
        $helper->identifier = $this->identifier;
        $helper->submit_action = 'submitStoreConf';
        $helper->currentIndex = $this->context->link->getAdminLink('AdminModules', false) . '&configure=' . $this->name . '&tab_module=' . $this->tab . '&module_name=' . $this->name;
        $helper->token = Tools::getAdminTokenLite('AdminModules');
        $helper->tpl_vars = [
            'fields_value' => $this->getConfigFieldsValues(),
            'languages' => $controller->getLanguages(),
            'id_language' => $this->context->language->id
        ];

        return $helper->generateForm([$fieldsForm]);
    }

    /**
     * @throws PrestaShopException
     */
    public function getConfigFieldsValues()
    {
        return [
            static::CONFIG_IMAGE => Tools::getValue(static::CONFIG_IMAGE, $this->getConfiguredImageName()),
            static::CONFIG_IMAGE_WIDTH => Tools::getValue(static::CONFIG_IMAGE_WIDTH, $this->getConfiguredImageWidth()),
            static::CONFIG_IMAGE_HEIGHT => Tools::getValue(static::CONFIG_IMAGE_HEIGHT, $this->getConfiguredImageHeight()),
            static::CONFIG_LINK => $this->getTranslatedFieldValues(static::CONFIG_LINK),
            static::CONFIG_TITLE => $this->getTranslatedFieldValues(static::CONFIG_TITLE),
            static::CONFIG_ALT => $this->getTranslatedFieldValues(static::CONFIG_ALT),
        ];
    }

    /**
     * @return array<string, int|string|null>
     * @throws PrestaShopException
     */
    protected function getBannerData()
    {
        $title = $this->getTranslatedConfigValue(static::CONFIG_TITLE);
        $image = $this->getConfiguredImageName();
        $alt = $this->getTranslatedConfigValue(static::CONFIG_ALT);
        if ($alt === '') {
            $alt = $title;
        }
        if ($alt === '') {
            $alt = $this->l('Available payment methods');
        }
        return [
            'image' => $image,
            'link' => $this->getTranslatedConfigValue(static::CONFIG_LINK),
            'title' => $title,
            'alt' => $alt,
            'width' => $this->getConfiguredImageWidth(),
            'height' => $this->getConfiguredImageHeight(),
        ];
    }

    /**
     * @return string
     * @throws PrestaShopException
     */
    protected function getConfiguredImageName()
    {
        $fileName = basename((string) Configuration::get(static::CONFIG_IMAGE));
        if ($fileName === '' || !$this->imageFileExists($fileName)) {
            return static::DEFAULT_IMAGE;
        }

        return $fileName;
    }

    /**
     * @return int|null
     * @throws PrestaShopException
     */
    protected function getConfiguredImageWidth()
    {
        $width = (int) Configuration::get(static::CONFIG_IMAGE_WIDTH);
        if ($width > 0) {
            return $width;
        }

        return $this->getConfiguredImageName() === static::DEFAULT_IMAGE ? static::DEFAULT_IMAGE_WIDTH : null;
    }

    /**
     * @return int|null
     * @throws PrestaShopException
     */
    protected function getConfiguredImageHeight()
    {
        $height = (int) Configuration::get(static::CONFIG_IMAGE_HEIGHT);
        if ($height > 0) {
            return $height;
        }

        return $this->getConfiguredImageName() === static::DEFAULT_IMAGE ? static::DEFAULT_IMAGE_HEIGHT : null;
    }

    /**
     * @param string $value
     *
     * @return array<int, string>
     * @throws PrestaShopException
     */
    protected function getDefaultTranslatedValues($value)
    {
        $values = [];
        foreach (Language::getLanguages(false) as $language) {
            $values[(int) $language['id_lang']] = (string) $value;
        }

        return $values;
    }

    /**
     * @param string $key
     *
     * @return array<int, string>
     * @throws PrestaShopException
     */
    protected function getTranslatedFieldValues($key)
    {
        $values = [];
        foreach (Language::getLanguages(false) as $language) {
            $idLang = (int) $language['id_lang'];
            $values[$idLang] = (string) Tools::getValue(
                $key . '_' . $idLang,
                $this->getTranslatedConfigValue($key, $idLang)
            );
        }

        return $values;
    }

    /**
     * @param string $key
     *
     * @return array<int, string>
     * @throws PrestaShopException
     */
    protected function getTranslatedValuesFromRequest($key)
    {
        $values = [];
        foreach (Language::getLanguages(false) as $language) {
            $idLang = (int) $language['id_lang'];
            $values[$idLang] = trim((string) Tools::getValue($key . '_' . $idLang, ''));
        }

        return $values;
    }

    /**
     * @param string $key
     * @param int|null $idLang
     *
     * @return string
     * @throws PrestaShopException
     */
    protected function getTranslatedConfigValue($key, $idLang = null)
    {
        $idLang = (int) ($idLang ?: $this->context->language->id);
        $value = Configuration::get($key, $idLang);
        if ($value === false && $idLang !== (int) Configuration::get('PS_LANG_DEFAULT')) {
            $value = Configuration::get($key, (int) Configuration::get('PS_LANG_DEFAULT'));
        }
        if ($value === false) {
            $value = Configuration::get($key);
        }

        return $value === false ? '' : (string) $value;
    }

    /**
     * @param string $fileName
     *
     * @return bool
     */
    protected function imageFileExists($fileName)
    {
        return file_exists(dirname(__FILE__) . '/img/' . $fileName);
    }

    /**
     * @param string $filePath
     *
     * @return array<string, int|null>
     */
    protected function detectImageDimensionsFromFile($filePath)
    {
        if (!is_file($filePath) || !is_readable($filePath) || !function_exists('getimagesize')) {
            return [
                'width' => null,
                'height' => null,
            ];
        }

        $dimensions = @getimagesize($filePath);
        if (!is_array($dimensions) || empty($dimensions[0]) || empty($dimensions[1])) {
            return [
                'width' => null,
                'height' => null,
            ];
        }

        return [
            'width' => (int) $dimensions[0],
            'height' => (int) $dimensions[1],
        ];
    }

    /**
     * @param string $oldFileName
     * @param string $newFileName
     *
     * @throws PrestaShopException
     */
    protected function deleteImageFileIfUnused($oldFileName, $newFileName)
    {
        $oldFileName = basename((string) $oldFileName);
        if (
            $oldFileName === ''
            || $oldFileName === static::DEFAULT_IMAGE
            || $oldFileName === basename((string) $newFileName)
        ) {
            return;
        }

        $inUse = (int) Db::readOnly()->getValue(
            (new DbQuery())
                ->select('COUNT(*)')
                ->from('configuration')
                ->where('`name` = \'' . pSQL(static::CONFIG_IMAGE) . '\'')
                ->where('`value` = \'' . pSQL($oldFileName) . '\'')
        );

        $oldFilePath = dirname(__FILE__) . '/img/' . $oldFileName;
        if ($inUse === 0 && file_exists($oldFilePath)) {
            unlink($oldFilePath);
        }
    }
}
