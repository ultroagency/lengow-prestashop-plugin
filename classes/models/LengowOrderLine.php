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
 * Lengow Order Line Class
 */
class LengowOrderLine
{
    /**
     * @var string Lengow order line table name
     */
    const TABLE_ORDER_LINE = 'lengow_order_line';

    /* Order line fields */
    const FIELD_ID = 'id';
    const FIELD_ORDER_ID = 'id_order';
    const FIELD_ORDER_LINE_ID = 'id_order_line';
    const FIELD_ORDER_DETAIL_ID = 'id_order_detail';

    /**
     * Get Order Lines by PrestaShop order id
     *
     * @param integer $idOrder PrestaShop order id
     *
     * @return array
     */
    public static function findOrderLineIds($idOrder)
    {
        $sql = 'SELECT id_order_line FROM `' . _DB_PREFIX_ . 'lengow_order_line`
            WHERE id_order = ' . (int) $idOrder;
        try {
            return Db::getInstance()->executeS($sql);
        } catch (PrestaShopDatabaseException $e) {
            return array();
        }
    }
}
