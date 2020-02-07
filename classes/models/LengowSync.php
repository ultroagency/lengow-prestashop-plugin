<?php
/**
 * Copyright 2017 Lengow SAS.
 *
 * Licensed under the Apache License, Version 2.0 (the "License"); you may
 * not use this file except in compliance with the License. You may obtain
 * a copy of the License at
 *
 *   http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS, WITHOUT
 * WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied. See the
 * License for the specific language governing permissions and limitations
 * under the License.
 *
 * @author    Team Connector <team-connector@lengow.com>
 * @copyright 2017 Lengow SAS
 * @license   http://www.apache.org/licenses/LICENSE-2.0
 */

/**
 * Lengow Sync Class
 */
class LengowSync
{
    /**
     * @var string cms type
     */
    const CMS_TYPE = 'prestashop';

    /**
     * @var string sync catalog action
     */
    const SYNC_CATALOG = 'catalog';

    /**
     * @var string sync carrier action
     */
    const SYNC_CARRIER = 'carrier';

    /**
     * @var string sync cms option action
     */
    const SYNC_CMS_OPTION = 'cms_option';

    /**
     * @var string sync status account action
     */
    const SYNC_STATUS_ACCOUNT = 'status_account';

    /**
     * @var string sync statistic action
     */
    const SYNC_STATISTIC = 'statistic';

    /**
     * @var string sync marketplace action
     */
    const SYNC_MARKETPLACE = 'marketplace';

    /**
     * @var string sync order action
     */
    const SYNC_ORDER = 'order';

    /**
     * @var string sync action action
     */
    const SYNC_ACTION = 'action';

    /**
     * @var array cache time for catalog, carrier, statistic, account status, options and marketplace synchronisation
     */
    protected static $cacheTimes = array(
        self::SYNC_CATALOG => 21600,
        self::SYNC_CARRIER => 86400,
        self::SYNC_CMS_OPTION => 86400,
        self::SYNC_STATUS_ACCOUNT => 86400,
        self::SYNC_STATISTIC => 86400,
        self::SYNC_MARKETPLACE => 43200,
    );

    /**
     * @var array valid sync actions
     */
    public static $syncActions = array(
        self::SYNC_ORDER,
        self::SYNC_CARRIER,
        self::SYNC_CMS_OPTION,
        self::SYNC_STATUS_ACCOUNT,
        self::SYNC_STATISTIC,
        self::SYNC_MARKETPLACE,
        self::SYNC_ACTION,
        self::SYNC_CATALOG,
    );

    /**
     * Get Sync Data (Inscription / Update)
     *
     * @return array
     */
    public static function getSyncData()
    {
        $data = array(
            'domain_name' => $_SERVER['SERVER_NAME'],
            'token' => LengowMain::getToken(),
            'type' => self::CMS_TYPE,
            'version' => _PS_VERSION_,
            'plugin_version' => LengowConfiguration::getGlobalValue('LENGOW_VERSION'),
            'email' => LengowConfiguration::get('PS_SHOP_EMAIL'),
            'cron_url' => LengowMain::getImportUrl(),
            'return_url' => 'http://' . $_SERVER['SERVER_NAME'] . $_SERVER['REQUEST_URI'],
            'shops' => array(),
        );
        $shopCollection = LengowShop::findAll(true);
        foreach ($shopCollection as $row) {
            $idShop = $row['id_shop'];
            $lengowExport = new LengowExport(array('shop_id' => $idShop));
            $shop = new LengowShop($idShop);
            $data['shops'][$idShop] = array(
                'token' => LengowMain::getToken($idShop),
                'shop_name' => $shop->name,
                'domain_url' => $shop->domain,
                'feed_url' => LengowMain::getExportUrl($shop->id),
                'total_product_number' => $lengowExport->getTotalProduct(),
                'exported_product_number' => $lengowExport->getTotalExportProduct(),
                'enabled' => LengowConfiguration::shopIsActive($idShop),
            );
        }
        return $data;
    }

