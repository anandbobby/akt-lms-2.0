<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * @package   block_iomad_company_admin
 * @copyright 2021 Derick Turner
 * @author    Derick Turner
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

if (!defined('MOODLE_INTERNAL')) {
    die('Direct access to this script is forbidden.');    //  It must be included from a Moodle page.
}

require_once($CFG->libdir.'/formslib.php');

require_once('lib.php');

class admin_uploaduser_form1 extends company_moodleform {
    public function definition () {
        global $CFG, $USER;

        $mform =& $this->_form;
        $mform->addElement("html",html_writer::link(new moodle_url('/blocks/iomad_company_admin/upload_logs.php'),'<i class="fa fa-history" aria-hidden="true"></i> &nbsp; '.get_string("upload_logs","block_iomad_company_admin"),array("class"=>"btn btn-primary")));

        $mform->addElement('filepicker', 'userfile', get_string('file'), null, array('accepted_types' => array('.csv')));
        $mform->addRule('userfile', null, 'required');

        $choices = csv_import_reader::get_delimiter_list();
        $mform->addElement('select', 'delimiter_name', get_string('csvdelimiter', 'tool_uploaduser'), $choices);
        if (array_key_exists('cfg', $choices)) {
            $mform->setDefault('delimiter_name', 'cfg');
        } else if (get_string('listsep', 'langconfig') == ';') {
            $mform->setDefault('delimiter_name', 'semicolon');
        } else {
            $mform->setDefault('delimiter_name', 'comma');
        }

        $choices = core_text::get_encodings();
        $mform->addElement('select', 'encoding', get_string('encoding', 'tool_uploaduser'), $choices);
        $mform->setDefault('encoding', 'UTF-8');

        $choices = array('1'=>1,'5'=>5,'10' => 10, '20' => 20, '100' => 100, );
        $mform->addElement('select', 'previewrows', get_string('rowpreviewnum', 'tool_uploaduser'), $choices);
        $mform->setType('previewrows', PARAM_INT);
        $mform->addHelpButton("previewrows","previewrows","block_iomad_company_admin");

        $choices = array(UU_ADDNEW    => get_string('uuoptype_addnew', 'tool_uploaduser'),
                         UU_ADDINC    => get_string('uuoptype_addinc', 'tool_uploaduser'),
                        UU_ADD_UPDATE => get_string('uuoptype_addupdate', 'tool_uploaduser'),
                         UU_UPDATE     => get_string('uuoptype_update', 'tool_uploaduser'));
        $mform->addElement('select', 'uutype', get_string('uuoptype', 'tool_uploaduser'), $choices);
        $mform->addElement('html','<style>
        #fitem_id_delimiter_name, #fitem_id_encoding {
display:none;
        }
        </style>');

        $this->add_action_buttons(false, get_string('uploadusers', 'block_iomad_company_admin'));
    }

    public function validation($data, $files) {
        global $DB, $SESSION;
        if (!empty($data['cancel'])) {
            return true;
        }
        $errors = parent::validation($data, $files);
        $columns =& $this->_customdata;
        $optype  = $data['uutype'];
        if($optype != UU_UPDATE){
            $systemcontext = context_system::instance();
            if (!empty($SESSION->currenteditingcompany)) {
                $companyid = $SESSION->currenteditingcompany;
            } else {
                $companyid = company_user::companyid();
            }
            if (iomad::has_capability('block/iomad_company_admin:allocate_licenses', $systemcontext) &&  !iomad::has_capability('block/iomad_company_admin:company_view_all', $systemcontext)) {
                if (!$foundlicenses = $DB->get_records_sql_menu("SELECT id, name FROM {companylicense}
                                                       WHERE expirydate >= :timestamp
                                                       AND companyid = :companyid",
                                                       array('timestamp' => time(),
                                                             'companyid' => $companyid))) {
                $errors['uutype'] = get_string("no_license","block_iomad_company_admin");

                  }
            }
        } 
        return $errors;
    }
}

class admin_uploaduser_form2 extends company_moodleform {
    protected $courseselector = null;

