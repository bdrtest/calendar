<html>
    <head>
        <link rel="stylesheet" href="SwitchesAndButtons.css"/>
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css"/>

        <script src="bookingCalendar.js"></script>
    </head>

    <style>
        table#eventcalendar {
            border: 1px solid black ;
            border-collapse: collapse ;
        }
        tr#infostatus {
            text-align: center ;
            border: 1px solid black ;
        }
        tr#wkdaynames td {
            width: 14% ;
            text-align: center ;
        }
        td#lastmonth {
            border: 1px solid black ;
            text-align: center ;
        }
        td#monthdisplay {
            text-align: right ;
            font-weight: bold ;
            font-size: 25 ;
        }
        td#nextmonth {
            border: 1px solid black ;
            text-align: center ;
        }

        tr#weekrow {
            border: 1px solid black ;
        }
        tr#weekrow > td {
            border: 1px solid black ;
            text-align: center ;
        }

        td.dayCellBlank {                        
        }
        td.dayCellNormal { 
            cursor: default;                       
        }
        td.dayCellHighlighted {
            background-Color: #007FFF;
            color: #FFFFFF;
            font-weight: bold;
            cursor: default;
        }
        td.dayCellPending  {
            background-Color: #007F00;
            color: #FFFFFF;
            font-weight: bold;
            cursor: default;                       
        }

        .editButton {
            cursor: pointer;
        }

        div#codedump {
            white-space: pre-wrap ;
        }

        table.registrationsTable {
            border-collapse: collapse;
        }
        table.registrationsTable tr {
            border-bottom: solid thin;
        }
        table.registrationsTable tr:last-child { 
            border-bottom: none; 
        }
    </style>

    <body style="zoom: 150%">

    <?php
        define('IN_PROJECT', true);
        include 'calendarUtils.php';
        include 'security.php';

        checkSession();

        $valid = true;
        $queryMonth = intval($_GET['m']);
        $queryYear = intval($_GET['y']);

        if ( $queryMonth == '' ) {
            echo 'Error: Missing month parameter "m" in calendar' . '<br>';
            $valid = false;
        }
        if ( $queryYear == '' ) {
            echo 'Error: Missing year parameter "y" in calendar' . '<br>';
            $valid = false;
        }
        if ( $queryMonth < 1 || $queryMonth > 12 ) {
            echo 'Error: Invalid month value m="' . $queryMonth . '" in calendar' . '<br>';
            $valid = false;
        }
        if ( $queryYear < 1 || $queryYear > 3000 ) {
            //  Note to maintainer: not Y3K compliant
            echo 'Error: Invalid year value y="' . $queryYear . '" in calendar' . '<br>';
            $valid = false;
        }
        if ( $_GET['t'] == 'p' ) {
            $queryShowType = 'p';
        }

        if ( !$valid ) return;

        $registrations = loadRegistrations($queryMonth, $queryYear);

        function exportVarsToJS()
        {
            global $queryShowType, $queryMonth, $queryYear;

            $vars = array(
                "t" => $queryShowType,
                "m" => $queryMonth,
                "y" => $queryYear);
            foreach($vars as $key => $value ) {
                echo 'var ' . $key . ' = ' . '"' . $value . '";';
            }
        }

        function loadRegistrations($month, $year)
        {
            $limit = 100;
            global $retreatGuruToken;

            $req_url = "https://demo14.secure.retreat.guru/api/v1/registrations?";
            $req_args = array(
                'token' => $retreatGuruToken,
                'limit' => '100',   /* Arbitrary limit */
                /*
                    Doesn't seem to work in API?
                'min_date' => $year . "-" . $month . "-01",
                'max_date' => $year . "-" . ($month + 1) . "-01"
                */
            );
    
            $ret = file_get_contents($req_url . http_build_query($req_args));
            return json_decode($ret);
            //return json_decode(file_get_contents("localdata.json"));
        }

        function shouldHighlightDay($dayRegistrations)
        {
            foreach($dayRegistrations as &$curReg) {
                if ( $curReg->room == "Room 5" ) {
                    return true;
                }
            }

            return false;
        }

        function hasPendingRegistrations($dayRegistrations)
        {            
            foreach($dayRegistrations as &$curReg) {
                if ( $curReg->status == "pending" ) {
                    return true;
                }
            }

            return false;
        }

        function genDayCellHover($month, $day, $dayRegistrations)
        {
            $ret = null;
            if ( count($dayRegistrations) > 0 ) {
                $ret = '<div id="tooltip" class="tooltip"><br>';
                $ret .= '<table id="tooltiptext" class="tooltiptext"><tr><td>';

                $ret .= '<table class="registrationsTable">';
                foreach($dayRegistrations as &$curReg) {
                    $ret .= '<tr border="1px" id="registrationRow" data-registration-id="' . $curReg->id . '">';

                    $ret .= '<td nowrap>';
                    $ret .= '<font size="4"><b>' . $curReg->full_name . '</b></font><br>';
                    $ret .= '<b><font size="2">' . $curReg->room . '</font></b><br>';
                    $ret .= '<font size="2"><i>' . date_create($curReg->start_date)->format('D M jS') . '</i> to <i>'
                             . date_create($curReg->end_date)->format('D M jS') . '</i></font><br>';

                    if ( $curReg->status == "reserved" ) {
                        $ret .= '<b><font size="2" color="#007FFF">Reserved</font></b>';
                    } else if ( $curReg->status == "pending" ) {
                        $ret .= '<b><font size="2" color="#007F00">Pending</b></font></b>';
                    } else {
                        $ret .= '<b><font size="2" color="red">' . $curReg->status . '</font></b>';
                    }
                    $ret .= '<td><button onclick="editRegInfo(this, event);" class="fa fa-pencil editButton"></button></td>';

                    $ret .= '<td nowrap><div style="display:none;" id="editRegInfo"></div></td>';

                    $ret .= '</tr>';
                }

                $ret .= '</table>';

                $ret .= '</tr></td></table>';
                $ret .= '</div>';
            }

            return $ret;
        }

        function emitFilterChecked()
        {
            global $queryShowType;

            if ( $queryShowType == 'p' ) {
                echo 'checked="true"';
            }
        }

        function emitNextMonth($month, $year, $addMonths, $id)
        {
            global $queryShowType;

            $month += $addMonths;
            if ( $month <= 0 ) {
                $year --;
                $month += 12;
            }
            if ( $month > 12 ) {
                $year ++;
                $month -= 12;
            }

            echo '<a id="'. $id . '" href="bookingCalendar.php?m=' . $month . '&y=' . $year . '&t=' . $queryShowType . '">';
        }

        function emitCalendarTitle($month, $year)
        {
            $time = mktime(0, 0, 0, $month, 1, $year);
            echo date('F', $time) . ' ' . $year;
        }

        function emitDayCell($month, $day, $dayRegistrations)
        {
            global $queryShowType;

            $cellAttributes = array();
            $cellAttributes['class'] = 'dayCellEmpty';
            $cellAttributes['id'] = 'calendarDayCell';
            $cellContent = '&nbsp;';
            if ( $day != 0 ) {

                $cellAttributes['class'] = 'dayCellNormal';
                if ( shouldHighlightDay($dayRegistrations) ) {
                    $cellAttributes['data-highlight'] = '1';
                }
                if ( hasPendingRegistrations($dayRegistrations) ) {
                    $cellAttributes['data-pending'] = '1';
                }

                if ( $queryShowType == 'p' ) {
                    if ( $cellAttributes['data-pending'] == 1 ) {
                        $cellAttributes['class'] = 'dayCellPending';
                    }
                } else {
                    if ( $cellAttributes['data-highlight'] == 1 ) {
                        $cellAttributes['class'] = 'dayCellHighlighted';
                    }
                }

                $cellContent = $day;
                $hoverText = genDayCellHover($month, $day, $dayRegistrations);
                if ( $hoverText != null ) {
                    $cellAttributes['onmouseover'] = "mouseOverCalendarCell(this)";
                    $cellAttributes['onmouseout'] = "mouseOutCalendarCell(this)";
                    $cellAttributes['onclick'] = "captureCalendarCell(this, event)";
                }
            }

            echo '<td ' . implodeAttributeList($cellAttributes) . '">' .$cellContent . $hoverText . '</td>';
        }

        function emitNumAvailableDays()
        {
            global $numAvailableDays;
            echo $numAvailableDays;
        }

        function emitCalendarCells($month, $year)
        {
            global $registrations;
            global $numAvailableDays;            

            $daysInMonth = cal_days_in_month(CAL_GREGORIAN, $month, $year);

            $startOfmonth = new DateTime();
            $startOfmonth->setDate($year, $month, 1);

            $endOfMonth = new DateTime();
            $endOfMonth->setDate($year, $month, $daysInMonth);

            //  Associate registrations with each day they fall on
            $regsByDay = array(31);
            for ( $i = 0; $i < 31; $i ++ ) $regsByDay[$i] = array();

            foreach($registrations as &$curReg) {
                $startTime = date_create($curReg->start_date);
                $endTime = date_create($curReg->end_date);
                $fullName = $curReg->full_name;

                //  Clip start and end of stay to within this month
                $startDay = $startTime;
                if ( $startDay < $startOfmonth ) $startDay = $startOfmonth;
                $endDay = $endTime;
                if ( $endDay > $endOfMonth ) $endDay = $endOfMonth;

                //  Loop through the days this guest is staying and associate it in regsByDay
                if ( $startDay < $endDay ) {
                    for ( $curDay = $startDay->format('d'); $curDay <= $endDay->format('d'); $curDay ++ ) {
                        array_push($regsByDay[intval($curDay) - 1], $curReg);
                    }
                }
            }

            $numAvailableDays = 0;

            //  Current index of cell being emitted (top left=0)
            $curCell = 0;

            //  Calculate which cell indexes the first and last days of the month should fall upon
            $calendarStartCell = $startOfmonth->format('w');
            $calendarEndCell = $calendarStartCell + $daysInMonth;          

            //  Generate 6x7 calendar cells
            for ( $curRow = 0; $curRow < 6; $curRow ++ ) {
                echo '<tr id="weekRow">';
                for ( $curCol = 0; $curCol < 7; $curCol ++ ) {
                    $dom = 0;
                    $dayRegistrations = null;

                    //  Does this cell contain an an actual day?
                    if ( $curCell >= $calendarStartCell && $curCell < $calendarEndCell ) {
                        $dom = $curCell - $calendarStartCell + 1;   //  day of month for this cell
                        $dayRegistrations = $regsByDay[intval($dom - 1)];   //  registrations on this day

                        //  If there are no registrations for this day, it's counted as an available day
                        if ( count($dayRegistrations) == 0 ) $numAvailableDays ++;
                    }

                    emitDayCell($month, $dom, $dayRegistrations);
                    $curCell ++;
                }
                echo '</tr>';
            }
        }
    ?>

        <script><?php exportVarsToJS(); ?></script>

        <!-- Generate the calendar statically so users without JS-enabled browsers can at least see the availablity -->
        <table id="eventcalendar" width="480">
            <thead>
                <tr id="infostatus">
                    <td colspan="7">
                        <table width="100%">
                            <tr>
                                <td width="30" id="lastmonth"><?php emitNextMonth($queryMonth, $queryYear, -1, "lastmonth");?>&lt;</a></td>
                                <td width="60"><label class="switch"><input type="checkbox" <?php emitFilterChecked(); ?> onclick="showPending(this)"><span class="slider"></span></label></td>
                                <td>Show all pending<br/>registrations</td>
                                <td id="monthdisplay"><?php emitCalendarTitle($queryMonth, $queryYear); ?></td>
                                <td width="30" id="nextmonth"><?php emitNextMonth($queryMonth, $queryYear, 1, "nextmonth");?>&gt;</a></td>
                            </tr>
                        </table>
                    </td>
                </tr>
            </thead>
            <tr id="wkdaynames">
                <td>Sun</td><td>Mon</td><td>Tue</td><td>Wed</td><td>Thurs</td><td>Fri</td><td>Sat</td>
            </tr>
            <?php emitCalendarCells($queryMonth, $queryYear); ?>
            <tr id="numAvailableDays">
                <td colspan="7"><?php emitNumAvailableDays(); ?> available days</td>
            </tr>
        </table>
    </body>
</html>