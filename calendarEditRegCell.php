<?php
    define('IN_PROJECT', true);
    include 'security.php';

    checkSession();

    $queryRegId = $_GET['id'];
    $tableUid = uniqid('', TRUE);

    //  Grab data - use HTTP over HTTPS since infinityfree doesn't support https for free ..
    $req_url = 'http://' . $_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF']) . "/calendarDb.php?";
    $req_args = array(
        'op' => 'get',
        'registrationId' => $queryRegId
    );

    $originalInputValues = json_decode(file_get_contents($req_url . http_build_query($req_args)));
    if ( $originalInputValues->registrationId != $queryRegId ) {
        echo $req_url . http_build_query($req_args) . "<br>";
        echo "Calendar request failed.<br>";
        return;
    }
?>

<script>
    var allInputsList = [
        "flightInfo",
        "actYogaClass",
        "actJuiceDetox",
        "actMassage",
        "actBreathWork",
        "mealPreference",
    ];
</script>

<table id="regEditingTable" data-tableId="<?php echo $tableUid; ?>" width="320">
    <tr>
        <td><b>Flight</b><br>
            <input type="text" id="flightInfo">
        </td>
        <td rowspan="2">
            <b>Activities</b><br>
            <div style="white-space:nowrap">
                <input type="checkbox" id="actYogaClass" name="act_yoga"><label for="act_yoga">Yoga class</label><br>
                <input type="checkbox" id="actJuiceDetox" name="act_detox"><label for="act_detox">Juice Detox</label><br>
                <input type="checkbox" id="actMassage" name="act_massage"><label for="act_massage">Massage</label><br>
                <input type="checkbox" id="actBreathWork" name="act_breathwork"><label for="act_breathwork">Breath-work Session</label><br>
            </div>
        </td>
    </tr>
    <tr>
    <td><b>Meal Preference</b><br>
            <select id="mealPreference">
                <option value="Omnivore">Omnivore</option>
                <option value="Vegetarian">Vegetarian</option>
                <option value="Vegan">Vegan</option>
            </select>
        </td>
    </tr>
    <tr>
        <td colspan="2" align="right">
            <button id="calButtonUndo" class="calActionButton calActionButtonUndo" disabled onclick="regUndoInputs()" type="button">Undo</button>
            <button id="calButtonCancel" class="calActionButton calActionButtonCancel" onclick="regCancel()" type="button">Close</button>
            <button id="calButtonSave" class="calActionButton calActionButtonSave" onclick="regSave()" disabled type="button">Save</buton>
            </button>
        </td>
    </tr>
</table><br>
</body>

