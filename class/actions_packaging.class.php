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
 * \file    class/actions_packaging.class.php
 * \ingroup packaging
 * \brief   This file is an example hook overload class file
 *          Put some comments here
 */

/**
 * Class Actionspackaging
 */
class Actionspackaging
{
    /**
     * @var DoliDb		Database handler (result of a new DoliDB)
     */
    public $db;

	/**
	 * @var array Hook results. Propagated to $hookmanager->resArray for later reuse
	 */
	public $results = array();

	/**
	 * @var string String displayed by executeHook() immediately after return
	 */
	public $resprints;

	/**
	 * @var array Errors
	 */
	public $errors = array();

	/**
	 * Constructor
     * @param DoliDB    $db    Database connector
	 */
	public function __construct($db)
	{
		$this->db = $db;
	}

	/**
	 * Overloading the doActions function : replacing the parent's function with the one below
	 *
	 * @param   array()         $parameters     Hook metadatas (context, etc...)
	 * @param   CommonObject    $object        The object to process (an invoice if you are in invoice module, a propale in propale's module, etc...)
	 * @param   string          $action        Current action (if set). Generally create or edit or null
	 * @param   HookManager     $hookmanager    Hook manager propagated to allow calling another hook
	 * @return  int                             < 0 on error, 0 on success, 1 to replace standard code
	 */
	public function printObjectLine($parameters, &$object, &$action, $hookmanager)
	{


		if (in_array('ordersuppliercard', explode(':', $parameters['context']))) {
		    global $langs;
		    $langs->load('packaging@packaging');
            dol_include_once('/packaging/class/packaging.class.php');
            dol_include_once('/form/class/html.form.class.php');
            $form = new Form($object->db);
            $object->lines[$parameters['i']]->fk_commande = $object->id;
            $conditionnement = TPackaging::getProductFournConditionnement($object->lines[$parameters['i']]);
            if(! empty($conditionnement)) {
                ?>
                <script type="text/javascript">
                    $(document).ready(function () {
                        let tooltip = '<?php echo $form->textwithtooltip(' ', $langs->trans('PackagingSupplierOrderTooltip',round($conditionnement,2),round($conditionnement,2).' x '.$object->lines[$parameters['i']]->qty.' = '.($conditionnement*$object->lines[$parameters['i']]->qty) ),2, 1, img_help(1,0)); ?>';
                        $('#row-<?php echo $object->lines[$parameters['i']]->id;?>').find('.linecolqty').append(tooltip);
                    });
                </script>
                <?php
            }
        }


	}

	public function formEditProductOptions($parameters, &$object, &$action, $hookmanager)
	{
        if (in_array('ordersuppliercard', explode(':', $parameters['context']))) {
            dol_include_once('/packaging/class/packaging.class.php');
            $parameters['line']->fk_commande = $object->id;
            $conditionnement = TPackaging::getProductFournConditionnement($parameters['line']);
            if(! empty($conditionnement)) {
                ?>
                <script type="text/javascript">
                    $(document).ready(function () {
                        let conditionnement = <?php echo $conditionnement;?>;
                        $('#qty').after(' (x '+conditionnement+')');
                        $('#qty').closest('td').attr('nowrap','nowrap');
                    });
                </script>
                <?php
            }
        }
	}

