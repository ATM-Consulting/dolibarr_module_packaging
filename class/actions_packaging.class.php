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
            dol_include_once('/packaging/class/packaging.class.php');
            $object->lines[$parameters['i']]->fk_commande = $object->id;
            $conditionnement = TPackaging::getProductFournConditionnement($object->lines[$parameters['i']]);
            if(! empty($conditionnement)) {
                ?>
                <script type="text/javascript">
                    $(document).ready(function () {
                        let conditionnement = <?php echo $conditionnement;?>;
                        let qty = $('#row-<?php echo $object->lines[$parameters['i']]->id;?>').find('.linecolqty').html();
                        qty = qty.replace(/\s/g, '');
                        let total = parseFloat(conditionnement)*parseFloat(qty);
                        $('#row-<?php echo $object->lines[$parameters['i']]->id;?>').find('.linecolqty').append(' (x'+conditionnement+' = '+total+')');
                    });
                </script>
                <?php
            }
        }


	}

	public function printFieldListValue($parameters, &$object, &$action, $hookmanager) {
        if (in_array('ordersupplierdispatch', explode(':', $parameters['context']))) {
            dol_include_once('/packaging/class/packaging.class.php');
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
                           let conditionnement = <?php echo $conditionnement;?>;
                            $('input[name^="fk_commandefourndet"][value="<?php echo$parameters['objp']->rowid;?>"]').closest('tr').find('input[name^="qty_"]').after(' (x'+conditionnement+')');
                       });
                   </script>
                   <?php
               }
           }
        }
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
                    $line->desc .= "<br>".$langs->trans('Packaging') . ' : '. $conditionnement;
                    $line->description .= "<br>".$langs->trans('Packaging') . ' : '. $conditionnement;
                }
            }
        }
    }
}
