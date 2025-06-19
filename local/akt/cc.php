<?php
require('../../config.php');

require_once($CFG->libdir . '/adminlib.php');
require_once($CFG->libdir . '/csvlib.class.php');
require_once("$CFG->libdir/formslib.php");
require_once('locallib.php');
require_once('lib.php');

$iid         = optional_param('iid', '', PARAM_INT);
$previewrows = optional_param('previewrows', 10, PARAM_INT);
core_php_time_limit::raise(60 * 60); // 1 hour should be enough
raise_memory_limit(MEMORY_HUGE);



admin_externalpage_setup('local_akt_cc');
require_admin();
$context = context_system::instance();
$url = new moodle_url('/local/akt/cc.php');
$PAGE->set_url($url);
$PAGE->set_context($context);
$title = get_string('manual_cc', 'local_akt');
$PAGE->set_heading($title);
$PAGE->set_title($title);
$PAGE->set_pagelayout('admin');




class file_form extends moodleform
{
    //Add elements to form
    public function definition()
    {
        global $CFG;

        $mform = $this->_form;

        $mform->addElement('filepicker', 'cclist', get_string('file'));
        $mform->addRule('cclist', null, 'required');

        //$choices = core_text::get_encodings();
        //$mform->addElement('select', 'encoding', get_string('encoding', 'local_akt'), $choices);
        //$mform->setDefault('encoding', 'UTF-8');
        $mform->addElement('html', html_writer::link(new moodle_url("/local/akt/cc_sample.csv"),"Sample CSV"));


        // $choices = array('10'=>10, '20'=>20, '100'=>100, '1000'=>1000);
        // $mform->addElement('select', 'previewrows', get_string('rowpreviewnum', 'local_akt'), $choices);
        // $mform->setType('previewrows', PARAM_INT);

        $this->add_action_buttons(false, get_string('cclist', 'local_akt'));
    }
    //Custom validation should be added here
    function validation($data, $files)
    {
        return array();
    }
}

class file_form2 extends moodleform
{
    function definition()
    {
        $mform = $this->_form;
        $mform->addElement('hidden', 'filedata', get_string('dup', 'local_akt'));
        $mform->setType('filedata', PARAM_RAW);
        $this->add_action_buttons(true, get_string('mark_cclist', 'local_akt'));
    }
}

$mform2 = new file_form2();

