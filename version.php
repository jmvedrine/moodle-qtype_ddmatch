<?php

defined('MOODLE_INTERNAL') || die();

$plugin->component = 'qtype_ddmatch';
$plugin->version   = 2012062600;

$plugin->requires  = 2012061700;
$plugin->dependencies = array(
    'qtype_match' => 2012061700,
);

$plugin->maturity  = MATURITY_STABLE;
