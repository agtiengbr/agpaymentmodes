<?php

if (!defined('_PS_VERSION_'))
    exit;

function upgrade_module_1_2_11($module)
{
    // Ensure the module is registered to the correct post-order hook
    try {
        $module->registerHook('displayOrderConfirmation');
    } catch (Exception $e) {
        // Ignore if already registered or errors from legacy versions
    }

    return true;
}