    /**
     * Set shop configuration key from Lengow
     *
     * @param array $params Lengow API credentials
     */
    public static function sync($params)
    {
        LengowConfiguration::setAccessIds(
            array(
                'LENGOW_ACCOUNT_ID' => $params['account_id'],
                'LENGOW_ACCESS_TOKEN' => $params['access_token'],
                'LENGOW_SECRET_TOKEN' => $params['secret_token'],
            )
        );
        if (isset($params['shops'])) {
            foreach ($params['shops'] as $shopToken => $shopCatalogIds) {
                $shop = LengowShop::findByToken($shopToken);
                if ($shop) {
                    LengowConfiguration::setCatalogIds($shopCatalogIds['catalog_ids'], (int)$shop->id);
                    LengowConfiguration::setActiveShop((int)$shop->id);
                }
            }
        }
        // save last update date for a specific settings (change synchronisation interval time)
        LengowConfiguration::updateGlobalValue('LENGOW_LAST_SETTING_UPDATE', time());
    }

    /**
     * Sync Lengow catalogs for order synchronisation
     *
     * @param boolean $force force cache update
     * @param boolean $logOutput see log or not
     *
     * @return boolean
     */
    public static function syncCatalog($force = false, $logOutput = false)
    {
        $settingUpdated = false;
        if (LengowConfiguration::isNewMerchant()) {
            return false;
        }
        if (!$force) {
            $updatedAt = LengowConfiguration::getGlobalValue('LENGOW_CATALOG_UPDATE');
            if (!is_null($updatedAt) && (time() - (int)$updatedAt) < self::$cacheTimes[self::SYNC_CATALOG]) {
                return false;
            }
        }
        $result = LengowConnector::queryApi(LengowConnector::GET, LengowConnector::API_CMS, array(), '', $logOutput);
        if (isset($result->cms)) {
            $cmsToken = LengowMain::getToken();
            foreach ($result->cms as $cms) {
                if ($cms->token === $cmsToken) {
                    foreach ($cms->shops as $cmsShop) {
                        $shop = LengowShop::findByToken($cmsShop->token);
                        if ($shop) {
                            $catalogIdsChange = LengowConfiguration::setCatalogIds(
                                $cmsShop->catalog_ids,
                                (int)$shop->id
                            );
                            $activeStoreChange = LengowConfiguration::setActiveShop((int)$shop->id);
                            if (!$settingUpdated && ($catalogIdsChange || $activeStoreChange)) {
                                $settingUpdated = true;
                            }
                        }
                    }
                    break;
                }
            }
        }
        // save last update date for a specific settings (change synchronisation interval time)
        if ($settingUpdated) {
            LengowConfiguration::updateGlobalValue('LENGOW_LAST_SETTING_UPDATE', time());
        }
        LengowConfiguration::updateGlobalValue('LENGOW_CATALOG_UPDATE', time());
        return true;
    }

    /**
     * Sync Lengow marketplaces and marketplace carriers
     *
     * @param boolean $force force cache update
     * @param boolean $logOutput see log or not
     *
     * @return boolean
     */
    public static function syncCarrier($force = false, $logOutput = false)
    {
        if (LengowConfiguration::isNewMerchant()) {
            return false;
        }
        if (!$force) {
            $updatedAt = LengowConfiguration::getGlobalValue('LENGOW_LIST_MARKET_UPDATE');
            if (!is_null($updatedAt) && (time() - (int)$updatedAt) < self::$cacheTimes[self::SYNC_CARRIER]) {
                return false;
            }
        }
        LengowMarketplace::loadApiMarketplace($force, $logOutput);
        LengowMarketplace::syncMarketplaces();
        LengowCarrier::syncCarrierMarketplace();
        LengowMethod::syncMethodMarketplace();
        LengowCarrier::createDefaultCarrier();
        LengowCarrier::cleanCarrierMarketplaceMatching();
        LengowMethod::cleanMethodMarketplaceMatching();
        LengowConfiguration::updateGlobalValue('LENGOW_LIST_MARKET_UPDATE', time());
        return true;
    }

