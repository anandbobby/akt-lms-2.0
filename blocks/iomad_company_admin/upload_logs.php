<?php

require_once(dirname(__FILE__) . '/../../config.php'); // Creates $PAGE.
$systemcontext = context_system::instance();
$page = optional_param("page", 0, PARAM_INT);
$perpage = optional_param("perpage", 10, PARAM_INT);
$params = [];
$params['page'] = $page;
$params['perpage'] = $perpage;
$offset = $page * $perpage;
$limit = $perpage;
require_login();
// Set the url.
$linkurl = new moodle_url('/blocks/iomad_company_admin/upload_logs.php', $params);
$PAGE->requires->jquery();
// Print the page header.
$PAGE->set_context($systemcontext);
$PAGE->set_url($linkurl);
$PAGE->set_pagelayout('base');
$PAGE->set_title(get_string("upload_logs", "block_iomad_company_admin"));

// Set the companyid
$companyid = iomad::get_my_companyid($systemcontext);
$company = new company($companyid);
$heading = get_string("upload_logs", "block_iomad_company_admin");
$heading .= " : ".$company->get("name") . ($company->get("shortname") ? " <span class='hd_company_shortname'>( " . $company->get("shortname") . " )</span>" : '');
$PAGE->set_heading($heading, false);
if (!(iomad::has_capability('block/iomad_company_admin:editusers', $systemcontext)
    or iomad::has_capability('block/iomad_company_admin:editallusers', $systemcontext))) {
    print_error('nopermissions', 'error', '', 'edit/delete users');
}
echo $OUTPUT->header();

$wparam = [];
$sql = "select b.* , u.firstname, u.lastname, c.shortname from  {iomad_bulk_upload} b ";
$sql .= " left join  {user} u on u.id = b.userid  ";
$sql .= " inner join {company} c on c.id = b.companyid  ";
$sql .= " where  c.id = $companyid ";
$sql .= "  order by b.timecreated desc";

try{
$totalrecord = $DB->count_records_sql("select count(1) from ($sql) t");
}catch(\Exception $ex){
    $totalrecord = 0;
}
$sql .=" limit $offset, $limit ";
$records = $DB->get_records_sql($sql);
$table = new html_table();
$table->attributes['class'] = 'table table-bordered table-striped';
$table->head = [
    '#',
    'Uploaded at',
    "By User",
    "For company",
    "Status",
    "Summary",
];
$i = $offset;

foreach ($records as $r) {
    $row = array();
    $row[] = ++$i;
    $row[] = userdate($r->timecreated);
    $row[] = html_writer::link(new moodle_url("/user/profile.php", array("id" => $r->userid)), implode(" ", array($r->firstname, $r->lastname)));
    $row[] = $r->shortname;
    $row[] = $r->status;
    $row[] = $r->output_logs;

    $row = new html_table_row(
        $row
    );
    if ($r->status == 'todo') {
        $row->attributes = array("class" => "table-warning");
    }
    $table->data[] = $row;
}
echo html_writer::table($table);
echo $OUTPUT->paging_bar($totalrecord, $page, $perpage, $linkurl);
echo $OUTPUT->footer();