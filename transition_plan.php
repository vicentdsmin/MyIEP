<?php

/** @file
 * @brief 	view transition plan
 * @copyright 	2014 Chelsea School 
 * @copyright 	2005 Grasslands Regional Division #6
 * @copyright		This program is free software; you can redistribute it and/or modify it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or (at your option) any later version.
    This program is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.
    You should have received a copy of the GNU General Public License along with this program; if not, write to the Free Software Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 * @authors		Rik Goldman, Sabre Goldman, Jason Banks, Alex, James, Paul, Bryan, TJ, Jonathan, Micah, Stephen, Joseph
 * @author		M. Nielson
 * @todo		
 * 1. Filter input
 * 2. Escape Output
 * 3. Priority UI overhaul (bootstrap)
 */  
 
//the authorization level for this page!
$MINIMUM_AUTHORIZATION_LEVEL = 100; //everybody


/*   INPUTS: $_GET['student_id']
 *
 */

/**
 * Path for IPP required files.
 */

$system_message = "";

define('IPP_PATH','./');

/* eGPS required files. */
require_once(IPP_PATH . 'etc/init.php');
require_once(IPP_PATH . 'include/db.php');
require_once(IPP_PATH . 'include/auth.php');
require_once(IPP_PATH . 'include/log.php');
require_once(IPP_PATH . 'include/user_functions.php');
require_once(IPP_PATH . 'include/supporting_functions.php');

header('Pragma: no-cache'); //don't cache this page!

if(isset($_POST['LOGIN_NAME']) && isset( $_POST['PASSWORD'] )) {
    if(!validate( $_POST['LOGIN_NAME'] ,  $_POST['PASSWORD'] )) {
        $system_message = $system_message . $error_message;
        IPP_LOG($system_message,$_SESSION['egps_username'],'ERROR');
        require(IPP_PATH . 'index.php');
        exit();
    }
} else {
    if(!validate()) {
        $system_message = $system_message . $error_message;
        IPP_LOG($system_message,$_SESSION['egps_username'],'ERROR');
        require(IPP_PATH . 'index.php');
        exit();
    }
}
//************* SESSION active past here **************************

$student_id="";
if(isset($_GET['student_id'])) $student_id= $_GET['student_id'];
if(isset($_POST['student_id'])) $student_id = $_POST['student_id'];

if($student_id=="") {
   //we shouldn't be here without a student id.
   echo "You've entered this page without supplying a valid student id. Fatal, quitting";
   exit();
}

//check permission levels
$permission_level = getPermissionLevel($_SESSION['egps_username']);
if( $permission_level > $MINIMUM_AUTHORIZATION_LEVEL || $permission_level == NULL) {
    $system_message = $system_message . "You do not have permission to view this page (IP: " . $_SERVER['REMOTE_ADDR'] . ")";
    IPP_LOG($system_message,$_SESSION['egps_username'],'ERROR');
    require(IPP_PATH . 'security_error.php');
    exit();
}

$our_permission = getStudentPermission($student_id);
if($our_permission == "WRITE" || $our_permission == "ASSIGN" || $our_permission == "ALL") {
    //we have write permission.
    $have_write_permission = true;
}  else {
    $have_write_permission = false;
}

//************** validated past here SESSION ACTIVE WRITE PERMISSION CONFIRMED****************

$student_query = "SELECT * FROM student WHERE student_id = " . mysql_real_escape_string($student_id);
$student_result = mysql_query($student_query);
if(!$student_result) {
    $error_message = $error_message . "Database query failed (" . __FILE__ . ":" . __LINE__ . "): " . mysql_error() . "<BR>Query: '$student_query'<BR>";
    $system_message=$system_message . $error_message;
    IPP_LOG($system_message,$_SESSION['egps_username'],'ERROR');
} else {$student_row= mysql_fetch_array($student_result);}



