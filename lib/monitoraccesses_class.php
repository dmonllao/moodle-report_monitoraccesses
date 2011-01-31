<?php // $Id$


/**
 * Requires automatically the requested classes
 *
 * @param  string  $class
 */
function __autoload($class) {

    global $CFG;

    $path = $CFG->dirroot.'/admin/report/monitoraccesses';

    if (file_exists($path.'/lib/'.$class.'.php')) {
        require_once($path.'/lib/'.$class.'.php');
    } else if (file_exists($path.'/forms/'.$class.'.php')) {
        require_once($path.'/forms/'.$class.'.php');
    }
}


abstract class monitoraccesses_class {

    protected $action;

    protected $form;
    protected $bus;


    /**
     * Main constructor
     *
     * @param  string  Action requested
     */
    public function __construct($action) {
        $this->action = $action;
    }


    public function load_form() {

        global $CFG;

        $reportpath = $CFG->wwwroot.'/admin/report/monitoraccesses/index.php';

        $formname = str_replace('_class', '_form_class', get_class($this));

        // There are actions without form
        if (class_exists($formname)) {
            $this->form = new $formname($reportpath, $this->bus);
        } else {
            $this->form = false;
        }

    }


    /**
     * Action controller (child classes should reimplement it)
     */
    public function controller() {}

    /**
     * Action model (child classes should reimplement it)
     */
    public function process() {}


    /**
     * Loads the header and displays the child class form
     *
     * Child classes must call it before any other thing
     */
    public function display() {

        global $CFG;


        // The AJAX petitions shouldn't display the header
        if (optional_param('output', 'html', PARAM_ALPHA) == 'html') {


            // Printing the header
            admin_externalpage_setup('monitoraccesses');

            $CFG->stylesheets[] = $CFG->wwwroot.'/admin/report/monitoraccesses/styles.css';
            admin_externalpage_print_header();

            print_heading(get_string('title'.$this->action, 'report_monitoraccesses'));
        }

        // Outputs the form if exists
        if ($this->form != false) {
            $this->form->display();
        }
    }


    public function display_footer() {
        admin_externalpage_print_footer();
    }
}

?>