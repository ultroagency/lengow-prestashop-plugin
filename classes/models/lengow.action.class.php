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

/**
 * Lengow Action class
 *
 */
class LengowAction
{

    const STATE_NEW = 0;
    const STATE_FINISH = 1;

    public function __construct()
    {

    }

    public function load($row)
    {
        $this->id = (int)$row['id'];
        $this->id_order = (int)$row['id_order'];
        $this->action_id = (int)$row['action_id'];
        $this->action_type = $row['action_type'];
        $this->retry = $row['retry'];
        $this->parameters = $row['parameters'];
        $this->state = $row['state'];
        $this->created_at = $row['created_at'];
        $this->updated_at = $row['updated_at'];
    }

    public function findByActionId($action_id)
    {
        $row = Db::getInstance()->getRow(
            'SELECT * FROM '._DB_PREFIX_.'lengow_actions la WHERE action_id = '.(int)$action_id
        );
        if ($row) {
            $this->load($row);
            return true;
        }
        return false;
    }

    public function findAll()
    {

    }

    public function find($id)
    {
        $row = Db::getInstance()->getRow('SELECT * FROM '._DB_PREFIX_.'lengow_actions la WHERE id = '.(int)$id);
        if ($row) {
            $this->load($row);
            return true;
        }
        return false;
    }

    public static function createAction($params)
    {
        $insertParams = array(
            'parameters' => pSQL(Tools::JsonEncode($params['parameters'])),
            'id_order' => (int)$params['id_order'],
            'action_id' => (int)$params['action_id'],
            'action_type' => $params['action_type'],
            'state' => self::STATE_NEW,
            'created_at' => date('Y-m-d h:m:i'),
            'updated_at' => date('Y-m-d h:m:i'),
        );
        if (isset($params['parameters']['line'])) {
            $insertParams['order_line_sku'] = $params['parameters']['line'];
        }

        Db::getInstance()->autoExecute(_DB_PREFIX_ . 'lengow_actions', $insertParams, 'INSERT');
        LengowMain::log('API', 'call tracking ', false, $params['id_order']);
    }

    public static function updateAction($params)
    {
        $action = new LengowAction();
        if ($action->findByActionId($params['action_id'])) {
            if ($action->state == self::STATE_NEW) {
                Db::getInstance()->autoExecute(
                    _DB_PREFIX_ . 'lengow_actions',
                    array(
                        'retry' => $action->retry + 1,
                        'updated_at' => date('Y-m-d h:m:i'),
                    ),
                    'UPDATE',
                    'id = ' . $action->id
                );
            }
        }
    }
}
