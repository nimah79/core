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

use Gibbon\Domain\Timetable\TimetableDayGateway;

include '../../gibbon.php';

$gibbonSchoolYearID = $_GET['gibbonSchoolYearID'];
$dates = 0;
if (isset($_POST['dates'])) {
    $dates = $_POST['dates'];
}
$gibbonTTDayID = $_POST['gibbonTTDayID'];
$URL = $_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_POST['q'])."/ttDates.php&gibbonSchoolYearID=$gibbonSchoolYearID";

if (isActionAccessible($guid, $connection2, '/modules/Timetable Admin/ttDates_edit_add.php') == false) {
    $URL .= '&return=error0';
    header("Location: {$URL}");
} else {
    //Proceed!
    //Validate Inputs
    if ($gibbonSchoolYearID == '' or $dates == '' or count($dates) < 1 or $gibbonTTDayID == '') {
        $URL .= '&return=error1';
        header("Location: {$URL}");
    } else {
        try {
            $data = array('gibbonSchoolYearID' => $gibbonSchoolYearID);
            $sql = 'SELECT * FROM gibbonSchoolYear WHERE gibbonSchoolYearID=:gibbonSchoolYearID';
            $result = $connection2->prepare($sql);
            $result->execute($data);
        } catch (PDOException $e) {
            $URL .= '&return=error2';
            header("Location: {$URL}");
            exit();
        }

        if ($result->rowCount() != 1) {
            $URL .= '&return=error2';
            header("Location: {$URL}");
        } else {
            //Get gibbonTTID for current day
            $timetableDayGateway = $container->get(TimetableDayGateway::class);
            $gibbonTTDay = $timetableDayGateway->getTTDayByID($gibbonTTDayID);
            if (!is_array($gibbonTTDay) && !empty($gibbonTTDay['gibbonTTID'])) {
                $URL .= '&return=error2';
                header("Location: {$URL}");
            }
            else {
                $gibbonTTID = $gibbonTTDay['gibbonTTID'];

                $partialFail = false;
                foreach ($dates as $date) {
                    if (isSchoolOpen($guid, date('Y-m-d', $date), $connection2, true) == false) {
                        $partialFail = true;
                    } else {
                        //Check if a day from the TT is already set
                        $days = $timetableDayGateway->selectDaysByDate(date('Y-m-d', $date), $gibbonTTID);

                        if ($days->rowCount() > 0) {
                            $partialFail = true;
                        }
                        else {
                            //Write to database
                            try {
                                $data = array('gibbonTTDayID' => $gibbonTTDayID, 'date' => date('Y-m-d', $date));
                                $sql = 'INSERT INTO gibbonTTDayDate SET gibbonTTDayID=:gibbonTTDayID, date=:date';
                                $result = $connection2->prepare($sql);
                                $result->execute($data);
                            } catch (PDOException $e) {
                                $partialFail = true;
                            }
                        }
                    }
                }

                //Report result
                if ($partialFail == true) {
                    $URL .= '&return=warning1';
                    header("Location: {$URL}");
                } else {
                    $URL .= '&return=success0';
                    header("Location: {$URL}");
                }
            }
        }
    }
}
