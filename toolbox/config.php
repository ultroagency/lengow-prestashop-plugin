<?php
/**
 * Copyright 2016 Lengow SAS.
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
 * @copyright 2016 Lengow SAS
 * @license   http://www.apache.org/licenses/LICENSE-2.0
 */

require 'conf.inc.php';

$action = isset($_REQUEST['action']) ?  $_REQUEST['action'] : null;
$accessToken = isset($_REQUEST['access_token']) ?  $_REQUEST['access_token'] : null;
$secretToken = isset($_REQUEST['secret_token']) ?  $_REQUEST['secret_token'] : null;

$form = new LengowConfigurationForm(array(
    "fields" => LengowConfiguration::getKeys()
));

$locale = new LengowTranslation();

if (_PS_VERSION_ < '1.5') {
    $shopCollection = array(array('id_shop' => 1));
} else {
    $sql = 'SELECT id_shop FROM '._DB_PREFIX_.'shop WHERE active = 1';
    $shopCollection = Db::getInstance()->ExecuteS($sql);
}

switch ($action) {
    case "update":
        $form->postProcess(array(
            'LENGOW_SHOP_ACTIVE',
            'LENGOW_EXPORT_FILE_ENABLED',
            'LENGOW_IMPORT_FORCE_PRODUCT',
            'LENGOW_IMPORT_PROCESSING_FEE',
            'LENGOW_IMPORT_PREPROD_ENABLED',
            'LENGOW_IMPORT_SHIP_MP_ENABLED',
            'LENGOW_IMPORT_STOCK_SHIP_MP',
            'LENGOW_REPORT_MAIL_ENABLED',
            'LENGOW_IMPORT_SINGLE_ENABLED',
            'LENGOW_TRACKING_ENABLED'
        ));
        Tools::redirect(_PS_BASE_URL_.__PS_BASE_URI__.'modules/lengow/toolbox/config.php', '');
        break;
}

require 'views/header.php';
?>
<div class="container">
<h1><?php echo $locale->t('toolbox.menu.configuration'); ?></h1>
<form class="form-horizontal" method="POST">
    <input type="hidden" name="action" value="update"/>
    <?php
    foreach ($shopCollection as $row) {
        $shop = new LengowShop($row['id_shop']);
        echo '<fieldset><legend>'.$locale->t('toolbox.configuration.shop').' : '.$shop->name
            .' > '.$locale->t('toolbox.configuration.import').'</legend>';
        echo $form->buildShopInputs($shop->id, array(
            'LENGOW_ACCOUNT_ID',
            'LENGOW_ACCESS_TOKEN',
            'LENGOW_SECRET_TOKEN',
            'LENGOW_SHOP_ACTIVE',
        ));
        echo '</fieldset>';
        echo '<fieldset><legend>'.$locale->t('toolbox.configuration.shop').' : '.$shop->name
            .' > '.$locale->t('toolbox.configuration.export').'</legend>';
        echo $form->buildShopInputs($shop->id, array(
            'LENGOW_SHOP_TOKEN',
            'LENGOW_EXPORT_SELECTION_ENABLED',
            'LENGOW_EXPORT_VARIATION_ENABLED',
            'LENGOW_LAST_EXPORT',
        ));
        echo '</fieldset>';
    }
    echo '<fieldset><legend>'.$locale->t('toolbox.configuration.import').'</legend>';
    echo $form->buildInputs(array(
        'LENGOW_ORDER_ID_PROCESS',
        'LENGOW_ORDER_ID_SHIPPED',
        'LENGOW_ORDER_ID_SHIPPEDBYMP',
        'LENGOW_ORDER_ID_CANCEL',
        'LENGOW_IMPORT_CARRIER_DEFAULT',
        'LENGOW_IMPORT_FORCE_PRODUCT',
        'LENGOW_IMPORT_PROCESSING_FEE',
        'LENGOW_IMPORT_DAYS',
        'LENGOW_IMPORT_PREPROD_ENABLED',
        'LENGOW_IMPORT_FAKE_EMAIL',
        'LENGOW_IMPORT_SHIP_MP_ENABLED',
        'LENGOW_IMPORT_STOCK_SHIP_MP',
        'LENGOW_REPORT_MAIL_ENABLED',
        'LENGOW_REPORT_MAIL_ADDRESS',
        'LENGOW_IMPORT_SINGLE_ENABLED',
        'LENGOW_IMPORT_IN_PROGRESS',
        'LENGOW_LAST_IMPORT_CRON',
        'LENGOW_LAST_IMPORT_MANUAL',
        'LENGOW_GLOBAL_TOKEN',
        'LENGOW_AUTHORIZED_IP',
        'LENGOW_TRACKING_ENABLED',
        'LENGOW_TRACKING_ID',
    ));
    echo '</fieldset>';
    echo '<fieldset><legend>'.$locale->t('toolbox.configuration.export').'</legend>';
    echo $form->buildInputs(array(
        'LENGOW_EXPORT_FORMAT',
        'LENGOW_EXPORT_FILE_ENABLED',
        'LENGOW_CARRIER_DEFAULT',
    ));
    echo '</fieldset>';
    ?>
<div class="form-group">
    <div class="col-sm-offset-2 col-sm-10">
        <button type="submit" class="btn btn-success">
            <?php echo $locale->t('toolbox.configuration.button_save'); ?>
        </button>
    </div>
</div>
</form>
</div><!-- /.container -->
<?php
require 'views/footer.php';
