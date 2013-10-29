<?php /* begin license *
 * 
 *     Tabbie, Debating Tabbing Software
 *     Copyright Contributors
 * 
 *     This file is part of Tabbie
 * 
 *     Tabbie is free software; you can redistribute it and/or modify
 *     it under the terms of the GNU General Public License as published by
 *     the Free Software Foundation; either version 2 of the License, or
 *     (at your option) any later version.
 * 
 *     Tabbie is distributed in the hope that it will be useful,
 *     but WITHOUT ANY WARRANTY; without even the implied warranty of
 *     MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *     GNU General Public License for more details.
 * 
 *     You should have received a copy of the GNU General Public License
 *     along with Tabbie; if not, write to the Free Software
 *     Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
 * 
 * end license */

require("includes/display.php");
require_once("includes/backend.php");


//Convert pre-1.4.2 databases to the new format
convert_db_ssesl();

//Get POST values and validate/convert them
//rls="Restricted Language Status"

$actionhidden="";
$univ_id=$team_code=$active=$composite=$speaker1=$speaker2=$speaker1esl=$speaker2esl=$esl=$speaker1novice=$speaker2novice=$novice="";

if(array_key_exists("univ_id", @$_POST)) $univ_id=trim(@$_POST['univ_id']);
if(array_key_exists("team_code", @$_POST)) $team_code=makesafe(@$_POST['team_code']);
if(array_key_exists("active", @$_POST)) $active=strtoupper(trim(@$_POST['active']));
if(array_key_exists("composite", @$_POST)) $composite=strtoupper(trim(@$_POST['composite']));
if(array_key_exists("speaker1", @$_POST)) $speaker1=makesafe(trim(@$_POST['speaker1']));
if(array_key_exists("speaker2", @$_POST)) $speaker2=makesafe(trim(@$_POST['speaker2']));
if(array_key_exists("speaker1esl", @$_POST)) $speaker1esl=trim(@$_POST['speaker1esl']);
if(array_key_exists("speaker2esl", @$_POST)) $speaker2esl=trim(@$_POST['speaker2esl']);
if(array_key_exists("speaker1novice", @$_POST)) $speaker1novice=trim(@$_POST['speaker1novice']);
if(array_key_exists("speaker2novice", @$_POST)) $speaker2novice=trim(@$_POST['speaker2novice']);
if(array_key_exists("esl", @$_POST)) $recordedesl=strtoupper(trim(@$_POST['esl']));
if(array_key_exists("novice", @$_POST)) $recordednovice=strtoupper(trim(@$_POST['novice']));
if(array_key_exists("actionhidden", @$_POST)) $actionhidden=trim(@$_POST['actionhidden']); //Hidden form variable to indicate action


