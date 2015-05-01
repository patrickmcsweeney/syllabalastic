<?php

class Model_Module extends RedBean_SimpleModel 
{

	public $provisionalsyllabus = false;
	public $currentsyllabus = false;
	static $staff_to_url;

	public function getProvisional() 
	{
		if(!empty($this->provisionalsyllabus)) { return $this->provisionalsyllabus; }

		if ($syllabus = $this->bean->fetchAs('syllabus')->provisionalsyllabus) 
		{
			$this->provisionalsyllabus = $syllabus;
			return $syllabus;
		}
		return false;
	}

	public function getCurrent() 
	{
		if(!empty($this->currentsyllabus)) { return $this->currentsyllabus; }

		if ($syllabus = $this->bean->fetchAs('syllabus')->currentsyllabus) 
		{
			$this->currentsyllabus = $syllabus;
			return $syllabus;
		}
		return false;
	}

	public function setProvisional($provisional) 
	{
		$this->provisionalsyllabus = $provisional;
		R::store($this);
	}

	public function setCurrent($current) 
	{
		$this->currentsyllabus = $current;
		R::store($this);
	}

        function sitePublisherXML($f3)
        {
		$syllabus = $this->getCurrent();
		if(!$syllabus)
		{
			return null;
		}

		if(!isset($staff_to_url))
		{
			$staff_to_url = json_decode(file_get_contents(__DIR__."/etc/idtourl.json"), true);
		}
                $xml = new DOMDocument( "1.0", "utf-8" );

                $xml_module = $xml->createElement( "Module" );
                $xml_module->setAttribute("manuallyUpdated", "no");
                $xml_module->setAttribute("id", $this->code);
                $xml->appendChild($xml_module);
                $xml_module->appendChild($xml->createElement("Current", "yes"));
                $xml_module->appendChild($xml->createElement("Code", $this->code));
	
		#site publisher doesnt like & in titles?
		$title = preg_replace('/&/', " and ", $this->title);

                $xml_module->appendChild($xml->createElement("Title"))->appendChild($xml->createTextNode($title));
                if($this->modulemajorrelation)
                {
                        $xml_module->appendChild($xml->createElement("CourseYear"))->appendChild($xml->createTextNode( $this->ownModulemajorrelation[0]->yearofstudy));
                }
                $xml_module->appendChild($xml->createElement("Semester", $this->semestername));
                $xml_module->appendChild($xml->createElement("CreditRating", $this->credits*2));

                $levels = array("UG"=>"Undergraduate", "PR"=>"Postgraduate Research", "PC" => "Postgraduate Taught");
                $level = @$levels[$this->levelcode];
                $xml_module->appendChild($xml->createElement("Level", $level));
                $contact = $syllabus->kisContactHours();
                $xml_module->appendChild($xml->createElement("ContactHours"));
                $independant = $syllabus->kisIndependantHours();
                $xml_module->appendChild($xml->createElement("NonContactHours"));

                $xml_module->appendChild($xml->createElement("Description"))->appendChild($xml->createTextNode(clean_html($syllabus->introduction)));
                $xml_module->appendChild($xml->createElement("Overview"))->appendChild($xml->createTextNode(clean_html($syllabus->introduction)));

                $f3->set("syllabus", $syllabus);
                $assessment = Template::instance()->render("assessment.htm");
                $xml_module->appendChild($xml->createElement("Assessment"))->appendChild($xml->createTextNode(clean_html($assessment)));
		
		$teaching_and_learning = Template::instance()->render("teachingandlearning.htm");
                $xml_module->appendChild($xml->createElement("LearningAndTeaching"))->appendChild($xml->createTextNode(clean_html($teaching_and_learning)));

                $aims = Template::instance()->render("itemisedlearningoutcomes.htm");
                $xml_module->appendChild($xml->createElement("AimsAndObjectives"))->appendChild($xml->createTextNode(clean_html($aims)));
                $xml_module->appendChild($xml->createElement("Syllabus"))->appendChild($xml->createTextNode(clean_html($syllabus->topics)));
                $xml_module->appendChild($xml->createElement("SpecialFeatures"))->appendChild($xml->createTextNode( $syllabus->specialfeatures));
                $resources = Template::instance()->render("resources.htm");
                $xml_module->appendChild($xml->createElement("Resources"))->appendChild($xml->createTextNode($resources));
		if($this->sharedPerson)
		{
			$coordinators = $xml_module->appendChild($xml->createElement("Coordinators"));
			foreach($this->sharedPerson as $staff)
			{
				if(@$staff_to_url[$staff->staffid])
				{
					$staff_url = trim($staff_to_url[$staff->staffid]);
					$coordinator = $coordinators->appendChild($xml->createElement("Coordinator"));
					$coordinator->appendChild($xml->createTextNode($staff_url));
				}
			}

		}

                return $xml;
        }


}

