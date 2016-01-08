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

                $xml_module->appendChild($xml->createElement("Description"))->appendChild($xml->createTextNode(substr(clean_html($syllabus->introduction),0,3500)));
                $xml_module->appendChild($xml->createElement("Overview"))->appendChild($xml->createTextNode(clean_html($syllabus->introduction)));

                $f3->set("syllabus", $syllabus);
                $assessment = Template::instance()->render("assessment.htm");
                $xml_module->appendChild($xml->createElement("Assessment"))->appendChild($xml->createTextNode(clean_html($assessment)));
		

                $aims = Template::instance()->render("itemisedlearningoutcomes.htm");
                $xml_module->appendChild($xml->createElement("AimsAndObjectives"))->appendChild($xml->createTextNode(clean_html($aims)));
                $xml_module->appendChild($xml->createElement("Syllabus"))->appendChild($xml->createTextNode(clean_html($syllabus->topics)));
                $xml_module->appendChild($xml->createElement("SpecialFeatures"))->appendChild($xml->createTextNode( $syllabus->specialfeatures));
		$teaching_and_learning = Template::instance()->render("teachingandlearning.htm");
                $xml_module->appendChild($xml->createElement("LearningAndTeaching"))->appendChild($xml->createTextNode(clean_html($teaching_and_learning)));
                $resources = Template::instance()->render("resources.htm");
                $xml_module->appendChild($xml->createElement("Resources"))->appendChild($xml->createTextNode($resources));
		if($this->sharedPerson)
		{
			$add_coordinators = false;
			$coordinators = $xml->createElement("Coordinators");
			foreach($this->sharedPerson as $staff)
			{
				if(@$staff_to_url[$staff->staffid])
				{
					$add_coordinators = true;
					$staff_url = trim($staff_to_url[$staff->staffid]);
					$coordinator = $coordinators->appendChild($xml->createElement("Coordinator"));
					$coordinator->appendChild($xml->createTextNode($staff_url));
				 }
			}
			if($add_coordinators)
			{
				$coordinators = $xml_module->appendChild($coordinators);	
			}

		}
		if($syllabus->costimplications || count($syllabus->ownSpecificcostimplications) > 0 || count($syllabus->generalcostimplications) > 0)
		{
			$additional_costs = $xml->createElement("AdditionalCosts");
			$xml_module->appendChild($additional_costs);
			if(count($syllabus->ownSpecificcostimplications) > 0)
			{
				$specific_costs = $xml->createElement("SpecificCosts");
				$additional_costs->appendChild($specific_costs);
				foreach($syllabus->ownSpecificcostimplications as $cost)
				{	
					$specific_cost = $xml->createElement("SpecificCost");
					$specific_costs->appendChild($specific_cost);
					$cost_type = $xml->createElement("CostType");
					$cost_type->appendChild($xml->createTextNode($syllabus->getConstant($cost->costtype)));
					$specific_cost->appendChild($cost_type);
					$description = $xml->createElement("Description");
					$description->appendChild($xml->createTextNode($cost->costdescription));
					$specific_cost->appendChild($description);

					$cost_price = $xml->createElement("CostPrice");
					$cost_price->appendChild($xml->createTextNode($cost->costprice));
					$specific_cost->appendChild($cost_price);
				}				
			}

			if(count($syllabus->generalcostimplications) > 0)
			{
				$general_costs = $xml->createElement("GeneralCosts");
				$addtional_costs->appendChild($general_costs);
				foreach($syllabus->ownGeneralcostimplications as $cost)
				{
					$general_cost = $xml->createElement("GeneralCost");
					$general_costs->appendChild($general_cost);
					$cost_type = $xml->createElement("CostType")
						->appendChild($xml->createTextNode($syllabus->getConstant($cost->costtype)));
					$general_cost->appendChild($cost_type);
					$description = $xml->createElement("Description")
						->appendChild($xml->createTextNode($cost->costdescription));
					$general_cost->appendChild($description);
				}
			}
			
			if($syllabus->costimplications)
			{
				$content = $xml->createElement("Content");
				$additional_costs->appendChild($content);

				$content->appendChild($xml->createTextNode($syllabus->costimplications));
			}
		}

                return $xml;
        }


}