//check if we are modifying a student...
if(isset($_POST['add_transition_plan']) && $have_write_permission) {
  //check that date is the correct pattern...
  $regexp = '/^\d\d\d\d-\d\d?-\d\d?$/';
 if($_POST['date'] == "" || $_POST['plan'] == "") { $system_message = $system_message . "You must supply both a date and a plan<BR>"; }
 else {
  if(!preg_match($regexp,$_POST['date'])) {
    //no way...
    $system_message = $system_message . "Date must be in YYYY-MM-DD format<BR>";
  } else {
    //we add the entry.
    $insert_query = "INSERT INTO transition_plan (student_id,date,plan) VALUES (" . mysql_real_escape_string($student_id) . ",'" . mysql_real_escape_string($_POST['date']) . "','" . mysql_real_escape_string($_POST['plan']) . "')";
    $insert_result = mysql_query($insert_query);
     if(!$insert_result) {
        $error_message = "Database query failed (" . __FILE__ . ":" . __LINE__ . "): " . mysql_error() . "<BR>Query: '$insert_query' <BR>";
        $system_message= $system_message . $error_message;
        IPP_LOG($system_message,$_SESSION['egps_username'],'ERROR');
     } else {
        unset($_POST['plan']);
        unset($_POST['date']);
     }
   }
 }
}

//check if we are deleting some entries...
if(isset($_GET['delete_x']) && $permission_level <= $IPP_MIN_DELETE_TRANSITION_PLAN && $have_write_permission ) {
    $delete_query = "DELETE FROM transition_plan WHERE ";
    foreach($_GET as $key => $value) {
        if(preg_match('/^(\d)*$/',$key))
        $delete_query = $delete_query . "uid=" . $key . " or ";
    }
    //strip trailing 'or' and whitespace
    $delete_query = substr($delete_query, 0, -4);
    //$system_message = $system_message . $delete_query . "<BR>";
    $delete_result = mysql_query($delete_query);
    if(!$delete_result) {
        $error_message = "Database query failed (" . __FILE__ . ":" . __LINE__ . "): " . mysql_error() . "<BR>Query: '$delete_query'<BR>";
        $system_message= $system_message . $error_message;
        IPP_LOG($system_message,$_SESSION['egps_username'],'ERROR');
    }
}

//get the coordination of services for this student...
$transition_query="SELECT * FROM transition_plan WHERE student_id=$student_id ORDER BY date DESC";

$transition_result = mysql_query($transition_query);
if(!$transition_result) {
        $error_message = "Database query failed (" . __FILE__ . ":" . __LINE__ . "): " . mysql_error() . "<BR>Query: '$transition_query'<BR>";
        $system_message= $system_message . $error_message;
        IPP_LOG($system_message,$_SESSION['egps_username'],'ERROR');
}


?>
<?php print_html5_primer(); ?>


    <TITLE><?php echo $page_title; ?></TITLE>

    
    <script language="javascript" src="<?php echo IPP_PATH . "include/popcalendar.js"; ?>"></script>
    <SCRIPT LANGUAGE="JavaScript">
      function confirmChecked() {
          var szGetVars = "strengthneedslist=";
          var szConfirmMessage = "Are you sure you want to modify/delete the following:\n";
          var count = 0;
          form=document.strengthneedslist;
          for(var x=0; x<form.elements.length; x++) {
              if(form.elements[x].type=="checkbox") {
                  if(form.elements[x].checked) {
                     szGetVars = szGetVars + form.elements[x].name + "|";
                     szConfirmMessage = szConfirmMessage + "ID #" + form.elements[x].name + ",";
                     count++;
                  }
              }
          }
          if(!count) { alert("Nothing Selected"); return false; }
          if(confirm(szConfirmMessage))
              return true;
          else
              return false;
      }

      function noPermission() {
          alert("You don't have the permission level necessary"); return false;
      }
    </SCRIPT>
