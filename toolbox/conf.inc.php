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
//ini_set("display_errors", 1);
$currentDirectory = str_replace('modules/lengow/toolbox/', '', dirname($_SERVER['SCRIPT_FILENAME']) . "/");

$sep = DIRECTORY_SEPARATOR;
require_once $currentDirectory . 'config' . $sep . 'config.inc.php';
require_once $currentDirectory . 'init.php';
require_once $currentDirectory . 'modules/lengow/lengow.php';

if (_PS_VERSION_ > '1.5') {
    Shop::setContext(Shop::CONTEXT_ALL);
}

$lengowTool = new LengowTool();

if (!in_array($lengowTool->getCurrentUri(), array('/modules/lengow/toolbox/login.php'))) {
    if (!$lengowTool->isLogged()) {
        Tools::redirect(_PS_BASE_URL_.__PS_BASE_URI__.'modules/lengow/toolbox/login.php', '');
    }
}

if ($lengowTool->getCurrentUri() == '/modules/lengow/toolbox/login.php' && $lengowTool->isLogged()) {
    Tools::redirect(_PS_BASE_URL_.__PS_BASE_URI__.'modules/lengow/toolbox/', '');
}

$employeeCollection = Employee::getEmployees(true);
$lastEmployeeId = end($employeeCollection);
Context::getContext()->employee = new Employee($lastEmployeeId);
LengowTranslation::$forceIsoCode = 'en';
Context::getContext()->smarty->assign('toolbox', true);
