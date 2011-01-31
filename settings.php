<?php // $Id$

$ADMIN->add('reports', new admin_externalpage('monitoraccesses', get_string('title', 'report_monitoraccesses'), $CFG->wwwroot.'/admin/report/monitoraccesses/index.php', 'report/monitoraccesses:view'));

