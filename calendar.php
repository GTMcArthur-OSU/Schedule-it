<?php
    include 'file_path.php';
    session_start();
    //check once again if the user is logged in
    //if not, redirect back to login page

    if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] == FALSE) {
        session_destroy();
        session_unset();
        $_SESSION = array();
        //header("Location: " . $FILE_PATH . "login.php");
        echo "<script type='text/javascript'> document.location = '" . $FILE_PATH . "login.php'; </script>";
    }
       
    //TODO: Retrieve the upcoming events and meetings, and reserved meetings 
    //of the user to populate the calendar
    require_once './database/dbconfig.php';
    require_once './database/dbquery.php';
    
    $mysqli = new mysqli($dbhost, $dbuser, $dbpass, $dbname);
    if ($mysqli->connect_errno) {
          echo "Failed to connect to MySQL: (" . $mysqli->connect_errno . ") " . $mysqli->connect_error;
          exit;
      } 

    //var_dump($_SESSION);
    //Find userID based off of onid
     $data = lookupUser($mysqli, $_SESSION["onidID"]);
     $user = json_decode($data);
     //echo "USER: " . $user->id;

    // Output: if any are found, then a 2D associative array containing event info with
    //         the first dimension being row number of result, else NULL.
    //       2nd dim array keys: id, title, dateStartTime
    $eventsCreatedByUser = eventCreateHist($mysqli, $user->id);
    
    // Output: if any are found, then a 2D associative array containing event info with
    //         the first dimension being row number of result, else NULL.
    //       2nd dim array keys: eventID, inviteID, title, dateStartTime, firstName, lastName
    $eventsYetToReserve = invitesUpcoming($mysqli, $user->id);

    // Output: if any are found, then a 2D associative array containing slot info with
    //         the first dimension being row number of result, else NULL.
    //    2nd dim array keys: eventID, inviteID, slotID, title, dateStartTime, startTime, duration, location, endTime
    $reservationsMadeByUser = reservedSlotHist($mysqli, $user->id);
    
    $reservations = array();
    foreach ($reservationsMadeByUser as $idx => $res) {
        $eventItem = array();
        $slotID = $res["slotID"];
        $inviteID = $res["inviteID"];
        $eventItem["id"] = $slotID;
        $eventItem["eventID"] = $res["eventID"];
        $eventItem["title"] = $res["title"];
        $date = $res["dateStart"];
        $eventItem["start"] = $date."T".$res["startTime"];
        $eventItem["end"] = $date."T".$res["endTime"];
        $eventItem["url"] = "view_reservation?slot=$slotID&inviteID=$inviteID";
        array_push($reservations, $eventItem);
    }

    $mysqli->close();

?>