    public function definition () {
        global $CFG, $USER, $SESSION;

        $mform   =& $this->_form;
        $columns =& $this->_customdata['columns'];
        $uutype =& $this->_customdata['uutype'];
        $selectyesno = [''=>get_string('select_option','block_iomad_company_admin'),"0"=>"No","1"=>"Yes"];
        // I am the template user, why should it be the administrator? we have roles now, other ppl may use this script ;-).
        $templateuser = $USER;
        // Upload settings and file.
        $mform->addElement('header', 'settingsheader', get_string('settings'));

        $mform->addElement('static', 'uutypelabel', get_string('uuoptype', 'tool_uploaduser'));

        $choices = array(''=>get_string('select_option','block_iomad_company_admin'),0 => get_string('infilefield', 'auth'), 1 => get_string('createpasswordifneeded', 'auth'));
        $mform->addElement('select', 'uupasswordnew', get_string('uupasswordnew', 'tool_uploaduser'), $choices);
        //$mform->setDefault('uupasswordnew', 1);
        if($uutype != UU_UPDATE){
            $mform->addRule('uupasswordnew',get_string('required'),'required',null,'client');
        }
        $mform->disabledIf('uupasswordnew', 'uutype', 'eq', UU_UPDATE);

        $mform->addElement('select', 'sendnewpasswordemails',
                            get_string('sendnewpasswordemails', 'block_iomad_company_admin'),$selectyesno);
       $mform->addRule('sendnewpasswordemails',get_string('required'),'required',null,'client');
        $choices = array(''=>get_string('select_option','block_iomad_company_admin'),
            0 => get_string('nochanges', 'tool_uploaduser'),
                         1 => get_string('uuupdatefromfile', 'tool_uploaduser'),
                         2 => get_string('uuupdateall', 'tool_uploaduser'),
                         3 => get_string('uuupdatemissing', 'tool_uploaduser'));
        $mform->addElement('select', 'uuupdatetype', get_string('uuupdatetype', 'tool_uploaduser'), $choices);
       // $mform->setDefault('uuupdatetype', 0);
       
        $mform->disabledIf('uuupdatetype', 'uutype', 'eq', UU_ADDNEW);
        $mform->disabledIf('uuupdatetype', 'uutype', 'eq', UU_ADDINC);
        if($uutype != UU_ADDNEW && $uutype != UU_ADDINC){
            $mform->addRule('uuupdatetype',get_string('required'),'required',null,'client');
        }

        $choices = array(''=>get_string('select_option','block_iomad_company_admin'),
            0 => get_string('nochanges', 'tool_uploaduser'), 1 => get_string('update'));
        $mform->addElement('select', 'uupasswordold', get_string('uupasswordold', 'tool_uploaduser'), $choices);
        //$mform->setDefault('uupasswordold', 0);
        if($uutype != UU_ADDNEW && $uutype != UU_ADDINC){
            $mform->addRule('uupasswordold',get_string('required'),'required',null,'client');
        }
        $mform->disabledIf('uupasswordold', 'uutype', 'eq', UU_ADDNEW);
        $mform->disabledIf('uupasswordold', 'uutype', 'eq', UU_ADDINC);
        //$mform->disabledIf('uupasswordold', 'uuupdatetype', 'eq', 0);
        //$mform->disabledIf('uupasswordold', 'uuupdatetype', 'eq', 3);

        $mform->addElement('select', 'uuallowrenames', get_string('allowrenames', 'tool_uploaduser'),$selectyesno);
        //$mform->setDefault('uuallowrenames', 0);
        if($uutype != UU_ADDNEW && $uutype != UU_ADDINC){
            $mform->addRule('uuallowrenames',get_string('required'),'required',null,'client');
        }
        
        $mform->disabledIf('uuallowrenames', 'uutype', 'eq', UU_ADDNEW);
        $mform->disabledIf('uuallowrenames', 'uutype', 'eq', UU_ADDINC);

        $mform->addElement('select', 'uuallowdeletes', get_string('allowdeletes', 'tool_uploaduser'),$selectyesno);
        //$mform->setDefault('uuallowdeletes', 0);
        if($uutype != UU_ADDNEW && $uutype != UU_ADDINC){
            $mform->addRule('uuallowdeletes',get_string('required'),'required',null,'client');
        }
        $mform->disabledIf('uuallowdeletes', 'uutype', 'eq', UU_ADDNEW);
        $mform->disabledIf('uuallowdeletes', 'uutype', 'eq', UU_ADDINC);

        $mform->addElement('select', 'uunoemailduplicates', get_string('uunoemailduplicates', 'tool_uploaduser'),$selectyesno);
        //$mform->setDefault('uunoemailduplicates', 1);
        $mform->addRule('uunoemailduplicates',get_string('required'),'required',null,'client');

        $choices = array(
            ''=>get_string('select_option','block_iomad_company_admin'),
            0 => get_string('no'),
                         1 => get_string('uubulknew', 'tool_uploaduser'),
                         2 => get_string('uubulkupdated', 'tool_uploaduser'),
                         3 => get_string('uubulkall', 'tool_uploaduser'));
        $mform->addElement('select', 'uubulk', get_string('uubulk', 'tool_uploaduser'), $choices);
        $mform->setDefault('uubulk', 0);
        
        // Roles selection.
        $showroles = false;
        foreach ($columns as $column) {
            if (preg_match('/^type\d+$/', $column)) {
                $showroles = true;
                break;
            }
        }
        if ($showroles) {
            $mform->addElement('header', 'rolesheader', get_string('roles'));

            $choices = uu_allowed_roles(true);

            $mform->addElement('select', 'uulegacy1', get_string('uulegacy1role', 'tool_uploaduser'), $choices);
            if ($studentroles = get_archetype_roles('student')) {
                foreach ($studentroles as $role) {
                    if (isset($choices[$role->id])) {
                        $mform->setDefault('uulegacy1', $role->id);
                        break;
                    }
                }
                unset($studentroles);
            }

            $mform->addElement('select', 'uulegacy2', get_string('uulegacy2role', 'tool_uploaduser'), $choices);
            if ($editteacherroles = get_archetype_roles('editingteacher')) {
                foreach ($editteacherroles as $role) {
                    if (isset($choices[$role->id])) {
                        $mform->setDefault('uulegacy2', $role->id);
                        break;
                    }
                }
                unset($editteacherroles);
            }

            $mform->addElement('select', 'uulegacy3', get_string('uulegacy3role', 'tool_uploaduser'), $choices);
            if ($teacherroles = get_archetype_roles('teacher')) {
                foreach ($teacherroles as $role) {
                    if (isset($choices[$role->id])) {
                        $mform->setDefault('uulegacy3', $role->id);
                        break;
                    }
                }
                unset($teacherroles);
            }
        }

        // Next the profile defaults.
        profile_definition($mform);

        // Remove the company profile field from the form (this was added by the call to profile_definition
        // above but we don't want the user to edit this here).

        // Hidden fields.
        $mform->addElement('hidden', 'iid');
        $mform->setType('iid', PARAM_INT);

        $mform->addElement('hidden', 'auth');
        $mform->setDefault('auth', '');
        $mform->setType('auth', PARAM_TEXT);

        $mform->addElement('hidden', 'previewrows');
        $mform->setType('previewrows', PARAM_INT);

        $mform->addElement('hidden', 'readcount');
        $mform->setType('readcount', PARAM_INT);

        $mform->addElement('hidden', 'uutype');
        $mform->setType('uutype', PARAM_INT);

        $mform->addElement('hidden', 'uploaded_file_name');
        $mform->setType('uploaded_file_name', PARAM_RAW);

        $mform->addElement('hidden', 'companyid', $this->selectedcompany);
        $mform->setType('companyid', PARAM_INT);
    }