    /**
     * Get options for all shops
     *
     * @return array
     */
    public static function getOptionData()
    {
        $data = array(
            'token' => LengowMain::getToken(),
            'version' => _PS_VERSION_,
            'plugin_version' => LengowConfiguration::getGlobalValue('LENGOW_VERSION'),
            'options' => LengowConfiguration::getAllValues(),
            'shops' => array(),
        );
        $shopCollection = LengowShop::findAll(true);
        foreach ($shopCollection as $row) {
            $idShop = $row['id_shop'];
            $lengowExport = new LengowExport(array('shop_id' => $idShop));
            $data['shops'][] = array(
                'token' => LengowMain::getToken($idShop),
                'enabled' => LengowConfiguration::shopIsActive($idShop),
                'total_product_number' => $lengowExport->getTotalProduct(),
                'exported_product_number' => $lengowExport->getTotalExportProduct(),
                'options' => LengowConfiguration::getAllValues($idShop),
            );
        }
        return $data;
    }

    /**
     * Set CMS options
     *
     * @param boolean $force force cache update
     * @param boolean $logOutput see log or not
     *
     * @return boolean
     */
    public static function setCmsOption($force = false, $logOutput = false)
    {
        if (LengowConfiguration::isNewMerchant()
            || LengowConfiguration::getGlobalValue('LENGOW_IMPORT_PREPROD_ENABLED')
        ) {
            return false;
        }
        if (!$force) {
            $updatedAt = LengowConfiguration::getGlobalValue('LENGOW_OPTION_CMS_UPDATE');
            if (!is_null($updatedAt) && (time() - (int)$updatedAt) < self::$cacheTimes[self::SYNC_CMS_OPTION]) {
                return false;
            }
        }
        $options = Tools::jsonEncode(self::getOptionData());
        LengowConnector::queryApi(LengowConnector::PUT, LengowConnector::API_CMS, array(), $options, $logOutput);
        LengowConfiguration::updateGlobalValue('LENGOW_OPTION_CMS_UPDATE', time());
        return true;
    }

    /**
     * Get Status Account
     *
     * @param boolean $force force cache update
     * @param boolean $logOutput see log or not
     *
     * @return array|false
     */
    public static function getStatusAccount($force = false, $logOutput = false)
    {
        if (!$force) {
            $updatedAt = LengowConfiguration::getGlobalValue('LENGOW_ACCOUNT_STATUS_UPDATE');
            if (!is_null($updatedAt) && (time() - (int)$updatedAt) < self::$cacheTimes[self::SYNC_STATUS_ACCOUNT]) {
                return Tools::jsonDecode(LengowConfiguration::getGlobalValue('LENGOW_ACCOUNT_STATUS'), true);
            }
        }
        $result = LengowConnector::queryApi(LengowConnector::GET, LengowConnector::API_PLAN, array(), '', $logOutput);
        if (isset($result->isFreeTrial)) {
            $status = array(
                'type' => $result->isFreeTrial ? 'free_trial' : '',
                'day' => (int)$result->leftDaysBeforeExpired < 0 ? 0 : (int)$result->leftDaysBeforeExpired,
                'expired' => (bool)$result->isExpired,
                'legacy' => $result->accountVersion === 'v2' ? true : false
            );
            LengowConfiguration::updateGlobalValue('LENGOW_ACCOUNT_STATUS', Tools::jsonEncode($status));
            LengowConfiguration::updateGlobalValue('LENGOW_ACCOUNT_STATUS_UPDATE', time());
            return $status;
        } else {
            if (LengowConfiguration::getGlobalValue('LENGOW_ACCOUNT_STATUS_UPDATE')) {
                return Tools::jsonDecode(LengowConfiguration::getGlobalValue('LENGOW_ACCOUNT_STATUS'), true);
            }
        }
        return false;
    }