	public function printFieldListValue($parameters, &$object, &$action, $hookmanager) {
        if (in_array('ordersupplierdispatch', explode(':', $parameters['context']))) {
            global $langs;
            $langs->load('packaging@packaging');

            dol_include_once('/packaging/class/packaging.class.php');
            dol_include_once('/form/class/html.form.class.php');
            $form = new Form($object->db);
           if($parameters['is_information_row']) {
               $myLine = '';

               foreach($object->lines as $line) {
                   if($line->id == $parameters['objp']->rowid) {
                       $myLine = $line;
                       $myLine->fk_commande = $object->id;
                       break;
                   }
               }
               $conditionnement = TPackaging::getProductFournConditionnement($myLine);
               if(! empty($conditionnement)) {
                   ?>
                   <script type="text/javascript">
                       $(document).ready(function () {
                           let tooltip = '<?php echo $form->textwithtooltip(' ', $langs->trans('PackagingSupplierOrderTooltip',round($conditionnement,2), $langs->trans('PackagingValueWillBeMultiplicated') ),2, 1, img_help(1,0)); ?>';
                            $('input[name^="fk_commandefourndet"][value="<?php echo$parameters['objp']->rowid;?>"]').closest('tr').find('input[name^="qty_"]').after(tooltip);
                       });
                   </script>
                   <?php
               }
           }
        }
        if (in_array('stockreplenishlist', explode(':', $parameters['context'])) && !empty($parameters['objp']->rowid)) {
            global $conf, $db;
            dol_include_once('/packaging/class/packaging.class.php');
            $mode = GETPOST('mode', 'alpha');
            $draftorder = GETPOST('draftorder');
            if (GETPOST('button_removefilter_x', 'alpha') || GETPOST('button_removefilter.x', 'alpha') || GETPOST('button_removefilter', 'alpha') || isset($_POST['valid'])) // Both test are required to be compatible with all browsers
            {
                $draftorder = '';
            }
            $usevirtualstock = !empty($conf->global->STOCK_USE_VIRTUAL_STOCK);
            if ($mode == 'physical') $usevirtualstock = 0;
            if ($mode == 'virtual') $usevirtualstock = 1;
            if($draftorder == 'on') $draftchecked = true;
            $prod = new Product($db);
            $prod->fetch($parameters['objp']->rowid);
            $prod->load_stock('warehouseopen, warehouseinternal', $draftchecked);
            if ($usevirtualstock)
            {
                // If option to increase/decrease is not on an object validation, virtual stock may differs from physical stock.
                $stock = $prod->stock_theorique;
            }
            else
            {
                $stock = $prod->stock_reel;
            }
            $draftordered = 0;
            if (isset($draftchecked)) {
//                if(!empty($usevirtualstock)) $draftordered = TPackaging::loadQtySupplierOrder($parameters['objp']->rowid,'0');
                $qtySupplier = TPackaging::loadQtySupplierOrder($parameters['objp']->rowid,'0,1,2,3,4');
            }
            else $qtySupplier = TPackaging::loadQtySupplierOrder($parameters['objp']->rowid,'1,2,3,4');
            $qtyReception = TPackaging::loadQtyReception($parameters['objp']->rowid,'4');

            $ordered = $qtySupplier - $qtyReception;

            $desiredstock = ($parameters['objp']->desiredstockpse ? $parameters['objp']->desiredstockpse : $parameters['objp']->desiredstock);
            $alertstock = ($parameters['objp']->seuil_stock_alertepse ? $parameters['objp']->seuil_stock_alertepse : $parameters['objp']->seuil_stock_alerte);

            if(empty($usevirtualstock)) $stocktobuy = max(max($desiredstock, $alertstock) - $stock - $ordered, 0);
            else $stocktobuy = max(max($desiredstock, $alertstock) - $stock , 0); //ordered is already in $stock in virtual mode
            return '<td style="display:none;" class="packagingReload"><input type="hidden" class="packaging_ordered" value="'.$ordered.'"/><input type="hidden" class="packaging_stocktobuy" value="'.$stocktobuy.'"/></td>';
        }

    }

    public function printFieldListFooter($parameters, &$object, &$action, $hookmanager) {
        ?>
        <script type="text/javascript">
            $(document).ready(function(){
                $('.packagingReload').each(function(){
                   let ordered = $(this).find('.packaging_ordered').val();
                   let stocktobuy = $(this).find('.packaging_stocktobuy').val();
                   let tr = $(this).closest('tr');
                   $(tr).find('input[name^="tobuy"]').val(stocktobuy);
                   $(tr).find('a[href^="replenishorders.php"]').html(ordered);
                });
            });
        </script>
        <?php
    }