    /**
     * Form tweaks that depend on current data.
     */
    public function definition_after_data() {
        global $USER, $SESSION, $DB, $output;

        $mform   =& $this->_form;
        $columns =& $this->_customdata['columns'];
        $uutype =& $this->_customdata['uutype'];
        foreach ($columns as $column) {
            if ($mform->elementExists($column)) {
                $mform->removeElement($column);
            }
        }

        // Set the companyid to bypass the company select form if possible.
        if (!empty($SESSION->currenteditingcompany)) {
            $companyid = $SESSION->currenteditingcompany;
        } else {
            $companyid = company_user::companyid();
        }

        // Get the department list.
        $company = new company($companyid);
        $companymanualcourses = $company->get_menu_courses(true, true);
        $systemcontext = context_system::instance();
        $parentlevel = company::get_company_parentnode($companyid);
        $this->departmentid = $parentlevel->id;
        $mform->addElement('header', 'advanced');
        $mform->setExpanded('advanced');
        $output->display_tree_selector_form($company, $mform, 0, '', false, false);

        if (!iomad::has_capability('block/iomad_company_admin:assign_company_manager', \context_system::instance())) {
            $mform->addElement('html','<style>fieldset#id_advanced .dep_tree, .department_heading_label, #fitem_manualcourseselector, #fitem_id_uubulk{display:none}</style>');
        }
        // Do we have manual enrolment courses?
        if ($companymanualcourses) {

        // We are going to cheat and be lazy here.
            $autooptions = array('multiple' => true,
                                 'noselectionstring' => get_string('none'),
                                 'onchange' => '');
            $manualcourseselecy = $mform->addElement('select', 'selectedcourses', get_string('selectenrolmentcourse', 'block_iomad_company_admin'), $companymanualcourses, array('id' => 'manualcourseselector'));
            $manualcourseselecy->setMultiple(true);
        }


        // Deal with licenses.
        if (iomad::has_capability('block/iomad_company_admin:allocate_licenses', $systemcontext)) {
            if ($foundlicenses = $DB->get_records_sql_menu("SELECT id, name FROM {companylicense}
                                                   WHERE expirydate >= :timestamp
                                                   AND companyid = :companyid",
                                                   array('timestamp' => time(),
                                                         'companyid' => $this->selectedcompany))) {
                $licenses = array('' => get_string('nolicense', 'block_iomad_company_admin')) + $foundlicenses;
                list($mylicenseid, $mylicensecourse) = current($licenses);
                $mform->addElement('html', "<div class='fitem'><div class='fitemtitle'>" .
                                            get_string('selectlicensecourse', 'block_iomad_company_admin') .
                                            "</div><div class='felement'>");
                $mform->addElement('select', 'licenseid', get_string('select_license', 'block_iomad_company_admin'), $licenses, array('id' => 'licenseidselector', 'onchange' => 'this.form.sumbit()'));
                if (empty($mylicenseid)) {
                    $mform->addElement('html', '<div id="licensedetails"></div>');
                } else {
                    $mylicensedetails = $DB->get_record('companylicense', array('id' => $mylicenseid));
                    $licensestring = get_string('licensedetails', 'block_iomad_company_admin', $mylicensedetails);
                    $licensestring2 = get_string('licensedetails2', 'block_iomad_company_admin', $mylicensedetails);
                    $licensestring3 = get_string('licensedetails3', 'block_iomad_company_admin', $mylicensedetails);
                    $mform->addElement('html', '<div id="    "><b>You have ' . ((intval($licensestring3, 0)) - (intval($licensestring2, 0))) . ' courses left to allocate on this license </b></div>');
                }

                if (!$licensecourses = $DB->get_records_sql_menu("SELECT c.id, c.fullname FROM {companylicense_courses} clc
                                                             JOIN {course} c ON (clc.courseid = c.id
                                                             AND clc.licenseid = :licenseid)",
                                                             array('licenseid' => $mylicenseid))) {
                    $licensecourses = array();
                }

                $mform->addElement('html', '<div id="licensecoursescontainer" style="display:none;">');
                $licensecourseselect = $mform->addElement('select', 'licensecourses',
                                                          get_string('select_license_courses', 'block_iomad_company_admin'),
                                                          $licensecourses, array('id' => 'licensecourseselector'));
                $licensecourseselect->setMultiple(true);
                $mform->addElement('html', '</div>');

                if(($uutype == UU_ADDNEW || $uutype == UU_ADDINC || $uutype == UU_ADD_UPDATE)){
                    $mform->addRule('licenseid',get_string('required'),'required', null, 'client');
                    $mform->addRule('licensecourses',get_string('required'),'required', null, 'client');
                }

                if (!empty($mylicensedetails->program)) {
                    $licensecourseselect->setSelected($licensecourses);
                } else {
                    $licensecourseselect->setSelected(array());
                }
                $mform->addElement('html', "<style>span.error{color:#ef1010}</style></div></div>");
            }else{
                $mform->addElement("html",get_string("no_license","block_iomad_company_admin"));
            }
        }
        

        $mform->addElement('select', 'confirm_upload', get_string("upload_confirm","block_iomad_company_admin"),array(""=>"No","1"=>"Yes"));
        $mform->addRule('confirm_upload',get_string('required'),'required', null, 'client');
        $mform->closeHeaderBefore('confirm_upload');
        $this->add_action_buttons(true, get_string('uploadusers', 'tool_uploaduser'));
    }