<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">

  <title>Schedule-It!</title>

  <script type="text/javascript">
      <?php 
            echo "var onidID = ".$user->id.";"
      ?>
  </script>
  
  <!--Bootstrap core CSS-->
  <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.4.1/css/bootstrap.min.css" integrity="sha384-Vkoo8x4CGsO3+Hhxv8T/Q5PaXtkKtu6ug5TOeNV6gBiFeWPGFN9MuhOf23Q9Ifjh" crossorigin="anonymous">
  <!--Customized css-->
  <link rel="stylesheet" href="./assets/css/main.css" type="text/css">

  <!--NEEDED FOR DIALOG-FORM DISPLAY -->
  <link rel="stylesheet" href="//code.jquery.com/ui/1.12.1/themes/base/jquery-ui.css">
  <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.2.1/jquery.min.js"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/jqueryui/1.12.1/jquery-ui.min.js"></script>
  
  <!-- javascript files -->
  <script src="./assets/js/main.js"></script> 
  <!-- <script src="./assets/js/homepage.js"></script> -->
  <script src="./assets/js/event.js"></script> 

  <!--fullcalendar-->
  <!--Use daygrid-views for homepage -->
  <!--Use Selectable for event creation page-->
  <!--Source  fullcalendar.io-->
  <link href='./assets/js/fullcalendar/packages/core/main.css' rel='stylesheet' />
  <link href='./assets/js/fullcalendar/packages/daygrid/main.css' rel='stylesheet' />
  <link href='./assets/js/fullcalendar/packages/list/main.css' rel='stylesheet' />
  <link href='./assets/js/fullcalendar/packages/timegrid/main.css' rel='stylesheet' />
 <!--NEEDED FOR DIALOG-FORM DISPLAY -->
  <style>

    label, input { display:block; }
    input.text { margin-bottom:12px; width:95%; padding: .4em; }
    fieldset { padding:0; border:0; margin-top:25px; }
    h1 { font-size: 1.2em; margin: .6em 0; }
    div#users-contain { width: 350px; margin: 20px 0; }
    div#users-contain table { margin: 1em 0; border-collapse: collapse; width: 100%; }
    div#users-contain table td, div#users-contain table th { border: 1px solid #eee; padding: .6em 10px; text-align: left; }
    .ui-dialog .ui-state-error { padding: .3em; }
    .validateTips { border: 1px solid transparent; padding: 0.3em; }
    #live_data .ui-dialog {
     width: 100%;
     padding: 0; }

     .tooltip {
        position: relative;
        display: inline-block;
        border-bottom: 1px dotted black;
      }

      .tooltip .tooltiptext {
        visibility: hidden;
        width: 120px;
        background-color: black;
        color: #fff;
        text-align: center;
        border-radius: 6px;
        padding: 5px 0;

        /* Position the tooltip */
        position: absolute;
        z-index: 1;
      }

      .tooltip:hover .tooltiptext {
        visibility: visible;
      }

      .hidden>div {
        display:none;
      }

      .visible>div {
        display:block;
      }

</style>

  <script src='./assets/js/fullcalendar/packages/core/main.js'></script>
  <script src='./assets/js/fullcalendar/packages/daygrid/main.js'></script>
  <script src='./assets/js/fullcalendar/packages/list/main.js'></script>
  <script src='./assets/js/fullcalendar/packages/interaction/main.js'></script>
  <script src='./assets/js/fullcalendar/packages/timegrid/main.js'></script>
  <script src='./assets/js/fullcalendar/packages/moment/main.js'></script>
</head>
<body>
  <!-- HEADER CODE FROM OSU WEBSITE TO DEVELOP COHESIVE LOOK -->
    <div class="header-container">
        <header role="banner" class="osu-top-hat">
            <a href="https://oregonstate.edu" title="Schedule-It Home" class="logo">
              <img src="https://oregonstate.edu/themes/osu/drupal8-osuhomepage/logo.svg" alt="Oregon State University" />
            </a>
            <nav role="navigation" id="block-homepage-main-menu" class="d-none d-lg-block">
              <ul class="main-menu nav nav-pills">
                <li class="nav-item">
                  <a href="homepage.php" class="nav-link">Schedule-It Home</a>
                </li>
                <li class="nav-item">
                  <a href="calendar.php" class="nav-link">Calendar</a>
                </li>
                <li class="nav-item">
                  <a href="eventmanagement.php" class="nav-link">Manage Events</a>
                </li>
                <li class="nav-item">
                  <a href="view_history.php" class="nav-link">Past Meetings</a>
                </li>
                <!-- Temporary spacing fix -->
                　　　　　　　　　　　　　　　　　　　　　　　
                <li class="nav-item">
                  <a href="logout.php" class="nav-link">Logout</a>
                </li>
              </ul>
            </nav>
        </header>
    </div><p>

    <!-- Passing variables from php to javascript to populate events and reservations -->
    <script type="text/javascript"> 
        let reservations = <?php echo json_encode($reservations) ?>; 
    </script>
    
    <!-- div for Events the user still need to make reservations for -->
    <div class="text_container">
        <ul id="upcomingEvents">
            <?php 
                foreach ($eventsYetToReserve as $idx => $event) {
                    $eventID = $event["eventID"];
                    $inviteID = $event["inviteID"];
                    $eventTitle = $event["title"];
                    $eventStartDate = $event["dateStart"];
                    $upcomingEvents = $eventStartDate;
                    $eventCreator = $event["firstName"]." ".$event["lastName"];
                    $li = "<a href=\"make_reservation?invite=$inviteID\" class=\"list-group-item list-group-item-action\" id=inviteID>Please RSVP to $eventTitle, starting on $eventStartDate, created by $eventCreator</a>";
                    echo $li;
                }
            ?>
        </ul>
    </div>

    <!-- div for Calendar-->
    <div class="container-fluid">
            <center><i>To create an event, while in <b>"Calendar View"</b>, click anywhere on any date in calendar month-view, week-view, or day-view and a <b>pop-up</b> will appear to create a new event/meeting. 
          </i></p><button type="button" class="btn btn-large" onclick="showList(event)" id="listButton">List View</button>
            <button type="button" class="btn btn-large" onclick="showCalendar(event)" id="calendarButton">Calendar View</button><br>
            </center>
    </div>
    <div class="container-fluid" id="content">
    </div>
    
