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

/**
 *    \file        core/triggers/interface_99_modMyodule_packagingtrigger.class.php
 *    \ingroup    packaging
 *    \brief        Sample trigger
 *    \remarks    You can create other triggers by copying this one
 *                - File name should be either:
 *                    interface_99_modPackaging_Mytrigger.class.php
 *                    interface_99_all_Mytrigger.class.php
 *                - The file must stay in core/triggers
 *                - The class name must be InterfaceMytrigger
 *                - The constructor method must be named InterfaceMytrigger
 *                - The name property name must be Mytrigger
 */

/**
 * Trigger class
 */
class Interfacepackagingtrigger
{
    private $db;

    /**
     * Constructor
     *
     * @param DoliDB $db Database handler
     */
    public function __construct($db) {
        $this->db = $db;

        $this->name = preg_replace('/^Interface/i', '', get_class($this));
        $this->family = "demo";
        $this->description = "Triggers of this module are empty functions."
            ."They have no effect."
            ."They are provided for tutorial purpose only.";
        // 'development', 'experimental', 'dolibarr' or version
        $this->version = 'development';
        $this->picto = 'packaging@packaging';
    }

    /**
     * Trigger name
     *
     * @return        string    Name of trigger file
     */
    public function getName() {
        return $this->name;
    }

    /**
     * Trigger description
     *
     * @return        string    Description of trigger file
     */
    public function getDesc() {
        return $this->description;
    }

    /**
     * Trigger version
     *
     * @return        string    Version of trigger file
     */
    public function getVersion() {
        global $langs;
        $langs->load("admin");

        if($this->version == 'development') {
            return $langs->trans("Development");
        }
        else if($this->version == 'experimental')

            return $langs->trans("Experimental");
        else if($this->version == 'dolibarr') return DOL_VERSION;
        else if($this->version) return $this->version;
        else {
            return $langs->trans("Unknown");
        }
    }

    /**
     * Function called when a Dolibarrr business event is done.
     * All functions "run_trigger" are triggered if file is inside directory htdocs/core/triggers
     *
     * @param string    $action code
     * @param Object    $object
     * @param User      $user   user
     * @param Translate $langs  langs
     * @param conf      $conf   conf
     * @return int <0 if KO, 0 if no triggered ran, >0 if OK
     */
    function runTrigger($action, $object, $user, $langs, $conf) {
        //For 8.0 remove warning
        $result = $this->run_trigger($action, $object, $user, $langs, $conf);
        return $result;
    }

    /**
     * Function called when a Dolibarrr business event is done.
     * All functions "run_trigger" are triggered if file
     * is inside directory core/triggers
     *
     * @param string    $action Event action code
     * @param Object    $object Object
     * @param User      $user   Object user
     * @param Translate $langs  Object langs
     * @param conf      $conf   Object conf
     * @return        int                        <0 if KO, 0 if no triggered ran, >0 if OK
     */
    public function run_trigger($action, $object, $user, $langs, $conf) {
        // Put here code you want to execute when a Dolibarr business events occurs.
        // Data and type of action are stored into $object and $action
        if($action == 'LINEORDER_SUPPLIER_CREATE' || $action == 'LINEORDER_SUPPLIER_UPDATE') {
            dol_include_once('/packaging/class/packaging.class.php');

            $conditionnement = TPackaging::getProductFournConditionnement($object);

            if(! empty($conditionnement)) {
                $new_qty = ceil($object->qty / $conditionnement);

                $commande = new CommandeFournisseur($this->db);
                $commande->fetch($object->fk_commande);

                $commande->updateline(
                    $object->id,
                    $object->desc,
                    $object->subprice,
                    $new_qty,
                    $object->remise_percent,
                    $object->tva_tx,
                    $object->localtax1_tx,
                    $object->localtax2_tx,
                    'HT',
                    $object->info_bits,
                    0,
                    true,
                    $object->date_start,
                    $object->date_end,
                    $object->array_options,
                    $object->fk_unit,
                    $object->multicurrency_subprice,
                    $object->ref_supplier
                );
            }
            //}
        }

        // Supplier orders
        else if($action == 'STOCK_MOVEMENT') {
            dol_include_once('/packaging/class/packaging.class.php');

            // Sélection de la ligne concernée
            $line = null;

            if(! empty($object->origin->lines)) {
                foreach($object->origin->lines as $l) {
                    if($object->product_id == $l->fk_product) {
                        $line = $l;
                    }
                }
            }

            if(! empty($line)) {

                $line->fk_commande = $object->origin->id;
                $conditionnement = TPackaging::getProductFournConditionnement($line);
                if(! empty($conditionnement)) {
                    $new_qty = round($object->qty * $conditionnement, 2);

                    // Mettre à jour le mouvement de stock
                    $sql = '
						UPDATE '.MAIN_DB_PREFIX.'stock_mouvement
						SET value = '.$new_qty.'
						WHERE fk_origin = '.$object->origin->id.'
						AND origintype = "order_supplier"
						AND fk_product =  '.$object->product_id.'
						ORDER BY tms DESC
						LIMIT 1;
					';

                    $statement = $this->db->query($sql);

                    // On supprime la précédente quantité insérée et on ajoute la nouvelle quantité calculée
                    $sql = "
						UPDATE ".MAIN_DB_PREFIX."product_stock 
						SET reel = (reel - ".$object->qty.") + ".$new_qty."
						WHERE fk_entrepot = ".$object->entrepot_id." 
						AND fk_product = ".$object->product_id."
					";

                    $statement = $this->db->query($sql);
                }
            }
        }

        return 0;
    }
}