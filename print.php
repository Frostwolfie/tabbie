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

$ntu_controller = "print";
$title = "Print";

require("view/header.php");
require("view/mainmenu.php");

require_once("includes/backend.php");
$round = get_num_rounds();
?>

<h2>Print</h2>
<p>
Print module for round <?= $round ?>.
</p>
<h3>Rooms (distribute)</h3>
<ul>
<li><a href="rest.php?result_type=pdf&amp;function=adjudicator_sheets&amp;param=<?= $round ?>">Personalised adjudicator sheets</a></li>
</ul>

<h3>Runners and floormanagers (keep in hand)</h3>
<ul>
<? /*
<li><a href="....recreate...">Printable version of the draw</a></li>
*/ ?>
<li><a href="rest.php?result_type=html&amp;function=get_adjudicators_venues&amp;param=<?= $round ?>&amp;title=Adjudicators%20locations%20for%20round%20<?= $round ?>">List of adjudicators, and their venues</a></li>
<li><a href="rest.php?result_type=html&amp;function=get_teams_venues&amp;param=<?= $round ?>&amp;title=Team%20locations%20for%20round%20<?= $round ?>">
List of teams, and their venues</a>
</ul>

<? /*
<h3>Tab Room (keep in the room)</h3>
<ul>
<li><a href="adjlist.php">Full list of adjudicators</a></li>
<li><a href="freeadj.php">List of unassigned adjudicators</a></li>
<li><a href="teamadjcount.php">Adjudicators per team overview</a></li>
</ul>
*/ ?>
<p>

<?php
require('view/footer.php'); 
?>