<div id="dialog-form" style="display:none;" title="Create new event">
   <p class="validateTips">All form fields are required.</p>

<form>
  <fieldset>
      <input type="hidden" id="date" name="date">
      <label for="title">Event title: </label>
      <input type="text" name="title" id="title" class="text ui-widget-content ui-corner-all" required>

      <label for="description">Description: </label>
      <input type="text" name="description" id="description" class="text ui-widget-content ui-corner-all">

      <label for="location">Location:  </label>
      <input type="text" name="location" id="location" class="text ui-widget-content ui-corner-all">  

      <label for="dateStart">Start Date: </label>
          <input type="date" name="dateStart" id="dateStart" class="text ui-widget-content ui-corner-all" required>

      <label for="dateEnd">End Date: </label>
          <input type="date" name="dateEnd" id="dateEnd" class="text ui-widget-content ui-corner-all" required>
<!--
      <label for="slots">How many time slots? </label>
          <input type="number" name="slots" id="slots" class="text ui-widget-content ui-corner-all" min="1">

      <label for="RSVPLim">Max attendees per slot: </label>
          <input type="number" name="RSVPLim" id="RSVPLim" class="text ui-widget-content ui-corner-all" min="0">  

      <label for="RSVPslotLim">Max Reservations per attendee: </label>
          <input type="number" name="RSVPslotLim" id="RSVPslotLim" class="text ui-widget-content ui-corner-all" min="0">  
-->
      <!--THIS IS CREATOR_ID -- SHOULD GET FROM SESSION -->
      <input type="hidden" name="creatorID" id="creatorID" value="<?php echo $user->id;?>" />   

      <!-- Allow form submission with keyboard without duplicating the dialog button -->
      <input type="submit" id="signupbtn">
    </fieldset>
  </form>
</div>


<!-- FORM FOR EDIT AND DELETE BUTTONS -->
<div id="edit-delete" style="display:none;" title="Edit or Delete">
<form>
  <fieldset>
      <input type="hidden" id="date" name="date" value="">
      <input type="hidden" name="creatorID" id="creatorID" value="<?php echo $user->id;?>" />   <!--THIS IS CREATOR_ID -- SHOULD GET FROM SESSION -->
      <!-- Allow form submission with keyboard without duplicating the dialog button -->
      <button type="button" id="sendEmail">Send Emails</button>
      <button type="button" id="editbtn">Edit</button>
      <button type="button" id="deletebtn">Delete</button>
    </fieldset>
  </form>
</div>

<!-- FORM FOR EDIT EVENT -->
<div id="edit-form" style="display:none;" title="Edit Current Event">
   <p class="validateTips">All form fields are required.</p>
      <button type="button" id="edit-slotbtn">Edit Slots</button>