<?php print_bootstrap_head(); ?>
<?php print_datepicker_depends(); ?>
</HEAD>
    <BODY>
<?php print_student_navbar($student_id, $student_row['first_name'] . " " . $student_row['last_name']); ?>

<?php print_jumbotron_with_page_name("Transition Plan", $student_row['first_name'] . " " . $student_row['last_name'], $our_permission); ?>
        
<div class="container">
<?php if ($system_message) { echo $system_message;} ?>


<h2>Current Transition Plans</h2>
<!-- BEGIN transitions table -->
<form name="transtionplans" onSubmit="return confirmChecked();" enctype="multipart/form-data" action="<?php echo IPP_PATH . "transition_plan.php"; ?>" method="get">
<input type="hidden" name="student_id" value="<?php echo $student_id ?>">
<table class="table table-striped table-hover">
                  
<?php
//print the header row...
                        echo "<tr><th>Select</th><th>uid</th><th>Plan</th><th>Date</th></tr>\n";
                        while ($transition_row=mysql_fetch_array($transition_result)) { //current...
                            echo "<tr>\n";
                            echo "<td><input type=\"checkbox\" name=\"" . $transition_row['uid'] . "\"></td>";
                            echo "<td>" . $transition_row['uid'] . "</td>";
                            echo "<td><a href=\"" . IPP_PATH . "edit_transition_plan.php?uid=" . $transition_row['uid'] . "\" class=\"editable_text\" spellcheck=\"true\">" . clean_in_and_out($transition_row['plan'])  ."</td>\n";
                            echo "<td><a href=\"" . IPP_PATH . "edit_transition_plan.php?uid=" . $transition_row['uid'] . "\" class=\"editable_text\">" . $transition_row['date']  ."</td>\n";
                            echo "</tr>\n";
                           
                        }
                        ?>
                       </table>
<!-- Act on selected transition entries -->                 
 <table>
	 <tr>
	<td nowrap>
	<img src="<?php echo IPP_PATH . "images/table_arrow.png"; ?>">&nbsp;With Selected:
	</td>
	<td>
<?php
 //if we have permissions also allow delete.
                                if($permission_level <= $IPP_MIN_DELETE_COORDINATION_OF_SERVICES && $have_write_permission) {
                                    echo "<INPUT NAME=\"delete\" TYPE=\"image\" SRC=\"" . IPP_PATH . "images/smallbutton.php?title=Delete\" border=\"0\" value=\"1\">";
                                }
                             ?>
</td>
</tr>
</form>
</table>
                       
<!-- end transitions table -->

                       
<!-- BEGIN add new entry -->

                        <h2>Add Transition Plan</h2>
                        <form name="add_transition_plan" enctype="multipart/form-data" action="<?php echo IPP_PATH . "transition_plan.php"; ?>" method="post" <?php if(!$have_write_permission) echo "onSubmit=\"return noPermission();\"" ?>>
						<div class="form-group">
                           <input type="hidden" name="add_transition_plan" value="1">
                           <input type="hidden" name="student_id" value="<?php echo $student_id; ?>">
                            <label>Plan</label>
                            <textarea class="form-control" spellcheck="true" name="plan" tabindex="1" cols="40" rows="5" wrap="soft"><?php if(isset($_POST['plan'])) echo $_POST['plan']; ?></textarea>
                            
                           
                       
                           <label>Date: (YYYY-MM-DD)</label>
							<input id="datepicker" class="form-control datepicker" type="datepicker" name="date" data-provide="datepicker" data-date-format="yyyy-mm-dd" value="<?php if(isset($_POST['date'])) echo $_POST['date']; ?>"></p>						 

                           <input type="submit" tabindex="3" name="add" value="add"></td>
                         </div>
                        </form>
                    
<!-- END add new entry --> 
       
 <?php print_complete_footer(); ?>              
              </div> 
<?php print_bootstrap_js(); ?>
    </BODY>
</HTML>
