<?php
    define('IN_PROJECT', true);
    include 'calendarUtils.php';
    include 'security.php';

    header('Content-Type: application/json');

    checkSession();

    $operation = $_GET['op'];
    if ( $operation == 'get' ) {
        $regId = $_GET['registrationId'];
        if ( $regId == null || $regId == '' || strlen($regId) > 32 ) die("Invalid registration id");

        echo getRegistrationId($regId);
    } else if ( $operation == 'update' ) {
        $regId = $_GET['registrationId'];
        if ( $regId == null || $regId == '' || strlen($regId) > 32 ) die("Invalid registration id");

        echo setRegistration($regId, $_GET);
    } else {
        exit("Invalid operation");
    }

    function dbConnect()
    {
        global $db;
        global $dbservername;
        global $dbusername;
        global $dbpassword;
        global $dbname;

        try {
            $db = new PDO("mysql:host=" . $dbservername . ";dbname=" . $dbname, $dbusername, $dbpassword);
        } catch (PDOException $e){
            exit("Failed to connect to database " . $e->getMessage());
        }
    }

    class DBUpdateBuilder
    {
        public $updateString = '';
        public $fieldList = '';
        public $valuesList = '';
        public $sqlParams = [];

        //  Pulls out any values from $kvList that are in $validColumns and
        //  processes them into INSERT/UPDATE parameters
        function processValidInputs($validColumns, $kvList)
        {
            $keys = array_keys($validColumns);
            $this->updateString = '';

            foreach ( $validColumns as &$key ) {
                if ( array_key_exists($key, $kvList ) ) {                
                    $sqlParamName = ':' . $key;

                    if ( $this->updateString != '' ) $this->updateString .= ',';
                    $this->updateString .= $key . '=' . $sqlParamName;
                    $this->sqlParams[$sqlParamName] = $kvList[$key];
                }
            }

            $this->fieldList = '';
            $this->valuesList = '';
            foreach ( array_keys($this->sqlParams) as &$key ) {
                $fieldName = ltrim($key, ':');

                if ( $this->fieldList != '' ) $this->fieldList .= ',';
                $this->fieldList .= $fieldName;
                if ( $this->valuesList != '' ) $this->valuesList .= ',';
                $this->valuesList .= $key;
            }


        }
    }

    function setRegistration($regId, $values)
    {
        global $db;

        dbConnect();

        $table = 'ExtraRegInfo';

        //  List of columns that are permitted to be set/updated
        $validUpdateColumns = [ 'flightInfo', 
                     'mealPreference',
                     'actYogaClass',
                     'actJuiceDetox',
                     'actMassage',
                     'actBreathWork' ];
        
        $updateQuery = new DBUpdateBuilder();
        $updateQuery->sqlParams[':registrationId'] = $regId;
        $updateQuery->processValidInputs($validUpdateColumns, $values);

        $sqlQueryString = 'INSERT INTO ' . $table . 
            ' (' . $updateQuery->fieldList . ') VALUES (' . $updateQuery->valuesList . ')' . 
            ' ON DUPLICATE KEY UPDATE ' . $updateQuery->updateString;

        $sqlStatement = $db->prepare($sqlQueryString);
        $result = $sqlStatement->execute($updateQuery->sqlParams);
        echo $result;
    }

    function getRegistrationId($regId)
    {
        global $db;

        dbConnect();

        $table = 'ExtraRegInfo';
        $columns = [ 'registrationId',
                     'flightInfo',
                     'mealPreference',
                     'actYogaClass',
                     'actJuiceDetox',
                     'actMassage',
                     'actBreathWork' ];

        $sqlParams = [
            ':registrationId' => $regId
        ];
        $sqlGet = $db->prepare('SELECT ' . implode($columns, ',') . ' FROM ' . $table . ' WHERE registrationId = :registrationId');
        $sqlGet->execute($sqlParams);
        $result = $sqlGet->fetch(PDO::FETCH_OBJ);
        if ( $result == false ) {
            $result = ['registrationId' => $regId];
        }

        echo json_encode($result);
    }

?>