<form>
  <fieldset>
      <input type="hidden" id="dateedit" name="dateedit" value="">
      <label for="titleedit">Event title: </label>
      <input type="text" name="titleedit" id="titleedit" value="" class="text ui-widget-content ui-corner-all">

      <label for="descriptionedit">Event Description: </label>
      <input type="text" name="descriptionedit" id="descriptionedit" class="text ui-widget-content ui-corner-all">

      <label for="dateStartEdit">Event Start Date: </label>
          <input type="date" name="dateStartEdit" id="dateStartEdit" class="text ui-widget-content ui-corner-all">

      <label for="dateEndEdit">Event End Date: </label>
          <input type="date" name="dateEndEdit" id="dateEndEdit" class="text ui-widget-content ui-corner-all">
<!--
      <label for="durationedit">Event Duration: <small><i>HH:mm format only</i></small></label>
          <input type="text" name="durationedit" id="durationedit" class="text ui-widget-content ui-corner-all">

      <label for="RSVPslotLimedit">Max Reservations per attendee: </label>
          <input type="number" name="RSVPslotLimedit" id="RSVPslotLimedit" class="text ui-widget-content ui-corner-all" min="0">  
-->
      <input type="hidden" name="creatorID" id="creatorID" value="<?php echo $user->id;?>" />   <!--THIS IS CREATOR_ID -- SHOULD GET FROM SESSION -->
      <!-- Allow form submission with keyboard without duplicating the dialog button -->
      <button type="button" id="edit-submit">Confirm Changes</button>
    </fieldset>
  </form>
</div>


<!-- FORM TO SEND EMAILS AFTER EVENT CREATED -->
<div id="send-email" style="display:none;" title="Send Emails">
<label for="email_invites">Email invites to: </label>
  <div class="form-group">  
    <form name="add_name" id="add_name">  
      <div class="table-responsive" id="add_name">  
         <table class="table table-bordered" id="dynamic_field">
          <button type="button" name="add" id="add" class="btn btn-success">Add Email Slot</button>  
          <p>
         </table>  
         <input type="hidden" name="creatorID" id="creatorID" value="<?php echo $user->id;?>" /> 
        <input type="button" name="submit" id="submitEmail" class="btn btn-info" value="Submit" />
      </div>  
    </form>  
  </div>  
</div>  


<!-- FORM FOR EDIT SLOT EVENT -->
<div class="table-responsive"  style="display:none;" title="Edit Event Slots">  
     <div id="live_data" title="Edit Event Slots"></div>                 
</div>  
 
<!-- SCRIPT FOR THE HIDDEN TITLE AND DESCRIPTION -->
<script type="text/javascript">
  $(document).ready(function(){
    $('.text_container').addClass("hidden");

    $('.text_container').click(function() {
      var $this = $(this);

      if ($this.hasClass("hidden")) {
        $(this).removeClass("hidden").addClass("visible");

      } else {
        $(this).removeClass("visible").addClass("hidden");
      }
    });
  });

//-- SCRIPT FOR "HOVER OVER" FOR UPCOMING EVENTS, WAITING FOR RESPONSE 
$(document).ready(function(){
  $('[data-toggle="tooltip"]').tooltip();   
});

</script>
    <!-- Optional JavaScript -->
    <!-- jQuery first, then Popper.js, then Bootstrap JS -->
    <!--<script src="https://code.jquery.com/jquery-3.4.1.slim.min.js" integrity="sha384-J6qa4849blE2+poT4WnyKhv5vZF5SrPo0iEjwBvKU7imGFAV0wwj1yYfoRSJoZ+n" crossorigin="anonymous"></script> -->
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.4.1/js/bootstrap.min.js" integrity="sha384-wfSDF2E50Y2D1uUdj0O3uMBJnjuUD4Ih7YwaYd1iqfktj0Uod8GCExl3Og8ifwB6" crossorigin="anonymous"></script>
    <script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.0/dist/umd/popper.min.js" integrity="sha384-Q6E9RHvbIyZFJoft+2mJbHaEWldlvI9IOYy5n3zV9zzTtmI3UksdQRVvoxMfooAo" crossorigin="anonymous"></script>

</body>
</html>

