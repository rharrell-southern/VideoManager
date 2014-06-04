<?php
ini_set('display_errors', 0);

session_start();
?>
<!DOCTYPE HTML>
<html>
<head>
    <title>Online Learning - Video Player</title>
    <script src="//code.jquery.com/jquery-1.11.0.js"></script>
    <script src="//code.jquery.com/ui/1.10.4/jquery-ui.js"></script>

    <script type="text/javascript" src="timepicker/jquery.timepicker.js"></script>
    <link rel="stylesheet" href="timepicker/jquery.timepicker.css">

    <!-- Load page style -->
    <link rel="stylesheet" href="//code.jquery.com/ui/1.10.4/themes/smoothness/jquery-ui.css">
    <link rel="stylesheet" href="../style.css" type="text/css" media="screen" />

    <script type="text/javascript">
        var data = "";
        var copyEntry = "";
        var index = 0;
        var startHtml = "<div id=\"tableContainer\">\
\
              <table id=\"videos\" border=\"1\" cellpadding=\"7\" cellspacing=\"0\">\
                      <thead>\
                          <tr>\
                              <th>\
                                  Video ID\
                              </th>\
                              <th>\
                                  Course Number\
                              </th>\
                              <th>\
                                  Video Title\
                              </th>\
                              <th>\
                                  File Path\
                              </th>\
                              <th>\
                                  Edit\
                              </th>\
                          </tr>\
                      </thead>\
                      <tbody>\
                      </tbody>\
                  </table>\
\
                  <div id=\"editRow\">\
                  <div id=\"editDupRow\">\
                  </div>\
            </div>";
        $(function() {

          //Populate table and display row modal
          $("body").on("click","#tableContainer tr .classEntry", function() {
            //get video's id
            var VID = $(this).closest('tr').find('td:first').text();

            //update index for current videoid
            index = findIndex(VID);
            alert(VID);

            //fetch row and session data and populate
            populateRow(index, false);

            //launch dialog
            $( "#editRow" ).dialog( "open" );
          });


          //save course number, video title, filepath
          $("body").on("click","#editRowData .saveTitle", function() {
            //Get new data
            var CN = $("#CN" + index).val();
            var VT = $("#VT" + index).val();
            var FP = $("#FP" + index).val();
            console.log(CN + ", " + VT + ", " + FP);

            //TODO: write to DB
              var dataObject = {
                type: "updateCourse",
                ID: data[index][0],
                CN: CN,
                VT: VT,
                FP: FP
              };

              $.ajax({
                type: "POST",
                url: "editRecords.php",
                cache: false,
                data: dataObject,
              }).done(function(status) {
                if(!status){
                  alert("Could not update course entry!");
                }else{
                  //Update local array
                  data[index][1] = CN;
                  data[index][2] = VT;
                  data[index][3] = FP;

                  //remove all children, and reprint table
                  $("#videos > tbody").html("");
                  populateTable();
                  alert("Course details updated!");
                }

              });
          });

          //Duplicate video entry [save course number, video title, filepath]
          $("body").on("click","#videos .duplicateEntry", function(e) {
            //TODO: duplicate entry in local array

            //get videoID
            var VID = $(this).closest('tr').find('td:first').text();
            console.log(VID);

            //find what the index is for videoid
            index = findIndex(VID);

            copyEntry = data[index].slice(0);
            copyEntry.splice(4, 1);
            var copyEntryArr = data[index][4].slice(0);

            copyEntry.splice(4, 0, copyEntryArr);

            console.log("copy: ", copyEntry);


            //display information to be duplicated, for editing purposes

            //fetch row and session data and populate
            populateRow(index, true);

            //launch dialog
            $( "#editDupRow" ).dialog( "open" );
          });


          //Delete video entry and subsequent session entries
          $("body").on("click","#videos .delete", function(e) {

            //get videoID
            var VID = $(this).closest('tr').find('td:first').text();
            console.log(VID);

            //if confirm, delete db entries
            if(confirm("Are you sure you want to delete this video entry? This action cannot be undone.")){

              var dataObject = {
                type: "deleteVideo",
                vid: VID
              };


               $.ajax({
                type: "POST",
                url: "editRecords.php",
                cache: false,
                data: dataObject,
              }).done(function(status) {
                 if(status == 1){  
                  //Reload page, bypassing local array to lazily update data array with the newly submitted data and auto generated IDs.
                  location.reload(true);
                }else{
                  alert("Entry could not be deleted!");
                  console.log("Error: ", status);
                }

              });
            }

          });


          //Remove specific session data
          $("body").on("click","#editRowData .delete", function() {

            if(confirm("Are you sure you want to delete this session?")){
              //thistr
              var thistr = $(this).closest('tr');

              //get session's id
              var SID = $(this).closest('tr').find('td:first').text();

              //filter td text down to just the id
              SID = SID.split(" ");
              SID = SID[2];

              //get innerindex of data array
              var innerIndex = findInnerIndex(SID, index);

              var dataObject = {
                type: "deleteSession",
                vid: data[index][0],
                uid: data[index][4][innerIndex][0]
              };

              $.ajax({
                type: "POST",
                url: "editRecords.php",
                cache: false,
                data: dataObject,
              }).done(function(status) {
                if(!status){
                  alert("Could not delete session!");
                }else{
                  //update data array, and current row modal table
                  data[index][4].splice(innerIndex, 1);
                  console.log("new data array: ", data);

                  thistr.remove();
                }

              });
            }
          });

          //delete row in editing duplicate (before saved)
          $("body").on("click","#editDupRowData .deleteDup", function() {

             if(confirm("Are you sure you want to delete this session?")){
              var thistr = $(this).closest('tr');
              thistr.remove();
            }
          });

          //remove temporary session that hasn't been saved
          $("body").on("click","#editRowData .deleteNew", function() {
            if(confirm("Are you sure you want to delete this session?")){
              var thistr = $(this).closest('tr');
              thistr.remove();
            }
          });

          //save a new session
          $("body").on("click","#editRowData .saveNew", function() {
            //this index
            var thisIndex = $(this).closest('tr').index();

            console.log("index: " + thisIndex);

            sessionArray = [data[index][0], $('#dateTmp' + thisIndex + '1').val() + " " + $('#timeTmp' + thisIndex + '1').val() + ":00", $('#dateTmp' + thisIndex + '2').val() + " " +  $('#timeTmp' + thisIndex + '2').val() + ":00"];

            sessionArray[1] = sessionArray[1].replace(/\//g, "-");
            sessionArray[2] = sessionArray[2].replace(/\//g, "-");

            console.log("sessionArray: ", sessionArray);

            startArray = sessionArray[1].split(" ");
            startArray[0] = startArray[0].split("-");
            startArray[1] = startArray[1].split(":");

            endArray = sessionArray[2].split(" ");
            endArray[0] = endArray[0].split("-");
            endArray[1] = endArray[1].split(":");


            //js date is formatted differently from MSSQL dattime objects, so we have to formulate both JS and SQL dates
            JSstartDate = new Date(startArray[0][2], startArray[0][0]-1, startArray[0][1], startArray[1][0], startArray[1][1], 0, 0);
            JSendDate = new Date(endArray[0][2], endArray[0][0]-1, endArray[0][1], endArray[1][0], endArray[1][1], 0, 0);

            SQLstartDate = startArray[0][2] + "-" + startArray[0][0] + "-" + startArray[0][1] + " " + startArray[1][0] + ":" + startArray[1][1] + ":" + startArray[1][2];
            SQLendDate = endArray[0][2] + "-" + endArray[0][0] + "-" + endArray[0][1] + " " + endArray[1][0] + ":" + endArray[1][1] + ":" + endArray[1][2];


            //Check for dates ending before beginning, along with empty fields
            if(JSstartDate > JSendDate || JSstartDate == JSendDate){
              alert("The specified starting date and time occurs after the specified ending date and time. Please adjust accordingly.");
            }else if($('#dateTmp' + thisIndex + '1').val() == "" || $('#timeTmp' + thisIndex + '1').val() == "" || $('#dateTmp' + thisIndex + '2').val() == "" || $('#timeTmp' + thisIndex + '2').val() == ""){
              alert("One or more fields are empty. Please adjust accordingly.");
            }else{
              //TODO: Insert record into DB and update data array

              var dataObject = {
                type: "addSession",
                vid: sessionArray[0],
                start: SQLstartDate,
                end: SQLendDate
              };

              console.log("dobj: ", dataObject);

              $.ajax({
                type: "POST",
                url: "editRecords.php",
                cache: false,
                data: dataObject,
              }).done(function(status) {
                if(!isNaN(status)){
                  //update data array, and current row modal table
                  var newID = parseInt(status);
                  //console.log("newid: " + newID);

                  //get correct index of data array for vID
                  var vIndex = findIndex(sessionArray[0]);

                  //Add new accepted session to data array
                  data[vIndex][4].push([newID, SQLstartDate, SQLendDate]);

                  //refetch row and session data and populate
                  populateRow(index, false);
                  alert("The session was successfully created!");

                  console.log("new data array: ", data);
                  console.log("status: ", status);
                }else{
                  alert("Could not add session!");
                  console.log("status: " + status);
                }
              });
            }
          });

          //save edits to current session
          $("body").on("click","#editRowData .save", function() {
            //TODO: Get values, validate time objects
            //this index
            var thisIndex = $(this).closest('tr').index();

            //get session id from first tr
            var SID = $(this).closest('tr').find('td:first').text();
            SID = SID.split(" ");
            SID = SID[2];

            //build our new session array
            var sessionArray = [data[index][0], $('#date' + thisIndex + '0').val() + " " + $('#time' + thisIndex + '0').val() + ":00", $('#date' + thisIndex + '1').val() + " " +  $('#time' + thisIndex + '1').val() + ":00", SID];

            sessionArray[1] = sessionArray[1].replace(/\//g, "-");
            sessionArray[2] = sessionArray[2].replace(/\//g, "-");

            console.log("sessionArray: ", sessionArray);

            startArray = sessionArray[1].split(" ");
            startArray[0] = startArray[0].split("-");
            startArray[1] = startArray[1].split(":");

            endArray = sessionArray[2].split(" ");
            endArray[0] = endArray[0].split("-");
            endArray[1] = endArray[1].split(":");


            //js date is formatted differently from MSSQL dattime objects, so we have to formulate both JS and SQL dates
            JSstartDate = new Date(startArray[0][2], startArray[0][0]-1, startArray[0][1], startArray[1][0], startArray[1][1], 0, 0);
            JSendDate = new Date(endArray[0][2], endArray[0][0]-1, endArray[0][1], endArray[1][0], endArray[1][1], 0, 0);

            SQLstartDate = startArray[0][2] + "-" + startArray[0][0] + "-" + startArray[0][1] + " " + startArray[1][0] + ":" + startArray[1][1] + ":" + startArray[1][2];
            SQLendDate = endArray[0][2] + "-" + endArray[0][0] + "-" + endArray[0][1] + " " + endArray[1][0] + ":" + endArray[1][1] + ":" + endArray[1][2];


            //Check for dates ending before beginning, along with empty fields
            if(JSstartDate > JSendDate || JSstartDate == JSendDate){
              alert("The specified starting date and time occurs after the specified ending date and time. Please adjust accordingly.");
            }else if($('#date' + thisIndex + '0').val() == "" || $('#time' + thisIndex + '0').val() == "" || $('#date' + thisIndex + '1').val() == "" || $('#time' + thisIndex + '1').val() == ""){
              alert("One or more fields are empty. Please adjust accordingly.");
            }else{
              var dataObject = {
                type: "updateSession",
                vid: sessionArray[0],
                sid: sessionArray[3],
                start: SQLstartDate,
                end: SQLendDate
              };

              console.log("dobj: ", dataObject);

              $.ajax({
                type: "POST",
                url: "editRecords.php",
                cache: false,
                data: dataObject,
              }).done(function(status) {
                if(status == 1){
                  //update data array, and current row modal table
                  //get correct index of data array for vID
                  var vIndex = findIndex(sessionArray[0]);
                  var iIndex = findInnerIndex(sessionArray[3], vIndex);
                  data[vIndex][4].splice(iIndex, 1);
                  data[vIndex][4].splice(iIndex, 0, [sessionArray[3], SQLstartDate, SQLendDate]);

                  //refetch row and session data and populate
                  populateRow(index, false);
                  alert("The session was successfully updated!");
                  console.log("new data array: ", data);
                }else{
                  alert("Could not update session!");
                  console.log("status: " + status);
                }
              });
            }
          });
        });

        function findIndex(VID){
          //find index in data where videoID = clickedID
          for(var i = 0; i < data.length; i++){
            if(data[i][0] == VID){
              return i;
            }
          }

        }

        function findInnerIndex(SID, ind){
          //find index in data where videoID = clickedID
          for(var i = 0; i < data[ind][4].length; i++){
            if(data[ind][4][i][0] == SID){
              return i;
            }
          }

        }

        function findInnerIndexDup(SID, ind, arr){
          //find index in data where videoID = clickedID
          for(var i = 0; i < arr[4].length; i++){
            if(arr[4][i][0] == SID){
              return i;
            }
          }

        }

        function populateTable(){

          //iterate through the first array, and populate data at the end of the table
          $.each(data, function(index, value){
            $('#videos > tbody:last').append('<tr><td class="classEntry">' + value[0] + '</td><td class="classEntry">' + value[1] + '</td><td class="classEntry">' + value[2] + '</td><td class="classEntry">' + value[3] + '</td><td> <center><span class = "delete" title="Delete Entry" alt="Delete Entry">X</span><br /><div id="spacer"></div> <span class="duplicateEntry"><img src="../img/copy.png" title="Duplicate Entry" alt="Duplicate Entry" height="18" width="18"></span></center></tr>');
          });

        }
        function populateRow(index, duplicate) {

          console.log("index:", data[index]);
          //create and populate table with session information, actual sessions added dynamically following this
          //if duplicate, edit row will be different
          if(!duplicate){
            $("#editRow").html("<table id='editRowData' border='1' cellpadding='7' cellspacing='0'><tbody><tr><td><input class='inputTD' id='CN" + index + "' type='text' value='" + data[index][1] +
             "'></td><td><input class='input' type='text' id='VT" + index + "' value='" + data[index][2] + "'></td><td><input class='input' type='text' id='FP" + index + "' value='" + data[index][3] + "'></td><td><span class='saveTitle'>Save</span></td></tr></tbody></table>");
          
            var k = 1;
            for(var i = 0; i < data[index][4].length; i ++){
              $('#editRowData > tbody:last').append('<tr><td>Session ID: ' + data[index][4][i][0] + '</td><td> <input class="inputTD" id="date' + k + '0" type="text" readonly="readonly"><input class="inputTD" id="time' + k + '0" type="text"> </td><td> <input class="inputTD" id="date' + k + '1" type="text" readonly="readonly"> <input class="inputTD" id="time' + k + '1" type="text"> </td><td class = "edit"> <span class="save">Save</span> <span class = "delete">X</span> </td></tr>');
              
              $('#date' + k + '0').datepicker();            
              $('#date' + k + '1').datepicker();
              $('#time' + k + '0').timepicker({ 'timeFormat': 'H:i' });
              $('#time' + k + '1').timepicker({ 'timeFormat': 'H:i' });

              //formulate date objects
              //start date
              var ts = data[index][4][i][1];
              ts = ts.split(" ");
              var date = ts[0];
              var time = ts[1];

              date = date.split("-");
              time = time.split(":");

              //end date
              var ts1 = data[index][4][i][2];
              ts1 = ts1.split(" ");
              var date1 = ts1[0];
              var time1 = ts1[1];

              date1 = date1.split("-");
              time1 = time1.split(":");

              $('#date' + k + '0').datepicker('setDate', new Date(date[0], (date[1] -1 ), date[2]));
              $('#date' + k + '1').datepicker('setDate', new Date(date1[0], (date1[1] -1 ), date1[2]));

              $('#time' + k + '0').timepicker('setTime', new Date(date[0], (date[1] -1 ), date[2], time[0], time[1], time[2], 0));
              $('#time' + k + '1').timepicker('setTime', new Date(date1[0], (date1[1]-1 ), date1[2], time1[0], time1[1], time1[2], 0));

              k++;
            }
          }else{
            $("#editDupRow").html("<table id='editDupRowData' border='1' cellpadding='7' cellspacing='0'><tbody><tr><td><input class='inputTD' id='CN" + index + "' type='text' value='" + data[index][1] +
             "'></td><td><input class='input' type='text' id='VT" + index + "' value='" + data[index][2] + "'></td><td><input class='input' type='text' id='FP" + index + "' value='" + data[index][3] + "'></td><td></td></tr></tbody></table>");

            var k = 1;
            for(var i = 0; i < data[index][4].length; i ++){
              $('#editDupRowData > tbody:last').append('<tr><td>Session ID: ' + data[index][4][i][0] + '</td><td> <input class="inputTD" id="Ddate' + k + '0" type="text" readonly="readonly"><input class="inputTD" id="Dtime' + k + '0" type="text"> </td><td> <input class="inputTD" id="Ddate' + k + '1" type="text" readonly="readonly"> <input class="inputTD" id="Dtime' + k + '1" type="text"> </td><td class = "edit"> <span class = "deleteDup">X</span> </td></tr>');
              
              $('#Ddate' + k + '0').datepicker();            
              $('#Ddate' + k + '1').datepicker();
              $('#Dtime' + k + '0').timepicker({ 'timeFormat': 'H:i' });
              $('#Dtime' + k + '1').timepicker({ 'timeFormat': 'H:i' });

              //formulate date objects
              //start date
              var ts = data[index][4][i][1];
              ts = ts.split(" ");
              var date = ts[0];
              var time = ts[1];

              date = date.split("-");
              time = time.split(":");

              //end date
              var ts1 = data[index][4][i][2];
              ts1 = ts1.split(" ");
              var date1 = ts1[0];
              var time1 = ts1[1];

              date1 = date1.split("-");
              time1 = time1.split(":");

              $('#Ddate' + k + '0').datepicker('setDate', new Date(date[0], (date[1] -1 ), date[2]));
              $('#Ddate' + k + '1').datepicker('setDate', new Date(date1[0], (date1[1] -1 ), date1[2]));

              $('#Dtime' + k + '0').timepicker('setTime', new Date(date[0], (date[1] -1 ), date[2], time[0], time[1], time[2], 0));
              $('#Dtime' + k + '1').timepicker('setTime', new Date(date1[0], (date1[1]-1 ), date1[2], time1[0], time1[1], time1[2], 0));

              k++;
            }
          }
        }

        function saveDuplicate(){
          //process modal data
          var dupData = [];
          var isValid = true;

          Date.prototype.valid = function() {
            return isFinite(this);
          }

          //if not empty, store input values from table 
          $('#editDupRowData > tbody  > tr > td > input').each(function() {
            if($(this).val() != ""){
              dupData.push($(this).val());
            }else{
              alert("One or more fields are empty. Please adjust accordingly.");
              isValid = false;
            }
          });

          var newCourse = dupData[0];
          var newTitle = dupData[1];
          var newVideo = dupData[2];
          

          //if input data
          if(isValid){
            //separate class info from session data
            var classInfo = [dupData[0], dupData[1], dupData[2]];

            dupData.splice(0, 3);

            

            var sessionData = [];
            var rowCount = 0;

            //formulate SQL and jQuery date objects
            //SQL: "YYYY-MM-DD HH:MM:SS"
            //JS: Date(year, month, day, hours, minutes, seconds, miliseconds)
            for(var i = 0; i < dupData.length; i += 4){
              //track row number
              rowCount++;

              //temp dates and times
              var tmpD = dupData[i].split("/");
              var tmpT = dupData[i+1].split(":");

              var SQLStart = tmpD[2] + "-" + tmpD[0] + "-" + tmpD[1] + " " + tmpT[0] + ":" + tmpT[1] + ":00";
              var jsStart = new Date(tmpD[2], tmpD[0] -1, tmpD[1], tmpT[0], tmpT[1], 0, 0);

              tmpD = dupData[i+2].split("/");
              tmpT = dupData[i+3].split(":");

              var SQLEnd = tmpD[2] + "-" + tmpD[0] + "-" + tmpD[1] + " " + tmpT[0] + ":" + tmpT[1] + ":00";
              var jsEnd = new Date(tmpD[2], tmpD[0] -1, tmpD[1], tmpT[0], tmpT[1], 0, 0);

              //Storing data for SQL
              sessionData.push([SQLStart, SQLEnd]);

              //verify valid date objects, and are ordered correctly
              if(jsStart.valid() == false || jsEnd == false){
                alert("The specified dates and/or times in row #" + rowCount + " are invalid. Please adjust accordingly.");
              }else if(jsStart > jsEnd || jsStart == jsEnd){
                alert("The specified starting date and time in row #" + rowCount + " occurs after the specified ending date and time. Please adjust accordingly.");
                isValid = false;
                break;
              }
            }

            console.log("data: ", isValid, sessionData);

            //Save only if new course number, otherwise prompt to add session, not duplicate entry.
            if(newCourse == data[index][1]){
              if(newVideo == data[index][3]){
                alert("There is an existing entry for this video under " + newCourse + ". Please add the necessary sessions under that entry instead.");
                isValid = false;
              }
            }

            //write to db if valid data
            if(isValid){
              var dataObject = {
                type:     "addDuplicateRow",
                vidData:  [newCourse, newTitle, newVideo],
                sessData: sessionData
              };

              $.ajax({
                type: "POST",
                url: "editRecords.php",
                cache: false,
                data: dataObject,
              }).done(function(status) {
                if(status == 1){  
                  //Reload page, bypassing local array to lazily update data array with the newly submitted data and auto generated IDs.
                  alert("New entry successfully created!");
                  location.reload(true);
                }else{
                  alert("New row could not be created!");
                  console.log("Error: ", status);
                }
              });
            }
              
          }

        }
    </script>
</head>

<body>
<div id="container">
<?php
//echo ("Session: " . $_SESSION['isLoggedIn']);
if ($_SESSION['isLoggedIn'] == 1) { //if we have a session open
   $postData = "{ Session: 'true' }";
} else if ($_POST) {  //if we have posted login information back to ourselves
   $postData = "{ Session: 'false', username: '".$_POST['username']."', password: '".$_POST['pass']."' }";
} 

if($postData) {
?>
   <div id="player"></div>
      <script>
      $.ajax({
          type: "POST",
          url: "auth.php",
          dataType: "json",
          cache: false,
          data: <?=$postData?>
      }).done(function( returnData ) {
        console.log("rdata: ", returnData);
        if(returnData.length > 0){

          data = returnData;

          $("#player").append(startHtml);

          $( "#editRow" ).dialog({
              title: 'Edit Row',
              autoOpen: false,
              resizable: true,
              height: 'auto',
              width: 'auto',
              show: { effect: 'drop', direction: "up" },
              modal: true,
              draggable: true,
              buttons: {
                "Add Session": function() {

                  var lastindex = $('#editRowData').find('tr:last').index();

                  console.log("newindex: ", lastindex);
                  $('#editRowData > tbody:last').append('<tr><td>New Session: </td><td> <input class="inputTD" id="dateTmp' + (lastindex + 1) + '1" type="text" readonly="readonly"><input class="inputTD" id="timeTmp' + (lastindex + 1) + '1" type="text"> </td><td> <input class="inputTD" id="dateTmp' + (lastindex + 1) + '2" type="text" readonly="readonly"> <input class="inputTD" id="timeTmp' + (lastindex + 1) + '2" type="text"></td><td class = "edit"><span class="saveNew">Save</span> <span class = "deleteNew">X</span> </td></tr>');

                  $('#dateTmp' + (lastindex + 1) + '1').datepicker();            
                  $('#dateTmp' + (lastindex + 1) + '2').datepicker();
                  $('#timeTmp' + (lastindex + 1) + '1').timepicker({ 'timeFormat': 'H:i' });            
                  $('#timeTmp' + (lastindex + 1) + '2').timepicker({ 'timeFormat': 'H:i' });


                },
                "Close": function() {
                  $( "#editRow" ).dialog( "close" );
                }
              }
          });

          $( "#editDupRow" ).dialog({
              title: 'Duplicate Row',
              autoOpen: false,
              resizable: true,
              height: 'auto',
              width: 'auto',
              show: { effect: 'drop', direction: "up" },
              modal: true,
              draggable: true,
              buttons: {
                "Add Session": function() {

                  var lastindex = $('#editDupRowData').find('tr:last').index();

                  console.log("newindex: ", lastindex);
                  $('#editDupRowData > tbody:last').append('<tr><td>New Session: </td><td> <input class="inputTD" id="dateTmp' + (lastindex + 1) + '1" type="text" readonly="readonly"><input class="inputTD" id="timeTmp' + (lastindex + 1) + '1" type="text"> </td><td> <input class="inputTD" id="dateTmp' + (lastindex + 1) + '2" type="text" readonly="readonly"> <input class="inputTD" id="timeTmp' + (lastindex + 1) + '2" type="text"></td><td class = "edit"> <span class = "deleteNew">X</span> </td></tr>');

                  $('#dateTmp' + (lastindex + 1) + '1').datepicker();            
                  $('#dateTmp' + (lastindex + 1) + '2').datepicker();
                  $('#timeTmp' + (lastindex + 1) + '1').timepicker({ 'timeFormat': 'H:i' });            
                  $('#timeTmp' + (lastindex + 1) + '2').timepicker({ 'timeFormat': 'H:i' });
                },
                "Save": function() {
                  saveDuplicate();
                },
                "Cancel": function() {
                  $( "#editDupRow" ).dialog( "close" );
                },
              }
          });

          populateTable();
        }else{
          $("#container").html(" <div id='login'>\
            <h2>\
              Please enter authentic login information for access.\
            </h2>\
            <form action='' method='post' >\
              <input name='username' /><br />\
              <input type='password' name='pass' /><br />\
              <input type='submit' value='login' />\
            </form>\
          </div>");
        }
      });
    </script>
<?php
} else {
  ?>
  <div id="login">
    <h2>
      Please enter authentic login information for access.
    </h2>
    <form action="" method="post" >
      <input name="username" /><br />
      <input type="password" name="pass" /><br />
      <input type="submit" value="login" />
    </form>
  </div>
  <?php
}
?>

</div>
</body>
</html>