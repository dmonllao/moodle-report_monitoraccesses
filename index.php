<?php // $Id$

/**
 * Monitor accesses report main controller
 *
 * @license      GPL 2.0    http://www.gnu.org/licenses/gpl-2.0.txt
 * @copyright    David Monllaó <david.monllao@urv.cat>
 */


require_once('../../../config.php');
require_once($CFG->libdir.'/adminlib.php');
require_once($CFG->dirroot.'/admin/report/monitoraccesses/lib/monitoraccesses_class.php');

$action = optional_param('action', 'courseslist', PARAM_ALPHANUM);


///////////////////////////////////////////////


// Permissions checkings
require_login();

require_capability('report/monitoraccesses:view', get_context_instance(CONTEXT_SYSTEM));


// Front Controller
$actionclass = 'monitoraccesses_'.$action.'_class';

$instance = new $actionclass($action);

// Calling the action controller
$instance->controller();

// Processing the data
$instance->process();
$instance->load_form();

// Displaying results
$instance->display();
$instance->display_footer();

?>
