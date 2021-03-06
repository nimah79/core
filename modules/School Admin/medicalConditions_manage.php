<?php
/*
Gibbon, Flexible & Open School System
Copyright (C) 2010, Ross Parker

This program is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 3 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program. If not, see <http://www.gnu.org/licenses/>.
*/

use Gibbon\Tables\DataTable;
use Gibbon\Services\Format;
use Gibbon\Domain\Students\MedicalConditionGateway;

if (isActionAccessible($guid, $connection2, '/modules/School Admin/medicalConditions_manage.php') == false) {
    //Acess denied
    echo "<div class='error'>";
    echo __('You do not have access to this action.');
    echo '</div>';
} else {
    //Proceed!
    $page->breadcrumbs->add(__('Manage Medical Conditions'));

    if (isset($_GET['return'])) {
        returnProcess($guid, $_GET['return'], null, null);
    }

    $medicalConditionGateway = $container->get(MedicalConditionGateway::class);

    // QUERY
    $criteria = $medicalConditionGateway->newQueryCriteria(true)
        ->sortBy(['name'])
        ->fromPOST();

    $medicalConditions = $medicalConditionGateway->queryMedicalConditions($criteria);

    // DATA TABLE
    $table = DataTable::createPaginated('medicalConditionsManage', $criteria);

    $table->addHeaderAction('add', __('Add'))
        ->setURL('/modules/School Admin/medicalConditions_manage_add.php')
        ->displayLabel();

    $table->addColumn('name', __('Name'));

    // ACTIONS
    $table->addActionColumn()
        ->addParam('gibbonMedicalConditionID')
        ->format(function ($facilities, $actions) use ($guid) {
            $actions->addAction('edit', __('Edit'))
                    ->setURL('/modules/School Admin/medicalConditions_manage_edit.php');

            $actions->addAction('delete', __('Delete'))
                    ->setURL('/modules/School Admin/medicalConditions_manage_delete.php');
        });

    echo $table->render($medicalConditions);
}
