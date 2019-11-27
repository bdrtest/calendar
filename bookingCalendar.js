function findParentOfTypeAndId(element, element_type, element_id)
{
    var cur = element;

    while ( cur != null ) {
        if ( cur.nodeName == element_type && (cur.id == element_id || element_id == null) )
        {
            return cur;
        }

        cur = cur.parentElement;
    }

    return null;
}

function FindAttributes(element, attribute, element_type)    {
    element_type = element_type || "*";
    var All = element.getElementsByTagName(element_type);

    var ret = [];
    for (var i = 0; i < All.length; i++) {
      if (All[i].getAttribute(attribute) != null) { 
          ret.push(All[i]);
      }
    }

    return ret;
  }
  
  function FindAttributeByValue(element, attribute, value, element_type)    {
    element_type = element_type || "*";
    var All = element.getElementsByTagName(element_type);

    for (var i = 0; i < All.length; i++) {
      if (All[i].getAttribute(attribute) == value) { 
          return All[i];
      }
    }

    return null;
  }
  
  function FindElementById(element, id, element_type)    {
    element_type = element_type || "*";
    var All = element.getElementsByTagName(element_type);

    var ret = [];
    for (var i = 0; i < All.length; i++) {
      if (All[i].id == id ) { 
          return All[i];
      }
    }

    return null;
  }
  
function setNextMonthButton(element, month, year, addMonths, t)
{
    month = parseInt(month);
    year = parseInt(year);
    addMonths = parseInt(addMonths);

    month += addMonths;
    if ( month <= 0 ) {
        year --;
        month += 12;
    }
    if ( month > 12 ) {
        year ++;
        month -= 12;
    }

    element.setAttribute("href", "bookingCalendar.php?m=" + month + "&y=" + year + "&t=" + t);
}

var capturedTip = null;
var capturedEditingRegistration = null;
var ongoingHTTPRequest = null;

function cancelExistingHTTPRequest()
{
    if ( ongoingHTTPRequest == null ) return;

    ongoingHTTPRequest = null;
}

function releaseEditingRegistration()
{
    if ( capturedEditingRegistration == null ) return;

    //  Cancel any outstanding HTTP request
    cancelExistingHTTPRequest();
    capturedEditingRegistration.innerHTML = null;
    capturedEditingRegistration.style.display = "none";
    capturedEditingRegistration = null;
}

function editRegInfo(element, event, registrationID)
{
    var editCell = findParentOfTypeAndId(element, "TD", "calendarDayCell");
    var registrationRow = findParentOfTypeAndId(element, "TR", "registrationRow");

    //  If the user has somehow managed to click the edit button without capturing the cell, capture it
    if ( capturedTip == null ) {
        captureCalendarCell(editCell, null);
    }

    releaseEditingRegistration();

    registrationId = registrationRow.getAttribute("data-registration-id");

    //  Find the properties table
    var regPropertiesTable = FindElementById(registrationRow, "editRegInfo", "DIV");    
    regPropertiesTable.style.display = "inline";
    regPropertiesTable.innerHTML = "Loading ...";

    //  Load up the properties editing table into the popover
    cancelExistingHTTPRequest();
    ongoingHTTPRequest = new XMLHttpRequest();
    ongoingHTTPRequest.open('GET', 'calendarEditRegCell.php?id=' + encodeURI(registrationId), true);
    ongoingHTTPRequest.onreadystatechange= function() {
        if (this.readyState !== 4) return;
        if (this.status !== 200) {
            regPropertiesTable.innerHTML = "Error editing registration";
            ongoingHTTPRequest = null;
            return;
        }
        regPropertiesTable.innerHTML = this.responseText;

        var scriptObjects = regPropertiesTable.getElementsByTagName('script');

        //  Terrible hack to get scripts to load ..
        for ( i = 0; i < scriptObjects.length; i ++ ) {
            var g = document.createElement('script');
            g.text = scriptObjects[i].text;
            var s = document.getElementsByTagName('script')[0];
            s.parentNode.insertBefore(g, s);
        }

        ongoingHTTPRequest = null;
    };
    ongoingHTTPRequest.send();
    
    capturedEditingRegistration = regPropertiesTable;

    //  Prevent the popover from being dismissed
    event.stopPropagation();
}

function releaseCapturedTip()
{
    if ( capturedTip == null ) {
        return;
    }

    releaseEditingRegistration();
}

function captureCalendarCell(cell, event)
{
    //  Make sure that the use clicked on the actual cell
    if ( event != null ) {
        var cursorElement = document.elementFromPoint(event.clientX, event.clientY);
        if ( cursorElement !== cell ) return;
    }

    var tooltipBox = FindElementById(cell, "tooltiptext", "table");

    if ( capturedTip != null ) {
        releaseCapturedTip();

        if ( capturedTip !== tooltipBox ) {
            capturedTip.style.visibility = "hidden";

            var capturedCell = findParentOfTypeAndId(capturedTip, 'TD', 'calendarDayCell');        
            capturedCell.style.outline = "";    
        } else {
            cell.style.outline = "#000000 dashed 2px";
            capturedTip.style.visibility = "hidden";
            capturedTip = null;
            return;
        }
    }
    capturedTip = tooltipBox;

    tooltipBox.style.visibility = "visible";
    cell.style.outline = "#000000 solid 2px";
}

function mouseOverCalendarCell(cell)
{
    var tooltipBox = FindElementById(cell, "tooltiptext", "table");

    if ( capturedTip != null ) {
        if ( tooltipBox !== capturedTip ) {
            cell.style.outline = "#000000 dashed 2px";
        }
        return;
    }

    tooltipBox.style.visibility = "visible";
    cell.style.outline = "#000000 dashed 2px";
}

function mouseOutCalendarCell(cell)
{
    var tooltipBox = FindElementById(cell, "tooltiptext", "table");

    if ( capturedTip != null ) {
        if ( tooltipBox !== capturedTip ) {
            cell.style.outline = "";
        }
        return;
    }
    
    tooltipBox.style.visibility = "hidden";
    cell.style.outline = "";
}

function showPending(checkbox)
{
    var parentTable = findParentOfTypeAndId(checkbox, 'TABLE', 'eventcalendar');
    var pendingCells = FindAttributes(parentTable, 'data-pending', 'td');
    var highlightCells = FindAttributes(parentTable, 'data-highlight', 'td');
    if ( checkbox.checked ) {
        highlightCells.forEach(function(cell) {
            cell.setAttribute("class", "dayCellNormal");
        });
        pendingCells.forEach(function(cell) {
            cell.setAttribute("class", "dayCellPending");
        });

        t = 'p';
    } else {
        pendingCells.forEach(function(cell) {
            cell.setAttribute("class", "dayCellNormal");
        });
        highlightCells.forEach(function(cell) {
            cell.setAttribute("class", "dayCellHighlighted");
        });

        t = '';
    }

    //  Update last/next month action buttons
    var lastMonth = FindElementById(parentTable, "lastmonth", "a");
    var nextMonth = FindElementById(parentTable, "nextmonth", "a");

    setNextMonthButton(lastMonth, m, y, -1, t);
    setNextMonthButton(nextMonth, m, y, 1, t);
}
