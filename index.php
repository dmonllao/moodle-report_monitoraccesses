<?php

/**
 * Monitor accesses report main controller
 *
 * @license      http://www.gnu.org/licenses/gpl.html
 * @copyright    David Monllaó <david.monllao@urv.cat>
 */


require_once(__DIR__ . '/../../config.php');
require_once($CFG->libdir.'/adminlib.php');
require_once($CFG->dirroot.'/report/monitoraccesses/lib/monitoraccesses_class.php');

$action = optional_param('action', 'courseslist', PARAM_ALPHANUM);


///////////////////////////////////////////////

// Permissions checkings
$context = context_system::instance();
require_login();
require_capability('report/monitoraccesses:view', $context);

$PAGE->set_url('/report/monitoraccesses/index.php', array('action' => $action));
$PAGE->set_pagelayout('report');
$PAGE->set_context($context);

// Front Controller
$class = 'monitoraccesses_'.$action.'_class';
$formclass = 'monitoraccesses_'.$action.'_form_class';

// Requires
$path = $CFG->dirroot.'/report/monitoraccesses';
if (file_exists($path.'/lib/'.$class.'.php')) {
    require_once($path.'/lib/'.$class.'.php');
}
if (file_exists($path.'/forms/'.$formclass.'.php')) {
    require_once($path.'/forms/'.$formclass.'.php');
}
$instance = new $class($action);

// Calling the action controller
$instance->controller();

// Processing the data
$instance->process();
$instance->load_form();

// Displaying results
$instance->display();
$instance->display_footer();