if (($actionhidden=="add")||($actionhidden=="edit")) //do validation
  {
    $validate=1;
    //Check if they are empty and set the validate flag accordingly

    if (!$univ_id) $msg[]="University ID Missing.";
    if (!$team_code) $msg[]="Team Code Missing.";
    if (!$speaker1) $msg[]="Speaker 1 Missing.";
    if (!$speaker2) $msg[]="Speaker 2 Missing.";


	//Change Team ESL status if justified
	if(($speaker1esl=="EFL")&&($speaker2esl=="EFL")) $esl="EFL"; else  $esl="N";
	if(($speaker1esl=="ESL")&&($speaker2esl=="EFL")) $esl="ESL"; else  $esl="N";
	if(($speaker1esl=="EFL")&&($speaker2esl=="ESL")) $esl="ESL"; else  $esl="N";
	if(($speaker1esl=="ESL")&&($speaker2esl=="ESL")) $esl="ESL"; else  $esl="N";
	
	if(($speaker1esl=="N")||($speaker2esl=="N"))
	{
		$esl="N";
	} else {
		if (($speaker1esl=="ESL")||($speaker2esl=="ESL")){
			$esl="ESL";
		} else {
			$esl="EFL";
		}
	}
	
	if (($speaker1novice=="Y") && ($speaker2novice=="Y")) {
		$novice="Y";
	} else if (($speaker1novice=="Y")||($speaker2novice=="Y")) {
		$novice="P-A";
	} else {
		$novice="N";
	}

	
	if($esl!=$recordedesl)
	{
		$messagestr="Team restrictive language status automatically set to: ";
		if($esl=="ESL") $messagestr.="ESL";
		if($esl=="EFL") $messagestr.="EFL";
		if($esl=="N") $messagestr.="MBO (Main Break Only)";
		$msg[]=$messagestr;
	}

	if ($novice != $recordednovice) {
		$messagestr="Team novice status automatically set to: ";
		if ($novice == "Y") {
			$messagestr.="Novice";
		} else if ($novice == "P-A") {
			$messagestr.="Pro-Am";
		} else if ($novice == "N") {
			$messagestr.="MBO (Main Break Only)";
		} else {
			$messagestr.="An invalid state ($novice) - ERROR!";
		}
		$msg[]=$messagestr;
	}

    if ((!$active=='Y') && (!$active=='N')) 
      {
        $msg[]="Active Status not set properly.";
        $validate=0;
      }
   
    if ((!$composite=='Y') && (!$composite=='N')) 
      {
        $msg[]="Composite Status not set properly.";
        $validate=0;
      }

    if (strcasecmp($speaker1, $speaker2)==0)
      {
        $msg[]="Speaker names cannot be equal.";
        $validate=0;
      }

    if ((!$univ_id) || (!$team_code) || (!$speaker1) ||(!$speaker2)) $validate=0;
  }

if ($action=="delete")
  {
    
    //Check for whether debates have started
    $query="SELECT COUNT(debate_id) FROM draw;";
    $result=mysql_query($query);

    if (mysql_num_rows($result)!=0)
      $msg[]="Debates in progress. Cannot delete now.";
    else
      {    
    
        //Delete Stuff (From Both Speaker and Team)
        $team_id=trim(@$_GET['team_id']);
    
        $query1="DELETE FROM speaker WHERE team_id='$team_id'";
        $result1=mysql_query($query1);
    //Check for Error
        if (mysql_affected_rows()==0)
      $msg[]="There were problems deleting speakers: No such record.";
   
        $query2="DELETE FROM team WHERE team_id='$team_id'";
        $result2=mysql_query($query2);
        //Check for Error
        if (mysql_affected_rows()==0)
      $msg[]="There were problems deleting team: No such record.";
      }
   
    //Change Mode to Display
    $action="display";    
  }

if ($actionhidden=="add")
  {
    //Check Validation
    if ($validate==1)
      {        
        //Insert Team First
        $query1 = "INSERT INTO team(univ_id, team_code, esl, novice, active, composite) ";
        $query1.= "VALUES('$univ_id','$team_code','$esl','$novice','$active','$composite')";
        $result1=mysql_query($query1);

        if ($result1)
      {
        $queryteam="SELECT team_id FROM team WHERE univ_id='$univ_id' AND team_code='$team_code'";
        $resultteam=mysql_query($queryteam);

        if ($resultteam) 
          {
        $row=mysql_fetch_assoc($resultteam);
        $team_id=$row['team_id'];
        $query2 = "INSERT INTO speaker(team_id, speaker_name, speaker_esl, speaker_novice) ";
        $query2.= "VALUES('$team_id','$speaker1', '$speaker1esl', '$speaker1novice'),('$team_id','$speaker2', '$speaker2esl','$speaker2novice')";
        $result2=mysql_query($query2);

        if (!$result2)
          {
            //Error. Go to display
            unset($msg);
            $msg[]="Serious Error : Cannot Insert Speakers. ".mysql_error();
            $action="display";
          }
        else
          {
            $msg[]="Added record successfully";
          }
          }
        else
          {
        //Error Finding Team. Go to display
        unset($msg);
        $msg[]="Serious Error : Cannot Find Team.".mysql_error();
        $action="display";
          }
      }

        else
      {
            //Error Adding Team. Show error
            $msg[]="Error during insert : ".mysql_error();
            $action="add";
      }

      }  
       
    else
      {
        //Back to Add Mode
        $action="add";
      }
  }


