<?php
    //get onid parameter
    $onidID = $_GET["onidID"];

    require_once '../dbconfig.php';
    require_once '../dbquery.php';
    
    $mysqli = new mysqli($dbhost, $dbuser, $dbpass, $dbname);
    if ($mysqli->connect_errno) {
          echo "Failed to connect to MySQL: (" . $mysqli->connect_errno . ") " . $mysqli->connect_error;
          exit;
      } 

    //Find userID based off of onid
    $data = lookupUser($mysqli, $onidID);
    $user = json_decode($data);

    // Output: if any are found, then a 2D associative array containing event info with
    //         the first dimension being row number of result, else NULL.
    //       2nd dim array keys: id, title, dateStartTime
    $eventsCreatedByUser = eventCreateHist($mysqli, $onidID);
    
    $userEvents = array();
    foreach ($eventsCreatedByUser as $idx => $res) {
        $eventItem = array();
        $eventItem["id"] = $res["id"];
        $eventItem["title"] = $res["title"];
        $eventItem["description"] = $res["description"];
        $eventItem["start"] = $res["dateStart"];
        $eventItem["end"] = $res["dateEnd"];
        $eventItem["allDay"] = true;
        //$eventItem["duration"] = $res["duration"];
        //$eventItem["RSVPslotLim"] = $res["RSVPslotLim"];
        array_push($userEvents, $eventItem);
    }

    echo json_encode($userEvents);
    $mysqli->close();
?>
