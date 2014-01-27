<?php

class Model_Module extends RedBean_SimpleModel {

    public $provisionalsyllabus = false;
    public $currentsyllabus = false;

    public function getProvisional() {
        if(!empty($this->provisionalsyllabus)) { return $this->provisionalsyllabus; }
            if ($syllabus = $this->bean->fetchAs('syllabus')->provisionalsyllabus) {
                $this->provisionalsyllabus = $syllabus;
                return $syllabus;
            }
        return false;
    }

    public function getCurrent() {
        if(!empty($this->currentsyllabus)) { return $this->currentsyllabus; }
            if ($syllabus = $this->bean->fetchAs('syllabus')->currentsyllabus) {
                $this->currentsyllabus = $syllabus;
                return $syllabus;
            }
        return false;
    }

    public function setProvisional($provisional) {
        $this->provisionalsyllabus = $provisional;
        R::store($this);
    }

    public function setCurrent($current) {
        $this->currentsyllabus = $current;
        R::store($this);
    }

}

?>
