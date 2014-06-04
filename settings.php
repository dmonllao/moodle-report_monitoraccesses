<?php

defined('MOODLE_INTERNAL') || die;

$ADMIN->add(
    'reports',
    new admin_externalpage(
        'monitoraccesses',
        get_string('title', 'report_monitoraccesses'),
        $CFG->wwwroot.'/report/monitoraccesses/index.php',
        'report/monitoraccesses:view'
    )
);

$settings = null;