    public function beforePDFCreation($parameters, &$object, &$action, $hookmanager) {

        if (in_array('ordersuppliercard', explode(':', $parameters['context']))) {
            global $langs;
            dol_include_once('/packaging/class/packaging.class.php');

            $langs->load('packaging@packaging');
            foreach($object->lines as &$line) {
                $line->fk_commande = $object->id;
                $conditionnement = TPackaging::getProductFournConditionnement($line);
                if(!empty($conditionnement)) {
                    if(!empty($line->desc)) $line->desc .= "<br>".$langs->trans('Packaging') . ' : '. round($conditionnement,2);
                    else if(!empty($line->description))$line->description .= "<br>".$langs->trans('Packaging') . ' : '. round($conditionnement,2);
                    else {
                        $line->desc = $langs->trans('Packaging') . ' : '. round($conditionnement,2);
                        $line->description = $langs->trans('Packaging') . ' : '. round($conditionnement, 2);
                    }
                }
            }
        }
    }
    public function loadvirtualstock($parameters, &$object, &$action, $hookmanager) {
	    //On écrase le stock virtuel
        if (in_array('productdao', explode(':', $parameters['context']))) {
            global $conf;
            dol_include_once('/packaging/class/packaging.class.php');
            dol_include_once('/fourn/class/fournisseur.commande.class.php');
            $stock_commande_fournisseur = 0;
            $stock_reception_fournisseur = 0;
            $filterCFStatus = '1,2,3,4';
            $filterReceptionStatus = '4';
            if (isset($parameters['includedraftpoforvirtual'])){
                $filterCFStatus = '0,'.$filterCFStatus;
                $filterReceptionStatus = '0,'.$filterReceptionStatus;
            }

            if (!empty($conf->fournisseur->enabled))
            {

                $result = $object->load_stats_commande_fournisseur(0, $filterCFStatus, 1);
                if ($result < 0) dol_print_error($object->db, $object->error);
                $stock_commande_fournisseur = $object->stats_commande_fournisseur['qty'];
            }
            if (!empty($conf->fournisseur->enabled) && empty($conf->reception->enabled))
            {

                $result = $object->load_stats_reception(0, $filterReceptionStatus, 1);
                if ($result < 0) dol_print_error($object->db, $object->error);
                $stock_reception_fournisseur = $object->stats_reception['qty'];
            }
            if (!empty($conf->fournisseur->enabled) && !empty($conf->reception->enabled))
            {
                $result = $object->load_stats_reception(0, $filterReceptionStatus, 1);			// Use same tables than when module reception is not used.
                if ($result < 0) dol_print_error($object->db, $object->error);
                $stock_reception_fournisseur = $object->stats_reception['qty'];
            }
            //On annule les mouvements de stocks qui ont été fait sur la partie "commandes fournisseurs"
            $object->stock_theorique -= $this->_calcStockTheo($stock_commande_fournisseur, $stock_reception_fournisseur);

            //On récupère les bonnes stats avec les multiplications du conditionnement
            $stock_commande_fournisseur = TPackaging::loadQtySupplierOrder($object->id, $filterCFStatus);
            $stock_reception_fournisseur = TPackaging::loadQtyReception($object->id, $filterReceptionStatus);

            $object->stock_theorique += $this->_calcStockTheo($stock_commande_fournisseur, $stock_reception_fournisseur);
        }
    }

