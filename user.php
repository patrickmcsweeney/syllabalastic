<?php

class Model_User extends RedBean_SimpleModel {

	function review_groups()
	{
		global $REVIEWERS;

		return $REVIEWERS[$this->username];
	}


	function syllabuses_awaiting_submission()
	{

		$params = $this->review_groups();

		$sql = "select distinct syllabus.* from syllabus, module where isprovisional=1 and (isunderreview!=1 or isunderreview is null ) and module.session=? and module.facultycode IN (".R::genSlots($params).") order by module.code";

		$session = key(date_as_session(time()+365*24*60*60)); //we review for next years modules not this years
	
		array_unshift($params, $session);
		$syllabuses = R::convertToBeans("syllabus", R::getAll( $sql, $params));

		return $syllabuses;
	}

	function syllabuses_awaiting_review()
	{
		global $REVIEWERS;
		$user = current_user();
		$session = key(date_as_session(time()+365*24*60*60)); //we review for next years modules not this years

		$params = $REVIEWERS[$user->username];
		array_unshift($params, $session);

		$sql = "select distinct syllabus.* from syllabus, module where isunderreview=1 and module.session=? and module.facultycode IN (".R::genSlots($REVIEWERS[$user->username]).") order by module.code";
		$syllabuses = R::convertToBeans("syllabus", R::getAll( $sql, $params));

		return $syllabuses;
	}



}

?>
