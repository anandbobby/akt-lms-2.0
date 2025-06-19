<?php

function xmldb_local_akt_install() {
    global $CFG, $DB;
    $dbman = $DB->get_manager();
    $table = new xmldb_table('oauth2_issuer');
    $field = new xmldb_field('companyid', XMLDB_TYPE_INTEGER, '20', null, XMLDB_NOTNULL, null, '0', 'id');


    if (!$dbman->field_exists($table, $field)) {
        $dbman->add_field($table, $field);
    }

    try{
    $DB->execute("CREATE TABLE IF NOT EXISTS {iomad_bulk_upload} (
        `id` int NOT NULL AUTO_INCREMENT,
        `formdata` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
        `output_logs` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
        `timecreated` int NOT NULL,
        `userid` int NOT NULL,
        `companyid` int NOT NULL,
        `iid` int NOT NULL,
        `status` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'todo',
        `filepath` varchar(1000) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
        PRIMARY KEY (`id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    $DB->execute("ALTER TABLE {companylicense} ADD `mapping` INT NULL DEFAULT '0' COMMENT '0: manual, 1 :automatic' AFTER `clearonexpire`");
    }catch(\Exception $ex){

    }

    $authrole = $DB->get_record('role', array('shortname' => "companydepartmentmanager"));
    
    assign_capability('block/iomad_company_admin:edit_departments', CAP_ALLOW, $authrole->id , \context_system::instance()->id);
    // Reset all default authenticated users permissions.
    unassign_capability('block/iomad_approve_access:approve', $authrole->id);
    unassign_capability('block/iomad_company_admin:companymanagement_view', $authrole->id);
    unassign_capability('block/iomad_company_admin:coursemanagement_view', $authrole->id);
    unassign_capability('local/iomad_learningpath:manage', $authrole->id);
    unassign_capability('local/iomad_learningpath:view', $authrole->id);
    unassign_capability('local/report_emails:resend', $authrole->id);
    unassign_capability('local/report_emails:view', $authrole->id); 
    $authrole->name = "Tenant Manager";
    $DB->update_record("role", $authrole);
    
    
     $table = new xmldb_table('company');
     $field = new xmldb_field('reengccemail', XMLDB_TYPE_TEXT, null, null, null, null);

    if (!$dbman->field_exists($table, $field)) {
        $dbman->add_field($table, $field);
    }
}
