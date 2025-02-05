<?php
/**
 * Copyright 2021 Lengow SAS.
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
 * @copyright 2021 Lengow SAS
 * @license   http://www.apache.org/licenses/LICENSE-2.0
 */

/**
 * Lengow Dashboard Controller Class
 */
class LengowDashboardController extends LengowController
{
    /**
     * Process Post Parameters
     */
    public function postProcess()
    {
        $action = isset($_REQUEST['action']) ? $_REQUEST['action'] : false;
        if ($action) {
            switch ($action) {
                case 'refresh_status':
                    LengowSync::getStatusAccount(true);
                    Tools::redirectAdmin($this->lengowLink->getAbsoluteAdminLink('AdminLengowDashboard'));
                    break;
                case 'remind_me_later':
                    $timestamp = time() + (7 * 86400);
                    LengowConfiguration::updateGlobalValue(LengowConfiguration::LAST_UPDATE_PLUGIN_MODAL, $timestamp);
                    echo json_encode(array('success' => true));
                    break;
            }
            exit();
        }
    }

    /**
     * Display data page
     */
    public function display()
    {
        $refreshStatus = $this->lengowLink->getAbsoluteAdminLink('AdminLengowDashboard') . '&action=refresh_status';
        $this->context->smarty->assign('refresh_status', $refreshStatus);
        parent::display();
    }
}
