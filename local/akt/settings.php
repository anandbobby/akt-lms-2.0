<?php

defined('MOODLE_INTERNAL') || die();



if ($hassiteconfig) {
    $settings = new admin_settingpage('local_akt', 'Aktrea Custom');
    $ADMIN->add('root', $settings);

    $settings->add(new admin_setting_configselect(
    'local_akt/hide_login_form',
    'Hide Login Form',
    '',
    0,
    array(0 => 'No', 1 => "Yes")
));

$companies = $DB->get_records_menu("company",array("suspended"=>0, "companyterminated"=>0),'id desc', 'id,name');

 $settings->add(new admin_setting_configmultiselect('local_akt/for_company', 'For Companies',
            '', [], $companies));

$ADMIN->add('root', new admin_externalpage('local_akt_cc', get_string("manual_cc","local_akt"), new moodle_url('/local/akt/cc.php')));
    
    
}
    ?>