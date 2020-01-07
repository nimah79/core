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

use Gibbon\Services\Format;
use Gibbon\Module\Reports\Domain\ReportingCycleGateway;
use Gibbon\Module\Reports\Domain\ReportingValueGateway;
use Gibbon\Module\Reports\Domain\ReportingProgressGateway;
use Gibbon\Module\Reports\Domain\ReportingScopeGateway;
use Gibbon\Module\Reports\Domain\ReportingCriteriaGateway;
use Gibbon\Module\Reports\Domain\ReportingAccessGateway;

require_once '../../gibbon.php';

$urlParams = [
    'gibbonReportingCycleID' => $_POST['gibbonReportingCycleID'] ?? '',
    'gibbonReportingScopeID' => $_POST['gibbonReportingScopeID'] ?? '',
    'scopeTypeID' => $_POST['scopeTypeID'] ?? '',
    'gibbonPersonID' => $_POST['gibbonPersonID'] ?? '',
    'allStudents' => $_POST['allStudents'] ?? '',
];

$URL = $gibbon->session->get('absoluteURL').'/index.php?q=/modules/Reports/reporting_write.php&'.http_build_query($urlParams);

if (isActionAccessible($guid, $connection2, '/modules/Reports/reporting_write.php') == false) {
    $URL .= '&return=error0';
    header("Location: {$URL}");
    exit;
} else {
    // Proceed!
    $partialFail = false;
    $reportingValueGateway = $container->get(ReportingValueGateway::class);
    $reportingProgressGateway = $container->get(ReportingProgressGateway::class);
    $reportingCriteriaGateway = $container->get(ReportingCriteriaGateway::class);
    $reportingAccessGateway = $container->get(ReportingAccessGateway::class);
    
    $values = $_POST['value'] ?? [];

    // Validate the required values are present
    if (empty($urlParams['gibbonReportingCycleID']) || empty($urlParams['gibbonReportingScopeID']) || empty($urlParams['scopeTypeID']) || empty($values)) {
        $URL .= '&return=error1';
        header("Location: {$URL}");
        exit;
    }

    // Validate the database relationships exist
    $reportingScope = $container->get(ReportingScopeGateway::class)->getByID($urlParams['gibbonReportingScopeID']);
    $reportingCycle = $container->get(ReportingCycleGateway::class)->getByID($urlParams['gibbonReportingCycleID']);
    if (empty($reportingCycle) || empty($reportingScope)) {
        $URL .= '&return=error2';
        header("Location: {$URL}");
        exit;
    }

    // ACCESS CHECK: overall check (for high-level access) or per-scope check for general access
    $accessCheck = $reportingAccessGateway->getAccessToScopeByPerson($urlParams['gibbonReportingScopeID'], $gibbon->session->get('gibbonPersonID'));
    $highestAction = getHighestGroupedAction($guid, $_POST['address'], $connection2);
    if ($highestAction == 'Write Reports_editAll') {
        $reportingOpen = ($accessCheck['reportingOpen'] ?? 'N') == 'Y';
        $canAccessReport = true;
        $canWriteReport = true;
    } elseif ($highestAction == 'Write Reports_mine') {
        $writeCheck = $reportingAccessGateway->getAccessToScopeAndCriteriaGroupByPerson($urlParams['gibbonReportingScopeID'], $reportingScope['scopeType'], $urlParams['scopeTypeID'], $gibbon->session->get('gibbonPersonID'));
        $reportingOpen = ($writeCheck['reportingOpen'] ?? 'N') == 'Y';
        $canAccessReport = ($accessCheck['canAccess'] ?? 'N') == 'Y';
        $canWriteReport = $reportingOpen && ($writeCheck['canWrite'] ?? 'N') == 'Y';
    }

    if (empty($canAccessReport) || !$canWriteReport) {
        $URL .= '&return=error0';
        header("Location: {$URL}");
        exit;
    }

    $data = [
        'gibbonReportingCycleID'    => $urlParams['gibbonReportingCycleID'],
        'gibbonReportingCriteriaID' => $urlParams['gibbonReportingScopeID'],
        'gibbonSchoolYearID'        => $reportingCycle['gibbonSchoolYearID'],
        'gibbonCourseClassID'       => $reportingScope['scopeType'] == 'Course' ? $urlParams['scopeTypeID'] : '',
        'gibbonPersonIDCreated'     => $gibbon->session->get('gibbonPersonID'),
    ];
    
    // Insert or update each record
    foreach ($values as $gibbonReportingCriteriaID => $value) {
        $data['gibbonReportingCriteriaID'] = $gibbonReportingCriteriaID;
        $data['value'] = $data['comment'] = $data['gibbonScaleGradeID'] = null;

        $criteriaType = $reportingCriteriaGateway->getCriteriaTypeByID($gibbonReportingCriteriaID);
        if ($criteriaType['valueType'] == 'Comment' || $criteriaType['valueType'] == 'Remark') {
            $data['comment'] = $value;
        } elseif ($criteriaType['valueType'] == 'Grade Scale') {
            $data['value'] = $reportingValueGateway->getGradeScaleValueByID($value);
            $data['gibbonScaleGradeID'] = $value;
        } else {
            $data['value'] = $value;
        }

        $updated = $reportingValueGateway->insertAndUpdate($data, [
            'value' => $data['value'],
            'comment' => $data['comment'],
            'gibbonScaleGradeID' => $data['gibbonScaleGradeID'],
            'gibbonPersonIDModified' => $gibbon->session->get('gibbonPersonID'),
            'timestampModified' => date('Y-m-d H:i:s'),
        ]);
        $partialFail = !$updated;
    }

    // Update progress
    // $dataProgress = [
    //     'gibbonReportingScopeID' => $urlParams['gibbonReportingScopeID'],
    //     'gibbonYearGroupID'      => $reportingScope['scopeType'] == 'Year Group' ? $urlParams['scopeTypeID'] : null,
    //     'gibbonRollGroupID'      => $reportingScope['scopeType'] == 'Roll Group' ? $urlParams['scopeTypeID'] : null,
    //     'gibbonCourseClassID'    => $reportingScope['scopeType'] == 'Course' ? $urlParams['scopeTypeID'] : '',
    //     'gibbonPersonIDStudent'  => $gibbonPersonIDStudent,
    //     'status'               => !empty($_POST['complete'])? 'Complete' : 'In Progress',
    // ];
    // $updated = $reportingProgressGateway->insertAndUpdate($dataProgress, [
    //     'status' => $dataProgress['status'],
    // ]);

    $URL .= $partialFail
        ? "&return=warning1"
        : "&return=success0";

    header("Location: {$URL}");
}