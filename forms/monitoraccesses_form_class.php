<?php // $Id$

require_once($CFG->libdir.'/formslib.php');

class monitoraccesses_form_class extends moodleform {

    /**
     * Child classes will call it before any other thing
     *
     * @param  string  $url
     * @param  mixed   $results
     */
    public function __construct($url, $results) {

        global $CFG;

        require_js($CFG->wwwroot.'/admin/report/monitoraccesses/lib/lib.js');
        require_js(array('yui_yahoo', 'yui_event', 'yui_connection'));

        $this->results = $results;
        parent::__construct($url);
    }


    /**
     * Adds a form checkbox for any results
     *
     * @param string $keyprefix
     */
    protected function add_checkbox_list($keyprefix) {

        global $SESSION;

        $storedprefix = $keyprefix.'s';

        if ($this->results) {
            foreach ($this->results as $element) {

                // Adding the element to $this->_form
                $elementkey = $keyprefix.'_'.$element->id;
                $this->_form->addElement('checkbox', $elementkey, $element->value);

                if (!empty($SESSION->monitoraccessesreport->{$storedprefix}[$element->id])) {
                    $this->_form->setDefault($elementkey, 1);
                }
            }

        }
    }

}

?>