    /**
     * Get Statistic with all shop
     *
     * @param boolean $force force cache update
     * @param boolean $logOutput see log or not
     *
     * @return array
     */
    public static function getStatistic($force = false, $logOutput = false)
    {
        if (!$force) {
            $updatedAt = LengowConfiguration::getGlobalValue('LENGOW_ORDER_STAT_UPDATE');
            if (!is_null($updatedAt) && (time() - (int)$updatedAt) < self::$cacheTimes[self::SYNC_STATISTIC]) {
                return Tools::jsonDecode(LengowConfiguration::getGlobalValue('LENGOW_ORDER_STAT'), true);
            }
        }
        $currencyId = 0;
        $result = LengowConnector::queryApi(
            LengowConnector::GET,
            LengowConnector::API_STATISTIC,
            array(
                'date_from' => date('c', strtotime(date('Y-m-d') . ' -10 years')),
                'date_to' => date('c'),
                'metrics' => 'year',
            ),
            '',
            $logOutput
        );
        if (isset($result->level0)) {
            $stats = $result->level0[0];
            $return = array(
                'total_order' => $stats->revenue,
                'nb_order' => (int)$stats->transactions,
                'currency' => $result->currency->iso_a3,
                'available' => false,
            );
        } else {
            if (LengowConfiguration::getGlobalValue('LENGOW_ORDER_STAT_UPDATE')) {
                return Tools::jsonDecode(LengowConfiguration::getGlobalValue('LENGOW_ORDER_STAT'), true);
            } else {
                return array(
                    'total_order' => 0,
                    'nb_order' => 0,
                    'currency' => '',
                    'available' => false,
                );
            }
        }
        if ($return['total_order'] > 0 || $return['nb_order'] > 0) {
            $return['available'] = true;
        }
        if ($return['currency']) {
            try {
                $currencyId = LengowCurrency::getIdBySign($return['currency']);
            } catch (Exception $e) {
                $currencyId = 0;
            }
        }
        if ($currencyId > 0) {
            try {
                $return['total_order'] = Tools::displayPrice($return['total_order'], new Currency($currencyId));
            } catch (Exception $e) {
                $return['total_order'] = number_format($return['total_order'], 2, ',', ' ');
            }
        } else {
            $return['total_order'] = number_format($return['total_order'], 2, ',', ' ');
        }
        LengowConfiguration::updateGlobalValue('LENGOW_ORDER_STAT', Tools::jsonEncode($return));
        LengowConfiguration::updateGlobalValue('LENGOW_ORDER_STAT_UPDATE', time());
        return $return;
    }

    /**
     * Get marketplace data
     *
     * @param boolean $force force cache update
     * @param boolean $logOutput see log or not
     *
     * @return array|false
     */
    public static function getMarketplaces($force = false, $logOutput = false)
    {
        $filePath = LengowMarketplace::getFilePath();
        if (!$force) {
            $updatedAt = LengowConfiguration::getGlobalValue('LENGOW_MARKETPLACE_UPDATE');
            if (!is_null($updatedAt)
                && (time() - (int)$updatedAt) < self::$cacheTimes[self::SYNC_MARKETPLACE]
                && file_exists($filePath)
            ) {
                // recovering data with the marketplaces.json file
                $marketplacesData = Tools::file_get_contents($filePath);
                if ($marketplacesData) {
                    return Tools::jsonDecode($marketplacesData);
                }
            }
        }
        // recovering data with the API
        $result = LengowConnector::queryApi(
            LengowConnector::GET,
            LengowConnector::API_MARKETPLACE,
            array(),
            '',
            $logOutput
        );
        if ($result && is_object($result) && !isset($result->error)) {
            // updated marketplaces.json file
            try {
                $marketplaceFile = new LengowFile(
                    LengowMain::$lengowConfigFolder,
                    LengowMarketplace::$marketplaceJson,
                    'w'
                );
                $marketplaceFile->write(Tools::jsonEncode($result));
                $marketplaceFile->close();
                LengowConfiguration::updateGlobalValue('LENGOW_MARKETPLACE_UPDATE', time());
            } catch (LengowException $e) {
                LengowMain::log(
                    LengowLog::CODE_IMPORT,
                    LengowMain::setLogMessage(
                        'log.import.marketplace_update_failed',
                        array(
                            'decoded_message' => LengowMain::decodeLogMessage(
                                $e->getMessage(),
                                LengowTranslation::DEFAULT_ISO_CODE
                            )
                        )
                    ),
                    $logOutput
                );
            }
            return $result;
        } else {
            // if the API does not respond, use marketplaces.json if it exists
            if (file_exists($filePath)) {
                $marketplacesData = Tools::file_get_contents($filePath);
                if ($marketplacesData) {
                    return Tools::jsonDecode($marketplacesData);
                }
            }
        }
        return false;
    }
}