<script>    
    getEditorTable()["data-regOriginalValues"] = getOriginalInputValues();    
    regUndoInputs();
    for ( var curIdx in allInputsList ) {
        var element = FindElementById(getEditorTable(), allInputsList[curIdx], null);
        element.oninput = checkInputChanges;
        if ( element.type.toUpperCase() == "CHECKBOX" ) element.onclick = checkInputChanges;
    }

    function getEditorTable()
    {
        return FindAttributeByValue(document, "data-tableId", <?php echo '"' . $tableUid . '"'; ?>, "TABLE");
    }

    function getOriginalInputValues()
    {
        var originalInputValues = <?php echo json_encode($originalInputValues) ?>;
        return originalInputValues;
    }

    function setInputValue(element, value)
    {
        switch ( element.tagName.toUpperCase() ) {
            case "INPUT":
                switch ( element.type.toUpperCase() ) {
                    case "CHECKBOX":
                        if ( value !== undefined ) {
                            element.checked = value == '1';
                        } else {
                            element.checked = false;
                        }
                        break;

                    case "TEXT":
                        if ( value !== undefined ) {
                            element.value = value;
                        } else {
                            element.value = "";
                        }
                        break;

                    default:
                        console.log("Unsupported input type " + element.type);
                        break;
                }
                break;

            case "SELECT":
                if ( value !== undefined ) {
                    element.value = value;
                } else {
                    element.selectedIndex = 0;
                }
                break;

            default:
                console.log("Unsupported element type " + element.tagName);
                break;
        }
    }

    function hasInputValueChanged(element, value)
    {        
        if ( value === undefined ) return true;

        switch ( element.tagName.toUpperCase() ) {
            case "INPUT":
                switch ( element.type.toUpperCase() ) {
                    case "CHECKBOX":
                        var cmp = element.checked ? '1' : '0';
                        return cmp != value;

                    case "TEXT":
                        return element.value != value;

                    default:
                        console.log("Unsupported input type " + element.type);
                        break;
                }
                break;

            case "SELECT":
                return element.value != value;

            default:
                console.log("Unsupported element type " + element.tagName);
                break;
        }
    }

    function getInputValue(element)
    {        
        switch ( element.tagName.toUpperCase() ) {
            case "INPUT":
                switch ( element.type.toUpperCase() ) {
                    case "CHECKBOX":
                        return element.checked ? '1' : '0';

                    case "TEXT":
                        return element.value;

                    default:
                        console.log("Unsupported input type " + element.type);
                        break;
                }
                break;

            case "SELECT":
                return element.value;

            default:
                console.log("Unsupported element type " + element.tagName);
                break;
        }
    }

    function hasInputChanged()
    {
        var originalInputValues = getEditorTable()["data-regOriginalValues"];

        for ( var curIdx in allInputsList ) {
            var inputName = allInputsList[curIdx];
            var element = FindElementById(getEditorTable(), inputName, null);

            if ( hasInputValueChanged(element, originalInputValues[inputName]) ) return true;
        }

        return false;
    }

    function regUndoInputs()
    {
        var originalInputValues = getEditorTable()["data-regOriginalValues"];

        for ( var curIdx in allInputsList ) {
            var inputName = allInputsList[curIdx];
            var element = FindElementById(getEditorTable(), inputName, null);

            setInputValue(element, originalInputValues[inputName]);
        }

        checkInputChanges();
    }

    function checkInputChanges()
    {
        var undoButton = FindElementById(getEditorTable(), "calButtonUndo", null);
        var saveButton = FindElementById(getEditorTable(), "calButtonSave", null);
        if ( hasInputChanged() ) {
            undoButton.removeAttribute("disabled");
            saveButton.removeAttribute("disabled");
            saveButton.innerHTML="Save";
            saveButton.classList.remove("calActionButtonError");
        } else {
            undoButton.setAttribute("disabled", "");
            saveButton.setAttribute("disabled", "");
        }
    }

    function regSave()
    {
        var originalInputValues = getEditorTable()["data-regOriginalValues"];
        var newValuesList = {};
        for ( var curIdx in allInputsList ) {
            var inputName = allInputsList[curIdx];
            var element = FindElementById(getEditorTable(), inputName, null);

            if ( hasInputValueChanged(element, originalInputValues[inputName]) ) {
                newValuesList[inputName] = getInputValue(element);
            }
        }

        var url = "calendarDb.php?op=update&registrationId=" + encodeURI("<?php echo $queryRegId ?>");
        for ( var key in newValuesList ) {
            url = url + "&" + key + "=" + encodeURIComponent(newValuesList[key]);
        }

        updateHTTPRequest = new XMLHttpRequest();
        updateHTTPRequest.open('GET', url, true);

        var saveButton = FindElementById(getEditorTable(), "calButtonSave", null);
        saveButton.classList.remove("calActionButtonError");
        saveButton.innerHTML="Saving ...";
        saveButton.setAttribute("disabled", "");

        updateHTTPRequest.onreadystatechange= function() {
            if (this.readyState !== 4) return;
            if (this.status !== 200) {
                saveButton.innerHTML="Error";
                saveButton.classList.add("calActionButtonError");
                saveButton.removeAttribute("disabled");
                return;
            }

            //  Successful update
            for ( var curKey in newValuesList ) {
                originalInputValues[curKey] = newValuesList[curKey];
            }

            saveButton.innerHTML="Saved";
            checkInputChanges();
        };
        updateHTTPRequest.send();
    }

    function regCancel()
    {
        releaseEditingRegistration();   //  defined in bookingCalendar.js
    }
</script>