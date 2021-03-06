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

require_once("includes/backend.php");

function __get_university_id_by_code($univ_code) {
	$db_result = qp("SELECT univ_id FROM university WHERE univ_code = ?", array($univ_code));
	$row = $db_result->FetchRow();
	return $row['univ_id'];
}

function __get_team_id_by_codes($univ_code, $team_code) {
	$univ_id = __get_university_id_by_code($univ_code);
	$db_result = qp("SELECT team_id FROM team WHERE univ_id = ? AND team_code = ?", array($univ_id, $team_code));
	$row = $db_result->FetchRow();
	return $row['team_id'];
}

function get_adjudicator_by_id($adjud_id) {
	$query = "SELECT adjud_name, adjud_id, univ_id, ranking, status FROM adjudicator WHERE adjud_id=?";
	$db_result = qp($query, array($adjud_id));
	$result = array();
	$row = $db_result->FetchRow();
	$adjudicator = array();
	$adjudicator['adjud_name'] = $row['adjud_name'];
	$adjudicator['adjud_id'] = $row['adjud_id'];
	$adjudicator['ranking'] = $row['ranking'];
	$adjudicator['status'] = $row['status'];
	$adjudicator['team_conflicts'] = array();
	$adjudicator['univ_conflicts'] = array();
	$query = "SELECT univ_id FROM strikes WHERE adjud_id=? AND team_id IS NULL";
	$strikeresult=qp($query, array($adjud_id));
	while($strike=$strikeresult->FetchRow()) {
		$adjudicator['univ_conflicts'][]=$strike['univ_id'];
	}
	$query = "SELECT team_id FROM strikes WHERE adjud_id=? AND team_id IS NOT NULL";
	$strikeresult=qp($query, array($adjud_id));
	while($strike=$strikeresult->FetchRow()) {
		$adjudicator['team_conflicts'][]=$strike['team_id'];
	}
	return $adjudicator;
}

function get_active_adjudicators($order_by='adjud_id') {
	$db_result = q("SELECT adjud_id FROM adjudicator WHERE active='Y' ORDER BY $order_by");
	$result = array();
	while ($row = $db_result->FetchRow()) {
		$result[] = get_adjudicator_by_id($row['adjud_id']);
	}
	return $result;
}

function create_temp_adjudicator_table($round) {
	q("DROP TABLE IF EXISTS temp_adjud");
	$query = "CREATE TABLE temp_adjud (";
	$query .= " time TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP ,";
	$query .= " debate_id MEDIUMINT NOT NULL ,";
	$query .= " adjud_id MEDIUMINT NOT NULL ,";
	$query .= " status ENUM( 'chair', 'panelist', 'trainee' ) NOT NULL , UNIQUE KEY adjud_id (adjud_id) );";
	$db_result = q($query);
	if (!$db_result)
		return $DBConn->ErrorMsg();
}

function debates_from_temp_draw_no_adjudicators($round) {
	//join with debates_from_temp_draw_with_adjudicators
	$query = "SELECT debate_id, og, oo, cg, co, T1.univ_id AS univ_1, T2.univ_id AS univ_2, T3.univ_id AS univ_3, T4.univ_id AS univ_4 ";
	$query .= "FROM temp_draw AS D, team AS T1, team AS T2, team AS T3, team AS T4 WHERE D.og = T1.team_id AND D.oo = T2.team_id AND D.cg = T3.team_id AND D.co = T4.team_id ";
        $query .= "ORDER BY debate_id";
	$db_result = q($query);
	if (!$db_result) {
		print $DBConn->ErrorMsg();
	}
	$result = array();
	while ($row = $db_result->FetchRow()) {
		$new_row = array();
		$new_row['debate_id'] = $row['debate_id'];
		$new_row['universities'] = array($row['univ_1'], $row['univ_2'], $row['univ_3'], $row['univ_4']);

		$new_row['points'] = 0;
		$new_row['teams'] = array();
		foreach (array('og', 'oo', 'cg', 'co') as $position) {
			$new_row['teams'][] = $row[$position];
			$new_row['points'] += points_for_team($row[$position], $round - 1);
		}
		$result[] = $new_row;
	}
	return $result;
}

function debates_from_temp_draw_with_adjudicators($round) {
	$query = "SELECT debate_id, og, oo, cg, co, T1.univ_id AS univ_1, T2.univ_id AS univ_2, T3.univ_id AS univ_3, T4.univ_id AS univ_4 ";
	$query .= "FROM temp_draw AS D, team AS T1, team AS T2, team AS T3, team AS T4 WHERE D.og = T1.team_id AND D.oo = T2.team_id AND D.cg = T3.team_id AND D.co = T4.team_id ";
        $query .= "ORDER BY debate_id";
	$db_result = q($query);
	if (!$db_result) {
		print $DBConn->ErrorMsg();
	}
	$result = array();
	while ($row = $db_result->FetchRow()) {
		$new_row = array();
		$new_row['debate_id'] = $row['debate_id'];
		$new_row['universities'] = array($row['univ_1'], $row['univ_2'], $row['univ_3'], $row['univ_4']);

		$new_row['points'] = 0;
		$new_row['teams'] = array();
		foreach (array('og', 'oo', 'cg', 'co') as $position) {
			$new_row['teams'][] = $row[$position];
			$new_row['points'] += points_for_team($row[$position], $round - 1);
		}
		$new_row['adjudicators'] = array();
		$adjudicators_db_result = qp("SELECT adjud_id FROM temp_adjud WHERE debate_id=?", array($row['debate_id']));
		while ($row2 = $adjudicators_db_result->FetchRow()) {
			$new_row['adjudicators'][] = get_adjudicator_by_id($row2['adjud_id']);
		}
		$result[] = $new_row;
	}
	return $result;
}

function get_chair($round, $debate_id) {
	$adj_query = "SELECT DA.adjud_id AS adjud_id, AJ.adjud_name AS adjud_name FROM draw_adjud DA, adjudicator AJ WHERE DA.round_no=? AND DA.debate_id =? AND DA.adjud_id = AJ.adjud_id AND DA.status = 'chair'";
	$adj_result=qp($adj_query, array($round, $debate_id));
	if ($adj_result) {
		$adj_row=$adj_result->FetchRow();
		return $adj_row['adjud_name'];
	} else {
		return FALSE;
	}
}

function get_panel($round, $debate_id) {
	$pan_query = "SELECT DA.adjud_id AS adjud_id, AJ.adjud_name AS adjud_name FROM draw_adjud AS DA, adjudicator AS AJ WHERE DA.round_no = ? AND debate_id = ? AND DA.adjud_id = AJ.adjud_id AND DA.status = 'panelist' ";
	$pan_result = qp($pan_query, array($round, $debate_id));
	$rv = array();
	if($pan_result->RecordCount() > 0){
		while($pan_row=$pan_result->FetchRow()) {
			$rv[]=$pan_row['adjud_name'];
		}
	}
	return $rv;
}

function get_trainees($round, $debate_id) {
	$trainee_query = "SELECT DA.adjud_id AS adjud_id, AJ.adjud_name AS adjud_name FROM draw_adjud AS DA, adjudicator AS AJ WHERE DA.round_no=? AND debate_id=? AND DA.adjud_id = AJ.adjud_id AND DA.status = 'trainee' ";
	$trainee_result=qp($trainee_query, array($round, $debate_id));
	$num_trainee=$trainee_result->RecordCount();
	$rv=array();
	if ($num_trainee > 0){
		while($trainee_row=$trainee_result->FetchRow()) {    
			$rv[] = $trainee_row['adjud_name'];
		}
	}
	return $rv;
}
?>
