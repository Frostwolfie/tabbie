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
 * end license */ ?>
  <div>
    <h2 class="hide">Teamstandings Submenu</h2>
    <form action=standing.php?moduletype=position method="POST">
       <label for="round">Round: </label>
        <select id="round" name="round">
        <?
            $query="SHOW TABLES LIKE 'result_round%'";
        $result=mysql_query($query);
        $numresults=mysql_num_rows($result);
        if (!$round)
            $round=$numdraws;
        for ($i=1;$i<=$numdraws;$i++)
        {    $text="<option value=\"".$i."\" ";
            if ($i==$round)
                $text.="selected";
            $text.=">Round: ".$i."</option>";
            echo "$text";
        }
    ?>
    </select> <br/><br/>
    
    <input type="submit" value="Change"/>
     </form>
   </div>