if ($actionhidden=="edit")
  {
    
    $team_id=trim(@$_POST['team_id']);
    $speaker1id=trim(@$_POST['speaker1id']);
    $speaker2id=trim(@$_POST['speaker2id']);
    //Check Validation
    if ($validate==1)
      {
        
        //Edit Team
        $query1 = "UPDATE team ";
        $query1.= "SET univ_id='$univ_id', team_code='$team_code', esl='$esl', novice='$novice', active='$active', composite='$composite'";
        $query1.= "WHERE team_id='$team_id'";
        $result1=mysql_query($query1);
        if (!$result1) 
      $msg[]="Problems editing Team : ".mysql_error();
        
        //Edit Speaker 1
        $query2 = "UPDATE speaker ";
        $query2.= "SET speaker_name='$speaker1', speaker_esl='$speaker1esl', speaker_novice='$speaker1novice' ";
        $query2.= "WHERE speaker_id='$speaker1id'";
        $result2=mysql_query($query2);
        if (!$result2)
      $msg[]="Problems editing Speaker 1 : ".mysql_error();

        //Edit Speaker 2
        $query3 = "UPDATE speaker ";
        $query3.= "SET speaker_name='$speaker2', speaker_esl='$speaker2esl', speaker_novice='$speaker2novice' ";
        $query3.= "WHERE speaker_id='$speaker2id'";
	$result3=mysql_query($query3);
        if (!$result3)
      $msg[]="Problems editing Speaker 2 : ".mysql_error();

        if ((!$result1) || (!$result2) || (!$result3))
      {    
            $action="edit";
      }
    else
      {
        $msg[]="Record Edited Successfully.";
      }
      }

    else
      {
        //Back to Edit Mode
        $action="edit";
      }
  }

if ($action=="edit")
  {
    //Check for Team ID. Issue Error and switch to display if missing or not found
    if ($actionhidden!="edit")
      {
        $team_id=trim(@$_GET['team_id']); //Get team_id from querystring

        //Extract values from database
        $result=mysql_query("SELECT * FROM team WHERE team_id='$team_id'");
        if (mysql_num_rows($result)==0)
      {
            unset($msg); //remove possible validation msgs
            $msg[]="Problems accessing team : Record Not Found.";
            $action="display";
            
      }

        else
      {
            $row=mysql_fetch_assoc($result);
            $univ_id=$row['univ_id'];
            $team_code=$row['team_code'];
	    $esl=$row['esl'];
	    $novice=$row['novice'];
            $active=$row['active'];
            $composite=$row['composite'];

            $result=mysql_query("SELECT * FROM speaker WHERE team_id='$team_id'");
            if (mysql_num_rows($result)!=2)
          {
                unset($msg);//remove possible validation msgs
                $msg[]="Problems accessing speaker : Record Not Found.";
          }

            $row1=mysql_fetch_assoc($result);
            $row2=mysql_fetch_assoc($result);
            $speaker1id=$row1['speaker_id'];
            $speaker1=$row1['speaker_name'];
	    $speaker1esl=$row1['speaker_esl'];
	    $speaker1novice=$row1['speaker_novice'];
            $speaker2id=$row2['speaker_id'];
            $speaker2=$row2['speaker_name'];
	    $speaker2esl=$row2['speaker_esl'];
	    $speaker2novice=$row2['speaker_novice'];

      }
      
      }   
    
  }


switch($action)
  {
  case "add" : 
    $title.=": Add";
    break;
  case "edit" :   
    $title.=": Edit";
    break;
                   
  case "display" :
    $title.=": Display";
    break;
                    
  case "delete"  :
    $title.=": Display";
    break;
  default :
    $title=": Display";
    $action="display";
                    
                    
  }