    public function addMoreActionsButtons($parameters, &$object, &$action, $hookmanager) {
        if (in_array('stockproductcard', explode(':', $parameters['context']))) {
            global $langs, $conf, $db;
            dol_include_once('/packaging/class/packaging.class.php');
            dol_include_once('/form/class/html.form.class.php');
            $form = new Form($object->db);

            $found = 0;
            //TODO à partir de la 12 utilisez les hooks loadstats
            //Pour que ce soit compatible en 11 obliger de recréer la tooltip
            $helpondiff = '<strong>'.$langs->trans("StockDiffPhysicTeoric").':</strong><br>';
            // Number of customer orders running
            if(! empty($conf->commande->enabled)) {
                if($found) $helpondiff .= '<br>';
                else $found = 1;
                $result = $object->load_stats_commande(0, '1,2', 1);
                $helpondiff .= $langs->trans("ProductQtyInCustomersOrdersRunning").': '.$object->stats_commande['qty'];
                $result = $object->load_stats_commande(0, '0', 1);
                if($result < 0) dol_print_error($db, $object->error);
                $helpondiff .= ' ('.$langs->trans("ProductQtyInDraft").': '.$object->stats_commande['qty'].')';
            }

            // Number of product from customer order already sent (partial shipping)
            if(! empty($conf->expedition->enabled)) {
                require_once DOL_DOCUMENT_ROOT.'/expedition/class/expedition.class.php';
                $filterShipmentStatus = '';
                if(! empty($conf->global->STOCK_CALCULATE_ON_SHIPMENT)) {
                    $filterShipmentStatus = Expedition::STATUS_VALIDATED.','.Expedition::STATUS_CLOSED;
                }
                else if(! empty($conf->global->STOCK_CALCULATE_ON_SHIPMENT_CLOSE)) {
                    $filterShipmentStatus = Expedition::STATUS_CLOSED;
                }
                if($found) $helpondiff .= '<br>';
                else $found = 1;
                $result = $object->load_stats_sending(0, '2', 1, $filterShipmentStatus);
                $helpondiff .= $langs->trans("ProductQtyInShipmentAlreadySent").': '.$object->stats_expedition['qty'];
            }

            // Number of supplier order running
            if(! empty($conf->fournisseur->enabled)) {
                if($found) $helpondiff .= '<br>';
                else $found = 1;
                $result = TPackaging::loadQtySupplierOrder($object->id, '3,4');
                $helpondiff .= $langs->trans("ProductQtyInSuppliersOrdersRunning").': '.$result;
                $result = TPackaging::loadQtySupplierOrder($object->id, '0,1,2');
                if($result < 0) dol_print_error($db, $object->error);
                $helpondiff .= ' ('.$langs->trans("ProductQtyInDraftOrWaitingApproved").': '.$result.')';
            }

            // Number of product from supplier order already received (partial receipt)
            if(! empty($conf->fournisseur->enabled)) {
                if($found) $helpondiff .= '<br>';
                else $found = 1;
                $result = TPackaging::loadQtyReception($object->id, '4');
                $helpondiff .= $langs->trans("ProductQtyInSuppliersShipmentAlreadyRecevied").': '.$result;
            }

            // Number of product in production
            if(! empty($conf->mrp->enabled)) {
                if($found) $helpondiff .= '<br>';
                else $found = 1;
                $helpondiff .= $langs->trans("ProductQtyToConsumeByMO").': '.$object->stats_mrptoconsume['qty'].'<br>';
                $helpondiff .= $langs->trans("ProductQtyToProduceByMO").': '.$object->stats_mrptoproduce['qty'];
            }

            ?>
            <script type="text/javascript">
                $(document).ready(function(){
                   let tdVirtual = $('td:contains("<?php echo $langs->trans('VirtualStock'); ?>")').next();
                   let tooltip = '<?php echo addslashes($form->textwithtooltip('', $helpondiff,2, 1, img_help(1,0))); ?>';
                   tdVirtual.find('.classfortooltip').remove();
                   tdVirtual.append(tooltip);
                });
            </script>
            <?php

        }
    }





    public function _calcStockTheo($stock_commande_fournisseur, $stock_reception_fournisseur) {
	    global $conf;

        if (!empty($conf->global->STOCK_CALCULATE_ON_RECEPTION) || !empty($conf->global->STOCK_CALCULATE_ON_RECEPTION_CLOSE)) {
           return ($stock_commande_fournisseur - $stock_reception_fournisseur);
        }
        elseif (!empty($conf->global->STOCK_CALCULATE_ON_SUPPLIER_DISPATCH_ORDER)) {
            return ($stock_commande_fournisseur - $stock_reception_fournisseur);
        }
        elseif (!empty($conf->global->STOCK_CALCULATE_ON_SUPPLIER_VALIDATE_ORDER)) {
            return $stock_reception_fournisseur;
        }
        elseif (!empty($conf->global->STOCK_CALCULATE_ON_SUPPLIER_BILL)) {
            return ($stock_commande_fournisseur - $stock_reception_fournisseur);
        }
        return 0;
    }
}


