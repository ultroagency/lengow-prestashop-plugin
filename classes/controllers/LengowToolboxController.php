<?php
/**
 * Copyright 2022 Lengow SAS.
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
 * @copyright 2022 Lengow SAS
 * @license   http://www.apache.org/licenses/LICENSE-2.0
 */

/**
 * Lengow Toolbox Controller Class
 */
class LengowToolboxController extends LengowController
{
    /**
     * Display data page
     */
    public function display()
    {
        $toolboxElement = new LengowToolboxElement();
        $this->context->smarty->assign('checklist', $toolboxElement->getCheckList());
        $this->context->smarty->assign('globalInformation', $toolboxElement->getGlobalInformation());
        $this->context->smarty->assign('synchronizationInformation', $toolboxElement->getImportInformation());
        $this->context->smarty->assign('exportInformation', $toolboxElement->getExportInformation());
        $this->context->smarty->assign('fileInformation', $toolboxElement->getFileInformation());
        $this->context->smarty->assign('checksum', $toolboxElement->checkFileMd5());
        parent::display();
    }
}
