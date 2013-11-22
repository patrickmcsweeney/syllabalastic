<?php

class Model_User extends RedBean_SimpleModel {

	function is_reviewer()
	{
		global $REVIEWERS;
		if (!array_key_exists($this->username, $REVIEWERS))
		{
			return false;
		}
		return true;
	}

	function review_groups_query()
	{
		global $REVIEWERS;

		$sql_parts = array();
		$all_vals = array();

		if (!$this->is_reviewer())
		{
			return null;
		}

		foreach ($REVIEWERS[$this->username] as $tablename => $cols)
		{
			foreach ($cols as $col_name => $values)
			{
				if (count($values) <1) { continue; }

				$sql_parts[] = "$tablename.$col_name IN ( " . R::genSlots($values) . ' )';
				$all_vals = array_merge($all_vals, $values);
			}
		}


		$sql = ' ( ' . implode(' OR ', $sql_parts) . ' ) ';

		return array('sql' => $sql, 'values' => $all_vals);
	}

	#search using the permissions and this syllabus's ID. If we get a result, then we are allowed to review
	function can_review($syllabus)
	{
		$review_groups = $this->review_groups_query();

		$sql = "SELECT
				distinct syllabus.*
			FROM
				syllabus JOIN module
			WHERE
				syllabus.module_id = module.id
				AND isunderreview=1
				AND module.session=?
				AND syllabus.id=?
				AND ". $review_groups["sql"]; 

		$values = array();
		$values[] = key(date_as_session(time()+365*24*60*60)); //we review for next years modules not this years
		$values[] = $syllabus->id;
		$values = array_merge($values, $review_groups['values']);


		$syllabuses = R::convertToBeans("syllabus", R::getAll( $sql, $values));
		return count($syllabuses);
	}

	#on login, pull all ldap data through
	function update_from_ldap_data($ldap_data)
	{
		$this->staffid = $ldap_data[0]['employeenumber'][0];
		$this->username = $ldap_data[0]['name'][0];
		$bits = explode(',',$ldap_data[0]['dn']);
		$faculty_bits = explode("OU=", $bits[2]);
		$this->facultycode = $faculty_bits[1];
		$this->facultyname = $ldap_data[0]['department'][0];
			
		R::store($this);
	}


	function syllabuses_awaiting_submission($session=null)
	{
		if ($session == null)
		{
			$session = dates_as_sessions(strtotime("+1 year"));
			$session = key($session); #we only want the internal represesntation
		}

		$review_groups = $this->review_groups_query();

		$sql = "SELECT
				distinct syllabus.*
			FROM
				syllabus, module
			WHERE
				syllabus.module_id = module.id
				AND isprovisional=1
				AND (isunderreview!=1 or isunderreview is null )
				AND module.session=?
				AND ". $review_groups["sql"] ." 
			ORDER BY
				module.code";

		$values = array();
		$values[] = $session; 
		$values = array_merge($values, $review_groups['values']);

		$syllabuses = R::convertToBeans("syllabus", R::getAll( $sql, $values));

		return $syllabuses;
	}

	function syllabuses_awaiting_review($session = null)
	{
		if ($session == null)
		{
			$session = dates_as_sessions(strtotime("+1 year"));
			$session = key($session); #we only want the internal represesntation
		}

		$review_groups = $this->review_groups_query();

		$sql = "SELECT
				distinct syllabus.*
			FROM
				syllabus JOIN module
			WHERE
				syllabus.module_id = module.id
				AND isunderreview=1
				AND module.session=?
				AND ". $review_groups["sql"] ." 
			ORDER BY
				module.code";


		$values = array();
		$values[] = $session; //we review for next years modules not this years
		$values = array_merge($values, $review_groups['values']);


		$syllabuses = R::convertToBeans("syllabus", R::getAll( $sql, $values));

		return $syllabuses;
	}



}

?>
