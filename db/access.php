<?php // $Id$

$report_monitoraccesses_capabilities = array(

    'report/monitoraccesses:view' => array(
        'captype' => 'read',
        'contextlevel' => CONTEXT_SYSTEM,
        'legacy' => array(
            'admin' => CAP_ALLOW
        )
    )
);

?>