$STD_FIELDS = array('id', 'username', 'course_shortname');
$final_out= [];
// If a file has been uploaded, then process it
if ($formdata = $mform2->is_cancelled()) {
    redirect($url);
} else if ($formdata = $mform2->get_data()) {
    global $DB, $CFG;
    $formdata = (array)$formdata;
    $completion_timestamp = time();
    $formdata = json_decode($formdata['filedata'], true);
    $courses = [];
    foreach ($formdata as $ids => $row) {
        if (!empty($row['userid']) && !empty($row['courseid']) &&  empty($row['status'])) {


            // Mark course completion using Moodle's core_completion_update_completion_status_manually function
            if (!array_key_exists($row['courseid'], $courses)) {
                $courses[$row['courseid']] = get_course($row['courseid']);
            }
            $completion = new completion_info($courses[$row['courseid']]);
            if ($completion->is_enabled()) {
                // if ($completion->is_course_complete($row['userid'])) {
                //     $row['status'] = 'Completion already marked';
                //     $formdata[$ids] = $row;
                //     continue;
                // }
                if (!$completion->is_tracked_user($row['userid'])) {
                    $row['status'] = ' Completion not being tracked for this user';
                    $formdata[$ids] = $row;
                    continue;
                }


                try {
                    // Get the user's completion data
                    $cms = $completion->get_activities();
                    foreach ($cms as $cm) {
                      
                        $completiondata =  $completion->get_data($cm, false, $row['userid']);
                        if (!$completiondata->completionstate) {
                            // Mark the course as complete for the user
                            $completion->update_state($cm, COMPLETION_COMPLETE, $row['userid'], true);
                        }
                      
                    }

                    $ccompletion = new \completion_completion([
                        'course' => $row['courseid'],
                        'userid' => $row['userid']
                    ]);
                    $ccompletion->mark_complete($completion_timestamp);

                    // Save the changes
                    $row['cc_status'] = 'Completion marked';
                } catch (\Exception $ex) {
                    $row['status'] = $ex->getMessage();
                }
            } else {
                $row['status'] = 'Completion not setup under course';
            }
        }
        $formdata[$ids] = $row;
    }

    $uniq_file = $USER->id . "_" . time() . "_cclist.csv";

    $erroredtable = new html_table();
    $erroredtable->head = ['username', 'course_shortname', 'status'];
    if(!is_dir($CFG->tempdir . "/iomad_cc_logs/")){
        @mkdir($CFG->tempdir . "/iomad_cc_logs/",0777, true);
    }
    file_put_contents($CFG->tempdir . "/iomad_cc_logs/" . $uniq_file, implode(",",  $erroredtable->head) . PHP_EOL, FILE_APPEND);
    foreach ($formdata as $rl) {
        unset($rl['userid']);
        unset($rl['courseid']);
        $erroredtable->data[] = $rl;
        $rl['username'] = preg_replace('/<\/?a[^>]*>/','', $rl['username'] );
        $rl['course_shortname'] = preg_replace('/<\/?a[^>]*>/','', $rl['course_shortname'] );
        file_put_contents($CFG->tempdir . "/iomad_cc_logs/" . $uniq_file, implode(",", $rl) . PHP_EOL, FILE_APPEND);
    }
     $final_out = '';
    $final_out .= html_writer::table($erroredtable);
    $final_out .= $OUTPUT->single_button(new moodle_url("/blocks/iomad_company_admin/file.php", array("file" => $uniq_file,"area"=>"iomad_cc_logs")), get_string('donwnload_final', 'local_akt'), "post", array("file" => $uniq_file,"area"=>"iomad_cc_logs"));
    $final_out .= $OUTPUT->single_button($url, get_string('continue'));
    echo $OUTPUT->header();
    echo $final_out;
    echo $OUTPUT->footer();
            die;
} else {
    if (empty($iid)) {
        $mform1 = new file_form();
        if ($formdata = $mform1->get_data()) {
            $iid = csv_import_reader::get_new_iid('local_akt');
            $cir = new csv_import_reader($iid, 'local_akt');
            $content = $mform1->get_file_content('cclist');
            $readcount = $cir->load_csv_content($content, 'UTF-8', '');
            $csvloaderror = $cir->get_error();
            unset($content);
            if (!is_null($csvloaderror)) {
                print_error('csvloaderror', '', $url, $csvloaderror);
            }
            // test if columns ok
            $filecolumns = uu_validate_cclist_upload_columns($cir, $STD_FIELDS, $url);
        } else {
            echo $OUTPUT->header();
            $mform1->display();
            echo $OUTPUT->footer();
            die;
        }
    } else {
        $cir = new csv_import_reader($iid, 'local_akt');
        $filecolumns = uu_validate_cclist_upload_columns($cir, $STD_FIELDS, $url);
    }
    // Print the header
    echo $OUTPUT->header();

    $data = array();
    $cir->init();
    $linenum = 1; //column header is first line
    while ($fields = $cir->next()) {
        $courses = [];
        $linenum++;
        if ($linenum > 1000) {
            throw new moodle_exception("Only 1000 records per upload.");
            break;
        }
        $rowcols = array();
        foreach ($fields as $key => $field) {
            $rowcols[$filecolumns[$key]] = s(trim($field));
        }
        $rowcols['status'] = array();
        $rowcols['userid'] = '';
        $rowcols['courseid'] = '';
        if (!empty($rowcols['username'])) {
            $stdusername = \core_user::clean_field($rowcols['username'], 'username');
            if ($rowcols['username'] !== $stdusername) {
                $rowcols['status'][] = get_string('invalidusernameupload');
            }
            if ($userid = $DB->get_field(
                'user',
                'id',
                ['username' => $stdusername, 'mnethostid' => $CFG->mnet_localhost_id]
            )) {
                $rowcols['username'] = \html_writer::link(
                    new \moodle_url('/user/profile.php', ['id' => $userid]),
                    $rowcols['username'],
                    array("target" => "_blank")
                );
                //------
                $ur = $DB->get_records('user', ['id' => $userid], '', '*');
                if ($ur[$userid]->deleted) {
                    $rowcols['status'][] = get_string('deleteduser', 'local_akt');
                }
                if ($ur[$userid]->suspended) {
                    $rowcols['status'][] = get_string('suspendeduser', 'local_akt');
                }
            } else {
                $rowcols['status'][] = get_string('notlmsuser', 'local_akt');
            }
        } else {
            $rowcols['status'][] = get_string('nousername', 'local_akt');
        }

        if (!empty($rowcols['course_shortname'])) {
            if ($courseid = $DB->get_field(
                'course',
                'id',
                ['shortname' => $rowcols['course_shortname']]
            )) {
                $rowcols['course_shortname'] = \html_writer::link(
                    new \moodle_url('/course/view.php', ['id' => $courseid]),
                    $rowcols['course_shortname'],
                    array("target" => "_blank")
                );
                if (!array_key_exists($courseid, $courses)) {
                    $courses[$courseid] = get_course($courseid);
                }
                if (!$courses[$courseid]->enablecompletion) {
                    $rowcols['status'][] = get_string('nocompletion', 'local_akt');
                }
            } else {
                $rowcols['status'][] = get_string('nocourse', 'local_akt');
            }
        } else {
            $rowcols['status'][] = get_string('nocourse', 'local_akt');
        }
        if ($userid && $courseid) {
            $contextcourse = context_course::instance($courseid);
            $roles = get_user_roles($contextcourse, $userid);
            $student = false;
            foreach ($roles as $role) {
                if ($role->shortname == 'student') {
                    $student = true;
                }
            }
            if (!$student) {
                $rowcols['status'][] = get_string('nostudent', 'local_akt');
            } else {
                $rowcols['userid'] = $userid;
                $rowcols['courseid'] = $courseid;
            }
        }
        $rowcols['status'] = implode('<br />', $rowcols['status']);
        $data[] = $rowcols;
    }
    if ($fields = $cir->next()) {
        $data[] = array_fill(0, count($fields) + 1, '...');
    }
    $cir->close();
    $table = new html_table();
    $table->id = "uupreview";
    $table->attributes['class'] = 'generaltable';
    $table->caption = get_string('uploaduserlistpreview', 'local_akt');
    $table->head = array();

    foreach ($filecolumns as $column) {
        $table->head[] = $column;
    }
    foreach ($data as $r) {
        unset($r['userid']);
        unset($r['courseid']);
        $table->data[] = $r;
    }
    $table->head[] = get_string('status');
    echo html_writer::tag('div', html_writer::table($table), array('class' => 'flexible-wrap'));

    $cir->close();
    $data = json_encode($data);
    $mform2->set_data(['filedata' => $data]);
    $mform2->display();
}


echo $OUTPUT->footer();