    /**
     * Server side validation.
     */
    public function validation($data, $files) {
        global $DB, $SESSION;
        if (!empty($data['cancel'])) {
            return true;
        }
        $errors = parent::validation($data, $files);
        $columns =& $this->_customdata['columns'];
        $optype  = $data['uutype'];

        if(empty($data['confirm_upload'])){
            $errors['confirm_upload'] = get_string('requried_confirm', 'block_iomad_company_admin');
        }
        // Detect if password column needed in file.
        if (!in_array('password', $columns)) {
            switch ($optype) {
                case UU_UPDATE:
                    if (!empty($data['uupasswordold'])) {
                        $errors['uupasswordold'] = get_string('missingfield', 'error', 'password');
                    }
                break;

                case UU_ADD_UPDATE:
                    if (empty($data['uupasswordnew'])) {
                        $errors['uupasswordnew'] = get_string('missingfield', 'error', 'password');
                    }
                    if (!empty($data['uupasswordold'])) {
                        $errors['uupasswordold'] = get_string('missingfield', 'error', 'password');
                    }
                break;

                case UU_ADDNEW:
                    if (empty($data['uupasswordnew'])) {
                        $errors['uupasswordnew'] = get_string('missingfield', 'error', 'password');
                    }
                break;
                case UU_ADDINC:
                    if (empty($data['uupasswordnew'])) {
                        $errors['uupasswordnew'] = get_string('missingfield', 'error', 'password');
                    }
               break;
            }
        }

        // Look for other required data.
        if ($optype != UU_UPDATE) {
            if (!in_array('firstname', $columns)) {
                $errors['uutype'] = get_string('missingfield', 'error', 'firstname');
            }

            if (!in_array('lastname', $columns)) {
                if (isset($errors['uutype'])) {
                    $errors['uutype'] = '';
                } else {
                    $errors['uutype'] = ' ';
                }
                $errors['uutype'] .= get_string('missingfield', 'error', 'lastname');
            }

            if (!in_array('email', $columns) and empty($data['email'])) {
                $errors['email'] = get_string('requiredtemplate', 'tool_uploaduser');
            }
            
    
        }
        if(empty($data['licenseid']) && ($optype == UU_ADDNEW || $optype == UU_ADDINC || $optype == UU_ADD_UPDATE) && !iomad::has_capability('block/iomad_company_admin:company_view_all', \context_system::instance())){
            $errors['licenseid'] = get_string('requiredlicense', 'block_iomad_company_admin');
        }
        //$errors['licenseid'] = 'Not enough test';
      
        if (!empty($data['licenseid']) && ($optype == UU_ADDNEW || $optype == UU_ADDINC)) {
            $license = $DB->get_record('companylicense', array('id' => $data['licenseid']));

            // Are we dealing with a program license?
            if (!empty($license->program)) {
                // If so the courses are not passed automatically.
                $data['licensecourses'] =  $DB->get_records_sql_menu("SELECT c.id, c.fullname FROM {companylicense_courses} clc
                                                                      JOIN {course} c ON (clc.courseid = c.id
                                                                      AND clc.licenseid = :licenseid)",
                                                                      array('licenseid' => $license->id));
            }
            if(empty( $data['licensecourses']) && $optype != UU_UPDATE){
                // try from post
                $data['licensecourses'] = !is_array($_POST['licensecourses'])?[]:$_POST['licensecourses'];
            }
            if (!empty($data['licensecourses'])) {
                if (empty($license->program)) {
                    $requiredcount = count($data['licensecourses']) * ($data['readcount'] - 1);
                } else {
                    $requiredcount = $data['readcount'] - 1;
                }
            } else {
                $errors['licenseid'] = get_string('licensecourses_error','block_iomad_company_admin') ;
            }
            if (empty($license->program)) {
                $free = ($license->allocation - $license->used);
            } else {
                $free = ($license->allocation - $license->used) / count($data['licensecourses']);
            }
            if ( $requiredcount > $free) {
                // check how many free spaces
                // compare it to numbers of users
                $ca = new \stdClass();
                $ca->cancreate = $free;
                $errors['licenseid'] = get_string('licence_error','block_iomad_company_admin',$ca) ;
            }
        }
 

        return $errors;
    }

    /**
     * Used to reformat the data from the editor component
     *
     * @return stdClass
     */
    public function get_data() {
        $data = parent::get_data();

        if ($data !== null && $this->courseselector) {
            $data->selectedcourses = $this->courseselector->get_selected_courses();
        }

        return $data;
    }

    public function set_data($data) {
        parent::set_data($data);

        if ($data['companyid'] > 0) {
            $this->selectedcompany = $data['companyid'];
        }
        if (!empty($data['licenseid'])) {
        }
    }
}

class admin_uploaduser_form3 extends moodleform {
    public function definition () {
        global $CFG, $USER;
        $mform =& $this->_form;
        $this->add_action_buttons(false, get_string('uploadnewfile'));
    }
}
