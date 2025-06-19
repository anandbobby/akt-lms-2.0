<?php

function xmldb_local_akt_upgrade($oldversion) {
    global $CFG, $DB;
    $dbman = $DB->get_manager();
        if ($oldversion < 2024031001) {
            
             $table = new xmldb_table('company');
            $field = new xmldb_field('reengccemail', XMLDB_TYPE_TEXT, null, null, null, null);
        
            if (!$dbman->field_exists($table, $field)) {
                $dbman->add_field($table, $field);
            }
            upgrade_plugin_savepoint(true, 2024031001, 'local', 'akt');
        }
}
