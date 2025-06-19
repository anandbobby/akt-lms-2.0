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
 * @package   local_iomad_signup
 * @copyright 2021 Derick Turner
 * @author    Derick Turner
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;
require_once($CFG->dirroot.'/local/iomad/lib/company.php');

/**
 * Event handler for 'user_created'
 * For 'email' authentication (only) add this user
 * to the defined role and company.
 * @param mixed $user user id or user object
 */
function local_iomad_signup_user_created($user) {
    global $CFG, $DB, $SESSION;

    // check if we already have the user object
    if (is_int($user)) {
        $user = $DB->get_record('user', array('id' => $user), '*', MUST_EXIST);
    }

    // If the user is already in a company then we do nothing more
    // as this came from the self sign up pages.
    if ($userrecord = $DB->get_record('company_users', array('userid' => $user->id))) {

        $company = new company($userrecord->companyid);
        // Deal with any auto enrolments.
        if ($CFG->local_iomad_signup_autoenrol) {
            $company->autoenrol($user);
        }
        return true;
    }

    // For the rest of this the plugin needs to be enabled.
    if (!$CFG->local_iomad_signup_enable) {
        return true;
    }

    // If not 'email' auth then we are not interested
    if (empty($CFG->local_iomad_signup_auth) || !in_array($user->auth, explode(',', $CFG->local_iomad_signup_auth))) {
        return true;
    }

    //  Check if user is already in a company.
    //  E.g. if this has already been handled.
    if (!$company = company::by_userid($user->id, true)) {

        // Get context
        $context = context_system::instance();

        // Check if we have a domain already for this users email address.
        list($dump, $emaildomain) = explode('@', $user->email);
        if ($domaininfo = $DB->get_record_sql("SELECT * FROM {company_domains} WHERE " . $DB->sql_compare_text('domain') . " = '" . $DB->sql_compare_text($emaildomain)."'")) {
            // Get company.
            $company = new company($domaininfo->companyid);

            // Get the full user information for department matching.
            profile_load_data($user);

            // Do we have a company departmet profile field?
            $autodepartmentid = $company->get_auto_department($user);

            // assign the user to the company.
            $company->assign_user_to_company($user->id, $autodepartmentid);

            // Deal with company defaults
            $defaults = $company->get_user_defaults();
            foreach ($defaults as $index => $value) {
                $user->$index = $value;
            }

            $DB->update_record('user', $user);
            profile_save_data($user);

            // Force the company theme in case it's not already been done.
            $DB->set_field('user', 'theme', $company->get_theme(), array('id' => $user->id));
        } elseif($SESSION->currenteditingcompany && $CFG->local_iomad_signup_assigncompany_basedon_session){
            // assign to current selected company

            $company = new company($SESSION->currenteditingcompany);

            // Get the full user information for department matching.
            profile_load_data($user);

            // Do we have a company departmet profile field?
            $autodepartmentid = $company->get_auto_department($user);

            // assign the user to the company.
            $company->assign_user_to_company($user->id, $autodepartmentid);

            // Deal with company defaults
            $defaults = $company->get_user_defaults();
            foreach ($defaults as $index => $value) {
                $user->$index = $value;
            }

	        $DB->update_record('user', $user);
            profile_save_data($user);

            // Force the company theme in case it's not already been done.
            $DB->set_field('user', 'theme', $company->get_theme(), array('id' => $user->id));
        }else if (!empty($CFG->local_iomad_signup_company)) {
            // Do we have a company to assign?
            // Get company.
            $company = new company($CFG->local_iomad_signup_company);

            // Get the full user information for department matching.
            profile_load_data($user);

            // Do we have a company departmet profile field?
            $autodepartmentid = $company->get_auto_department($user);

            // assign the user to the company.
            $company->assign_user_to_company($user->id, $autodepartmentid);

            // Deal with company defaults
            $defaults = $company->get_user_defaults();
            foreach ($defaults as $index => $value) {
                $user->$index = $value;
            }

	        $DB->update_record('user', $user);
            profile_save_data($user);

            // Force the company theme in case it's not already been done.
            $DB->set_field('user', 'theme', $company->get_theme(), array('id' => $user->id));
        }
        
        if($CFG->local_iomad_signup_assign_autolicense && $company){
            // license
            $license_to_assign = '';
            $licenses = $DB->get_records('companylicense', array('companyid' => $company->id,"mapping"=>1), 'expirydate DESC', 'id,name,startdate,expirydate,allocation,used');
            if (!empty($licenses) && $user) {
                foreach ($licenses as $license) {
                    if ($license->expirydate > time() && $license->used < $license->allocation) {
                        // check usage count, if available
                        $license_to_assign  = $license;
                        $license_courses = $DB->get_records("companylicense_courses", array("licenseid"=>$license_to_assign->id));
                        foreach ($license_courses as $lc){
                                $recordarray = array('licensecourseid' => $lc->courseid,
                                                    'userid' => $user->id,
                                                    'timecompleted' => null);

                                // Check if we are not assigning multiple times.
                                if (!$DB->get_record('companylicense_users', $recordarray)) {
                                    $recordarray['licenseid'] = $license_to_assign->id;
                                    $recordarray['issuedate'] = time();
                                    $recordarray['isusing'] = 0;
                                    $recordarray['id'] = $DB->insert_record('companylicense_users', $recordarray);
                                    // Create an event.
                                    $eventother = array('licenseid' => $license_to_assign->id,
                                                        'issuedate' => $recordarray['issuedate'],
                                                        'duedate' => '');
                                    $event = \block_iomad_company_admin\event\user_license_assigned::create(array('context' => \context_course::instance($lc->courseid),
                                                                                                                'objectid' => $recordarray['id'],
                                                                                                                'courseid' => $lc->courseid,
                                                                                                                'userid' => $user->id,
                                                                                                                'other' => $eventother));
                                    $event->trigger();
                                }
                        }
                    }
                }
            }
       
        }

        // Do we have a role to assign?
        if (!empty($CFG->local_iomad_signup_role)) {
            // Get role
            if ($role = $DB->get_record('role', array('id' => $CFG->local_iomad_signup_role), '*', MUST_EXIST)) {

                // assign the user to the role
                role_assign($role->id, $user->id, $context->id);
            }
        }
    }

    return true;
}
