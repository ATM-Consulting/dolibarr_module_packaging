<?php
/* Copyright (C) 2020 ATM Consulting <support@atm-consulting.fr>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

if(! class_exists('SeedObject')) {
    /**
     * Needed if $form->showLinkedObjectBlock() is call or for session timeout on our module page
     */
    define('INC_FROM_DOLIBARR', true);
    require_once dirname(__FILE__).'/../config.php';
}

class TPackaging extends SeedObject
{
    public $db;

    public function __construct($db) {
        parent::__construct($db);
        $this->init();
    }

    public static function getProductFournConditionnement($line) {
        global $db;
        $sql = "SELECT pfpe.packaging FROM ".MAIN_DB_PREFIX."product_fournisseur_price_extrafields as pfpe WHERE fk_object=( 
                    SELECT pfp.rowid FROM ".MAIN_DB_PREFIX."product_fournisseur_price as pfp 
                    WHERE pfp.fk_product = ".$line->fk_product." AND pfp.ref_fourn = '".$line->ref_supplier."' AND pfp.fk_soc = (
                        SELECT cf.fk_soc FROM  ".MAIN_DB_PREFIX."commande_fournisseur as cf WHERE cf.rowid = ".$line->fk_commande."
                    ) AND pfp.price = ".$line->subprice."
        );";
        $resql = $db->query($sql);

        if(! empty($resql) && $db->num_rows($resql) > 0) {
            $obj = $db->fetch_object($resql);
            return $obj->packaging;
        }
        return 0;
    }
}