echo "<h2>$title</h2>\n"; //title

if(isset($msg)) displayMessagesUL(@$msg);
   
//Check for Display
if ($action=="display")
  {
    //Display Data in Tabular Format
    $query = "SELECT T.team_id, univ_code, team_code, univ_name, S1.speaker_name AS speaker1, S2.speaker_name AS speaker2, S1.speaker_esl AS speaker1esl, S2.speaker_esl AS speaker2esl, esl, S1.speaker_novice as speaker1novice, S2.speaker_novice as speaker2novice, novice, active, composite ";
    $query.= "FROM university AS U, team AS T, speaker AS S1, speaker AS S2 ";
    $query.= "WHERE T.univ_id=U.univ_id AND S1.team_id=T.team_id AND S2.team_id=T.team_id AND S1.speaker_id > S2.speaker_id ";

    $active_query = $query . " AND T.ACTIVE = 'Y' ";
    $query.= "ORDER BY univ_name, team_code ";
    $result=mysql_query($query);
    $active_result=mysql_query($active_query);

    if (mysql_num_rows($result)==0)
      {
    //Print Empty Message    
    echo "<h3>No Teams Found.</h3>\n";
    echo "<h3><a href=\"input.php?moduletype=team&amp;action=add\">Add New</a></h3>";
      }
    else
      {

    //Check whether to display Delete Button
    $query="SHOW  TABLES  LIKE  '%_round_%'";
    $showdeleteresult=mysql_query($query);

    if (mysql_num_rows($showdeleteresult)!=0)
      $showdelete=0;
    else
      $showdelete=1;

    //Print Table
    ?>
        <h3>Total No. of Teams : <span id="totalcount"><?echo mysql_num_rows($result)?></span> (<span id="activecount"><?echo mysql_num_rows($active_result)?></span>)</h3>          
      <? echo "<h3><a href=\"input.php?moduletype=team&amp;action=add\">Add New</a></h3>";?>      
          <table>
          <tr><th>Team</th><th>University</th><th>Speaker 1</th><th>Speaker 2</th><th>S1 RLS</th><th>S2 RLS</th><th>Team RLS</th><th>S1 Novice?</th><th>S2 Novice?</th><th>Novice Team?</th><th>Active(Y/N)</th><th>Composite(Y/N)</th></tr>
          <? while($row=mysql_fetch_assoc($result)) { ?>

      <tr <?if ($row['active']=='N') echo "class=\"inactive\""?>>
        <td><?echo $row['univ_code']." ".$row['team_code'];?></td>
         <td><?echo $row['univ_name'];?></td>
         <td><?echo $row['speaker1'] ?></td>
         <td><?echo $row['speaker2'] ?></td>
	 <td><?echo $row['speaker1esl'] ?></td>
	 <td><?echo $row['speaker2esl'] ?></td>
         <td><?echo $row['esl'];?></td>
	 <td><?echo $row['speaker1novice'] ?></td>
	 <td><?echo $row['speaker2novice'] ?></td>
         <td><?echo $row['novice'];?></td>
          <td class='activetoggle' id='team<?php echo $row['team_id']?>'><?echo $row['active'];?></td>
         <td><?echo $row['composite']?></td>
          <td class="editdel"><a href="input.php?moduletype=team&amp;action=edit&amp;team_id=<?echo $row['team_id'];?>">Edit</a></td>
         <?

          if ($showdelete)
               {
             ?>
              <td class="editdel"><a href="input.php?moduletype=team&amp;action=delete&amp;team_id=<?echo $row['team_id'];?>" onClick="return confirm('Are you sure?');">Delete</a></td>
         <?} //Do Not Remove  ?> 
      </tr>

          <?} //Do Not Remove  ?> 
    </table>

      <?
      }

  }

 else //Either Add or Edit
   {

     //Display Form and Values
     ?>
            
     <form action="input.php?moduletype=team" method="POST">
       <input type="hidden" name="actionhidden" value="<?echo $action;?>"/>
       <input type="hidden" name="team_id" value="<?echo $team_id;?>"/>
       <input type="hidden" name="speaker1id" value="<?echo $speaker1id;?>"/>
       <input type="hidden" name="speaker2id" value="<?echo $speaker2id;?>"/>
	<input type="hidden" name="esl" value="<?echo $esl;?>"/>
	<input type="hidden" name="novice" value="<?echo $novice;?>"/>
       <label for="univ_id">University</label>
       <select id="univ_id" name="univ_id">
       <?
       $query="SELECT univ_id,univ_code FROM university ORDER BY univ_code";
     $result=mysql_query($query);
     while($row=mysql_fetch_assoc($result))
       {
                            
     if ($row['univ_id']==$univ_id)
       echo "<option selected value=\"{$row['univ_id']}\">{$row['univ_code']}</option>\n";
     else
       echo "<option value=\"{$row['univ_id']}\">{$row['univ_code']}</option>\n";
       }
                            
     ?>
       </select><br/><br/>
                
       <label for="team_code">Team Code</label>
       <input type="text" maxlength="50" id="team_code" name="team_code" value="<?echo $team_code;?>"/><br/><br/>

	<label for="speaker1">Speaker 1</label>
	<input maxlength="100" type="text" id="speaker1" name="speaker1" value="<?echo $speaker1;?>"/><br/><br/>
               
	<label for="speaker2">Speaker 2</label>
	<input maxlength="100" type="text" id="speaker2" name="speaker2" value="<?echo $speaker2;?>"/><br/><br/>


	<label for="speaker1esl">Speaker 1 ESL</label>
	<select id="speaker1esl" name="speaker1esl">
		<option value="N" <?echo ($speaker1esl=="N")?"selected":""?>>No</option>
		<option value="ESL" <?echo ($speaker1esl=="ESL")?"selected":""?>>ESL</option>
		<option value="EFL" <?echo ($speaker1esl=="EFL")?"selected":""?>>EFL</option>
	</select> <br/><br/>


	<label for="speaker2esl">Speaker 2 ESL</label>
	<select id="speaker2esl" name="speaker2esl">
		<option value="N" <?echo ($speaker2esl=="N")?"selected":""?>>No</option>
		<option value="ESL" <?echo ($speaker2esl=="ESL")?"selected":""?>>ESL</option>
		<option value="EFL" <?echo ($speaker2esl=="EFL")?"selected":""?>>EFL</option>
	</select> <br/><br/>

	<label for="speaker1novice">Speaker 1 Novice</label>
	<select id="speaker1novice" name="speaker1novice">
		<option value="N" <?echo ($speaker1novice=="N")?"selected":""?>>No</option>
		<option value="Y" <?echo ($speaker1novice=="Y")?"selected":""?>>Yes</option>
	</select> <br/><br/>

	<label for="speaker2novice">Speaker 2 Novice</label>
	<select id="speaker2novice" name="speaker2novice">
		<option value="N" <?echo ($speaker2novice=="N")?"selected":""?>>No</option>
		<option value="Y" <?echo ($speaker2novice=="Y")?"selected":""?>>Yes</option>
	</select> <br/><br/>

	<label for="active">Active</label>
	<select id="active" name="active">
		<option value="Y" <?echo ($active=="Y")?"selected":""?>>Yes</option>
		<option value="N" <?echo ($active=="N")?"selected":""?>>No</option>
	</select> <br/><br/>

	<label for="composite">Composite</label>
	<select id="composite" name="composite">
		<option value="N" <?echo ($composite=="N")?"selected":""?>>No</option>
		<option value="Y" <?echo ($composite=="Y")?"selected":""?>>Yes</option>
	</select> <br/><br/>


	<input type="submit" value="<?echo ($action=="edit")?"Edit Team":"Add Team" ;?>"/>
	<input type="button" value="Cancel" onClick="location.replace('input.php?moduletype=team')"/>
	</form>

<?

	}
?>
