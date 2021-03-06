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

namespace Gibbon\Domain\Planner;

use Gibbon\Domain\Traits\TableAware;
use Gibbon\Domain\QueryCriteria;
use Gibbon\Domain\QueryableGateway;

/**
 * Planner Entry Gateway
 *
 * @version v17
 * @since   v17
 */
class PlannerEntryGateway extends QueryableGateway
{
    use TableAware;

    private static $tableName = 'gibbonPlannerEntry';
    private static $primaryKey = 'gibbonPlannerEntryID';
    private static $searchableColumns = [];
    
    public function queryHomeworkByPerson($criteria, $gibbonSchoolYearID, $gibbonPersonID)
    {
        $criteria->addFilterRules([
            'class' => function ($query, $gibbonCourseClassID) {
                return $query
                    ->where('gibbonCourseClass.gibbonCourseClassID = :gibbonCourseClassID')
                    ->bindValue('gibbonCourseClassID', $gibbonCourseClassID);
            },
            'submission' => function ($query, $homeworkSubmission) {
                return $query
                    ->where('gibbonPlannerEntry.homeworkSubmission = :homeworkSubmission')
                    ->bindValue('homeworkSubmission', $homeworkSubmission);
            },
            'viewableParents' => function ($query, $viewableParents) {
                return $query
                    ->where('gibbonPlannerEntry.viewableParents = :viewableParents')
                    ->bindValue('viewableParents', $viewableParents);
            },
            'viewableStudents' => function ($query, $viewableStudents) {
                return $query
                    ->where('gibbonPlannerEntry.viewableStudents = :viewableStudents')
                    ->bindValue('viewableStudents', $viewableStudents);
            },
            'weekly' => function ($query, $weekly) {
                return $query
                    ->where('gibbonPlannerEntry.date>:lastWeek')
                    ->bindValue('lastWeek', date('Y-m-d', strtotime('-1 week')))
                    ->where('gibbonPlannerEntry.date<=:today')
                    ->bindValue('today', date('Y-m-d'));
            },
        ]);

        $query = $this
            ->newQuery()
            ->cols([
                "'teacherRecorded' AS type",
                'gibbonPlannerEntry.gibbonPlannerEntryID',
                'gibbonPlannerEntry.gibbonUnitID',
                'gibbonPlannerEntry.gibbonCourseClassID',
                'gibbonCourse.nameShort AS course',
                'gibbonCourseClass.nameShort AS class',
                'gibbonPlannerEntry.name',
                'gibbonPlannerEntry.date',
                'gibbonPlannerEntry.timeStart',
                'gibbonPlannerEntry.timeEnd',
                'gibbonPlannerEntry.viewableStudents',
                'gibbonPlannerEntry.viewableParents',
                'gibbonPlannerEntry.homework',
                'gibbonCourseClassPerson.role',
                'gibbonPlannerEntry.homeworkDueDateTime',
                'gibbonPlannerEntry.homeworkDetails',
                'gibbonPlannerEntry.homeworkSubmission',
                'gibbonPlannerEntry.homeworkSubmissionRequired',
                'gibbonPerson.dateStart',
                'gibbonUnit.name as unit',
                ])
            ->from($this->getTableName())
            ->innerJoin('gibbonCourseClass', 'gibbonPlannerEntry.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID')
            ->innerJoin('gibbonCourseClassPerson', 'gibbonCourseClass.gibbonCourseClassID=gibbonCourseClassPerson.gibbonCourseClassID')
            ->innerJoin('gibbonCourse', 'gibbonCourse.gibbonCourseID=gibbonCourseClass.gibbonCourseID')
            ->innerJoin('gibbonPerson', 'gibbonPerson.gibbonPersonID=gibbonCourseClassPerson.gibbonPersonID')
            ->leftJoin('gibbonUnit', 'gibbonUnit.gibbonUnitID=gibbonPlannerEntry.gibbonUnitID')
            ->where('gibbonCourseClassPerson.gibbonPersonID=:gibbonPersonID')
            ->bindValue('gibbonPersonID', $gibbonPersonID)
            ->where('gibbonCourse.gibbonSchoolYearID=:gibbonSchoolYearID')
            ->bindValue('gibbonSchoolYearID', $gibbonSchoolYearID)
            ->where("gibbonPlannerEntry.homework='Y'")
            ->where('(gibbonCourseClassPerson.dateEnrolled IS NULL OR gibbonCourseClassPerson.dateEnrolled <= gibbonPlannerEntry.date)')
            ->where("(gibbonCourseClassPerson.role NOT LIKE '%Left' OR gibbonCourseClassPerson.dateUnenrolled > gibbonPlannerEntry.homeworkDueDateTime)")
            ->where("(gibbonPlannerEntry.date < :todayDate OR (gibbonPlannerEntry.date=:todayDate AND timeEnd <= :todayTime))")
            ->bindValue('todayDate', date('Y-m-d'))
            ->bindValue('todayTime', date('H:i:s'));
          
        $this->unionAllWithCriteria($query, $criteria)
            ->cols([
                "'studentRecorded' AS type",
                'gibbonPlannerEntry.gibbonPlannerEntryID',
                'gibbonPlannerEntry.gibbonUnitID',
                'gibbonPlannerEntry.gibbonCourseClassID',
                'gibbonCourse.nameShort AS course',
                'gibbonCourseClass.nameShort AS class',
                'gibbonPlannerEntry.name',
                'gibbonPlannerEntry.date',
                'gibbonPlannerEntry.timeStart',
                'gibbonPlannerEntry.timeEnd',
                "'Y' AS viewableStudents",
                "'Y' AS viewableParents",
                "'Y' AS homework",
                'gibbonCourseClassPerson.role',
                'gibbonPlannerEntryStudentHomework.homeworkDueDateTime',
                'gibbonPlannerEntryStudentHomework.homeworkDetails',
                "'N' AS homeworkSubmission",
                "'N' AS homeworkSubmissionRequired",
                'gibbonPerson.dateStart',
                'gibbonUnit.name as unit',
                ])
            ->from($this->getTableName())
            ->innerJoin('gibbonCourseClass', 'gibbonPlannerEntry.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID')
            ->innerJoin('gibbonCourseClassPerson', 'gibbonCourseClass.gibbonCourseClassID=gibbonCourseClassPerson.gibbonCourseClassID')
            ->innerJoin('gibbonCourse', 'gibbonCourse.gibbonCourseID=gibbonCourseClass.gibbonCourseID')
            ->innerJoin('gibbonPerson', 'gibbonPerson.gibbonPersonID=gibbonCourseClassPerson.gibbonPersonID')
            ->innerJoin('gibbonPlannerEntryStudentHomework', 'gibbonPlannerEntryStudentHomework.gibbonPlannerEntryID=gibbonPlannerEntry.gibbonPlannerEntryID 
            AND gibbonPlannerEntryStudentHomework.gibbonPersonID=gibbonCourseClassPerson.gibbonPersonID')
            ->leftJoin('gibbonUnit', 'gibbonUnit.gibbonUnitID=gibbonPlannerEntry.gibbonUnitID')
            ->where('gibbonCourseClassPerson.gibbonPersonID=:gibbonPersonID')
            ->bindValue('gibbonPersonID', $gibbonPersonID)
            ->where('gibbonCourse.gibbonSchoolYearID=:gibbonSchoolYearID')
            ->bindValue('gibbonSchoolYearID', $gibbonSchoolYearID)
            ->where('(gibbonCourseClassPerson.dateEnrolled IS NULL OR gibbonCourseClassPerson.dateEnrolled <= gibbonPlannerEntry.date)')
            ->where("(gibbonCourseClassPerson.role NOT LIKE '%Left' OR gibbonCourseClassPerson.dateUnenrolled > gibbonPlannerEntry.homeworkDueDateTime)")
            ->where("(gibbonPlannerEntry.date < :todayDate OR (gibbonPlannerEntry.date=:todayDate AND timeEnd <= :todayTime))")
            ->bindValue('todayDate', date('Y-m-d'))
            ->bindValue('todayTime', date('H:i:s'));

        return $this->runQuery($query, $criteria);
    }

    public function getPlannerEntryByID($gibbonPlannerEntryID)
    {
        $data = ['gibbonPlannerEntryID' => $gibbonPlannerEntryID];
        $sql = "SELECT * FROM gibbonPlannerEntry WHERE gibbonPlannerEntryID=:gibbonPlannerEntryID";

        return $this->db()->selectOne($sql, $data);
    }

    public function selectUpcomingHomeworkByStudent($gibbonSchoolYearID, $gibbonPersonID, $viewableBy = 'viewableStudents')
    {
        $data = [
            'gibbonSchoolYearID' => $gibbonSchoolYearID,
            'gibbonPersonID' => $gibbonPersonID,
            'todayTime' => date('Y-m-d H:i:s'),
            'todayDate' => date('Y-m-d'),
            'time' => date('H:i:s'),
        ];
        $sql = "
            (SELECT 'teacherRecorded' AS type, gibbonPlannerEntry.gibbonPlannerEntryID, gibbonUnitID, gibbonCourse.nameShort AS course, gibbonCourseClass.nameShort AS class, gibbonPlannerEntry.name, date, timeStart, timeEnd, viewableStudents, viewableParents, homework, homeworkDueDateTime, role, gibbonPlannerEntryStudentTracker.homeworkComplete, (CASE WHEN gibbonPlannerEntryHomework.gibbonPlannerEntryHomeworkID IS NOT NULL THEN 'Y' ELSE 'N' END) as onlineSubmission
                FROM gibbonPlannerEntry 
                JOIN gibbonCourseClass ON (gibbonPlannerEntry.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID) 
                JOIN gibbonCourseClassPerson ON (gibbonCourseClass.gibbonCourseClassID=gibbonCourseClassPerson.gibbonCourseClassID) 
                JOIN gibbonCourse ON (gibbonCourse.gibbonCourseID=gibbonCourseClass.gibbonCourseID) 
                LEFT JOIN gibbonPlannerEntryStudentTracker ON (gibbonPlannerEntryStudentTracker.gibbonPlannerEntryID=gibbonPlannerEntry.gibbonPlannerEntryID 
                    AND gibbonPlannerEntryStudentTracker.gibbonPersonID=gibbonCourseClassPerson.gibbonPersonID)
                LEFT JOIN gibbonPlannerEntryHomework ON (gibbonPlannerEntryHomework.gibbonPlannerEntryID=gibbonPlannerEntry.gibbonPlannerEntryID 
                    AND gibbonPlannerEntryHomework.gibbonPersonID=gibbonCourseClassPerson.gibbonPersonID AND gibbonPlannerEntryHomework.version='Final')
                WHERE gibbonSchoolYearID=:gibbonSchoolYearID AND gibbonCourseClassPerson.gibbonPersonID=:gibbonPersonID 
                AND homework='Y' 
                AND (role='Teacher' OR (role='Student' AND $viewableBy='Y')) 
                AND homeworkDueDateTime>:todayTime 
                AND ((date<:todayDate) OR (date=:todayDate AND timeEnd<=:time))
                AND (gibbonCourseClassPerson.dateEnrolled IS NULL OR gibbonCourseClassPerson.dateEnrolled <= gibbonPlannerEntry.date)
                AND (gibbonCourseClassPerson.dateUnenrolled IS NULL OR gibbonCourseClassPerson.dateUnenrolled > gibbonPlannerEntry.homeworkDueDateTime)
            )
            UNION
            (SELECT 'studentRecorded' AS type, gibbonPlannerEntry.gibbonPlannerEntryID, gibbonUnitID, gibbonCourse.nameShort AS course, gibbonCourseClass.nameShort AS class, gibbonPlannerEntry.name, date, timeStart, timeEnd, 'Y' AS viewableStudents, 'Y' AS viewableParents, 'Y' AS homework, gibbonPlannerEntryStudentHomework.homeworkDueDateTime, role, gibbonPlannerEntryStudentHomework.homeworkComplete, (CASE WHEN gibbonPlannerEntryHomework.gibbonPlannerEntryHomeworkID IS NOT NULL THEN 'Y' ELSE 'N' END) as onlineSubmission FROM gibbonPlannerEntry JOIN gibbonCourseClass ON (gibbonPlannerEntry.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID) 
                JOIN gibbonCourseClassPerson ON (gibbonCourseClass.gibbonCourseClassID=gibbonCourseClassPerson.gibbonCourseClassID) 
                JOIN gibbonCourse ON (gibbonCourse.gibbonCourseID=gibbonCourseClass.gibbonCourseID) 
                JOIN gibbonPlannerEntryStudentHomework ON (gibbonPlannerEntryStudentHomework.gibbonPlannerEntryID=gibbonPlannerEntry.gibbonPlannerEntryID 
                AND gibbonPlannerEntryStudentHomework.gibbonPersonID=gibbonCourseClassPerson.gibbonPersonID) 
                LEFT JOIN gibbonPlannerEntryHomework ON (gibbonPlannerEntryHomework.gibbonPlannerEntryID=gibbonPlannerEntry.gibbonPlannerEntryID 
                    AND gibbonPlannerEntryHomework.gibbonPersonID=gibbonCourseClassPerson.gibbonPersonID AND gibbonPlannerEntryHomework.version='Final')
                WHERE gibbonSchoolYearID=:gibbonSchoolYearID 
                AND gibbonCourseClassPerson.gibbonPersonID=:gibbonPersonID 
                AND (role='Teacher' OR (role='Student' AND $viewableBy='Y')) 
                AND gibbonPlannerEntryStudentHomework.homeworkDueDateTime>:todayTime 
                AND ((date<:todayDate) OR (date=:todayDate AND timeEnd<=:time))
                AND (gibbonCourseClassPerson.dateEnrolled IS NULL OR gibbonCourseClassPerson.dateEnrolled <= gibbonPlannerEntry.date)
                AND (gibbonCourseClassPerson.dateUnenrolled IS NULL OR gibbonCourseClassPerson.dateUnenrolled > gibbonPlannerEntry.homeworkDueDateTime)
            )
            ORDER BY homeworkDueDateTime, type";

        return $this->db()->select($sql, $data);
    }

    public function selectHomeworkTrackerByStudent($gibbonSchoolYearID, $gibbonPersonID)
    {
        $data = ['gibbonSchoolYearID' => $gibbonSchoolYearID, 'gibbonPersonID' => $gibbonPersonID];
        $sql = "
            (SELECT gibbonPlannerEntryStudentTracker.gibbonPlannerEntryID, 'teacherRecorded' AS type, homeworkComplete 
            FROM gibbonPlannerEntryStudentTracker JOIN gibbonPlannerEntry ON (gibbonPlannerEntryStudentTracker.gibbonPlannerEntryID=gibbonPlannerEntry.gibbonPlannerEntryID) JOIN gibbonCourseClass ON (gibbonPlannerEntry.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID) JOIN gibbonCourse ON (gibbonCourseClass.gibbonCourseID=gibbonCourse.gibbonCourseID) WHERE gibbonSchoolYearID=:gibbonSchoolYearID AND gibbonPersonID=:gibbonPersonID and homeworkComplete='Y')
            UNION
            (SELECT gibbonPlannerEntryStudentHomework.gibbonPlannerEntryID, 'studentRecorded' AS type, homeworkComplete
            FROM gibbonPlannerEntryStudentHomework JOIN gibbonPlannerEntry ON (gibbonPlannerEntryStudentHomework.gibbonPlannerEntryID=gibbonPlannerEntry.gibbonPlannerEntryID) JOIN gibbonCourseClass ON (gibbonPlannerEntry.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID) JOIN gibbonCourse ON (gibbonCourseClass.gibbonCourseID=gibbonCourse.gibbonCourseID) WHERE gibbonSchoolYearID=:gibbonSchoolYearID AND gibbonPersonID=:gibbonPersonID and homeworkComplete='Y')
            ORDER BY gibbonPlannerEntryID, type
            ";

        return $this->db()->select($sql, $data);
    }

    public function selectHomeworkSubmissionsByStudent($gibbonSchoolYearID, $gibbonPersonID)
    {
        $data = ['gibbonSchoolYearID' => $gibbonSchoolYearID, 'gibbonPersonID' => $gibbonPersonID];
        $sql = "SELECT TRIM(LEADING '0' FROM gibbonPlannerEntryHomework.gibbonPlannerEntryID) as groupBy, gibbonPlannerEntryHomework.* 
            FROM gibbonPlannerEntryHomework 
            JOIN gibbonPlannerEntry ON (gibbonPlannerEntry.gibbonPlannerEntryID=gibbonPlannerEntryHomework.gibbonPlannerEntryID) 
            JOIN gibbonCourseClass ON (gibbonPlannerEntry.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID) 
            JOIN gibbonCourse ON (gibbonCourse.gibbonCourseID=gibbonCourseClass.gibbonCourseID) 
            WHERE gibbonCourse.gibbonSchoolYearID=:gibbonSchoolYearID
            AND gibbonPlannerEntryHomework.gibbonPersonID=:gibbonPersonID 
            ORDER BY count DESC";

        return $this->db()->select($sql, $data);
    }

    public function selectHomeworkSubmissionCounts($gibbonPlannerEntryID)
    {
        $gibbonPlannerEntryIDList = is_array($gibbonPlannerEntryID)? $gibbonPlannerEntryID : [$gibbonPlannerEntryID];
        $gibbonPlannerEntryIDList = array_map(function($item) {
            return str_pad($item, 14, '0', STR_PAD_LEFT);
        }, $gibbonPlannerEntryIDList);

        $data = ['gibbonPlannerEntryIDList' => implode(',', $gibbonPlannerEntryIDList)];
        $sql = "SELECT TRIM(LEADING '0' FROM gibbonPlannerEntry.gibbonPlannerEntryID) as groupBy,
            COUNT(DISTINCT CASE WHEN gibbonPlannerEntryHomework.version='Final' AND gibbonPlannerEntryHomework.status='On Time' THEN  gibbonPlannerEntryHomework.gibbonPersonID END) as onTime,
            COUNT(DISTINCT CASE WHEN gibbonPlannerEntryHomework.version='Final' AND gibbonPlannerEntryHomework.status='Late' THEN  gibbonPlannerEntryHomework.gibbonPersonID END) as late,
            (SELECT COUNT(*) FROM gibbonCourseClassPerson WHERE gibbonCourseClassPerson.gibbonCourseClassID=gibbonPlannerEntry.gibbonCourseClassID AND role='Student' AND (gibbonCourseClassPerson.dateEnrolled IS NULL OR gibbonCourseClassPerson.dateEnrolled <= gibbonPlannerEntry.date) AND (gibbonCourseClassPerson.dateUnenrolled IS NULL OR gibbonCourseClassPerson.dateUnenrolled > gibbonPlannerEntry.date) ) as total
            FROM gibbonPlannerEntry
            LEFT JOIN gibbonPlannerEntryHomework ON (gibbonPlannerEntry.gibbonPlannerEntryID=gibbonPlannerEntryHomework.gibbonPlannerEntryID)
            WHERE FIND_IN_SET(gibbonPlannerEntry.gibbonPlannerEntryID, :gibbonPlannerEntryIDList)
            GROUP BY gibbonPlannerEntry.gibbonPlannerEntryID
            ";

        return $this->db()->select($sql, $data);


    }

    public function selectAllUpcomingHomework($gibbonSchoolYearID)
    {
        $data = ['gibbonSchoolYearID' => $gibbonSchoolYearID, 'homeworkDueDateTime' => date('Y-m-d H:i:s'), 'date1' => date('Y-m-d'), 'date2' => date('Y-m-d'), 'timeEnd' => date('H:i:s')];
        $sql = "SELECT gibbonPlannerEntryID, gibbonUnitID, gibbonCourse.nameShort AS course, gibbonCourseClass.nameShort AS class, gibbonPlannerEntry.name, date, timeStart, timeEnd, viewableStudents, viewableParents, homework, homeworkDueDateTime 
            FROM gibbonPlannerEntry 
            JOIN gibbonCourseClass ON (gibbonPlannerEntry.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID) 
            JOIN gibbonCourse ON (gibbonCourse.gibbonCourseID=gibbonCourseClass.gibbonCourseID) 
            WHERE gibbonCourse.gibbonSchoolYearID=:gibbonSchoolYearID AND homework='Y' AND homeworkDueDateTime>:homeworkDueDateTime AND ((date<:date1) OR (date=:date2 AND timeEnd<=:timeEnd)) ORDER BY homeworkDueDateTime";

        return $this->db()->select($sql, $data);
    }

    public function selectPlannerClassesByPerson($gibbonSchoolYearID, $gibbonPersonID)
    {
        $data = [
            'gibbonSchoolYearID' => $gibbonSchoolYearID,
            'gibbonPersonID' => $gibbonPersonID,
            'today' => date('Y-m-d'),
        ];
        $sql = "SELECT DISTINCT gibbonCourseClass.gibbonCourseClassID as value, CONCAT(gibbonCourse.nameShort, '.', gibbonCourseClass.nameShort) as name 
            FROM gibbonPlannerEntry 
            JOIN gibbonCourseClass ON (gibbonPlannerEntry.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID) 
            JOIN gibbonCourseClassPerson ON (gibbonCourseClass.gibbonCourseClassID=gibbonCourseClassPerson.gibbonCourseClassID) 
            JOIN gibbonCourse ON (gibbonCourse.gibbonCourseID=gibbonCourseClass.gibbonCourseID) 
            WHERE gibbonCourseClassPerson.gibbonPersonID=:gibbonPersonID 
            AND gibbonSchoolYearID=:gibbonSchoolYearID  
            AND NOT role='Student - Left' AND NOT role='Teacher - Left' 
            AND homework='Y' AND date<=:today 
            AND (gibbonCourseClassPerson.dateEnrolled IS NULL OR gibbonCourseClassPerson.dateEnrolled <= gibbonPlannerEntry.date)
            AND (gibbonCourseClassPerson.dateUnenrolled IS NULL OR gibbonCourseClassPerson.dateUnenrolled > gibbonPlannerEntry.date)
            ORDER BY name";

        return $this->db()->select($sql, $data);
    }
}
