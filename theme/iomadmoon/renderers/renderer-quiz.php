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

defined('MOODLE_INTERNAL') || die();


// Quiz.
require_once($CFG->dirroot . "/mod/quiz/renderer.php");
class theme_iomadmoon_mod_quiz_renderer extends mod_quiz_renderer {
    /**
     * Outputs a box.
     *
     * @param string $contents The contents of the box
     * @param string $classes A space-separated list of CSS classes
     * @param string $id An optional ID
     * @param array $attributes An array of other attributes to give the box.
     * @return string the HTML to output.
     */
    public function space_box($contents, $classes = 'generalbox', $id = null, $attributes = array()) {
        return $this->space_box_start($classes, $id, $attributes) . $contents . $this->space_box_end();
    }

    /**
     * Outputs the opening section of a box.
     *
     * @param string $classes A space-separated list of CSS classes
     * @param string $id An optional ID
     * @param array $attributes An array of other attributes to give the box.
     * @return string the HTML to output.
     */
    public function space_box_start($classes = 'generalbox', $id = null, $attributes = array()) {
        $this->opencontainers->push('box', html_writer::end_tag('div'));
        $attributes['id'] = $id;
        $attributes['class'] = 'box ' . renderer_base::prepare_classes($classes);
        return html_writer::start_tag('div', $attributes);
    }

    /**
     * Outputs the closing section of a box.
     *
     * @return string the HTML to output.
     */
    public function space_box_end() {
        return $this->opencontainers->pop('box');
    }


    /**
     * Render the tertiary navigation for pages during the attempt.
     *
     * @param string|moodle_url $quizviewurl url of the view.php page for this quiz.
     * @return string HTML to output.
     */
    public function during_attempt_tertiary_nav($quizviewurl): string {
        $output = '';
        $output .= html_writer::start_div('tertiary-navigation mb-md-2');
        $output .= html_writer::start_div('row no-gutters');
        $output .= html_writer::start_div('navitem');
        $output .= html_writer::link(
            $quizviewurl,
            get_string('back'),
            ['class' => 'btn btn-secondary']
        );
        $output .= html_writer::end_div();
        $output .= html_writer::end_div();
        $output .= html_writer::end_div();
        return $output;
    }


    /**
     * Render the tertiary navigation for the view page.
     *
     * @param mod_quiz_view_object $viewobj the information required to display the view page.
     * @return string HTML to output.
     */
    public function view_page_tertiary_nav(mod_quiz_view_object $viewobj): string {
        $content = '';

        if ($viewobj->buttontext) {
            $attemptbtn = $this->start_attempt_button(
                $viewobj->buttontext,
                $viewobj->startattempturl,
                $viewobj->preflightcheckform,
                $viewobj->popuprequired,
                $viewobj->popupoptions
            );
            $content .= $attemptbtn;
        }

        if ($viewobj->canedit && !$viewobj->quizhasquestions) {
            $content .= html_writer::link(
                $viewobj->editurl,
                get_string('addquestion', 'quiz'),
                ['class' => 'btn btn-primary']
            );
        }

        if ($content) {
            return html_writer::div(html_writer::div($content, 'row no-gutters'), 'tertiary-navigation mb-3');
        } else {
            return '';
        }
    }

    /**
     * Output the page information
     *
     * @param object $quiz the quiz settings.
     * @param object $cm the course_module object.
     * @param context $context the quiz context.
     * @param array $messages any access messages that should be described.
     * @param bool $quizhasquestions does quiz has questions added.
     * @return string HTML to output.
     */
    public function view_information($quiz, $cm, $context, $messages, bool $quizhasquestions = false) {
        $output = '';

        // Output any access messages.
        if ($messages) {
            $output .= $this->box($this->access_messages($messages), 'rui-quizinfo');
        }

        // Show number of attempts summary to those who can view reports.
        if (has_capability('mod/quiz:viewreports', $context)) {
            if (
                $strattemptnum = $this->quiz_attempt_summary_link_to_reports(
                    $quiz,
                    $cm,
                    $context
                )
            ) {
                $output .= html_writer::tag(
                    'div',
                    $strattemptnum,
                    array('class' => 'rui-quizattemptcounts my-4')
                );
            }
        }

        if (has_any_capability(['mod/quiz:manageoverrides', 'mod/quiz:viewoverrides'], $context)) {
            if ($overrideinfo = $this->quiz_override_summary_links($quiz, $cm)) {
                $output .= html_writer::tag('div', $overrideinfo, ['class' => 'rui-quizattemptcounts my-4']);
            }
        }

        return $output;
    }

    /**
     * Output the quiz intro.
     * @param object $quiz the quiz settings.
     * @param object $cm the course_module object.
     * @return string HTML to output.
     */
    public function quiz_intro($quiz, $cm) {
        if (html_is_blank($quiz->intro)) {
            return '';
        }

        return html_writer::tag(
            'div',
            format_module_intro('quiz', $quiz, $cm->id),
            array('id' => 'intro')
        );
    }

    /**
     * Generates data pertaining to quiz results
     *
     * @param array $quiz Array containing quiz data
     * @param int $context The page context ID
     * @param int $cm The Course Module Id
     * @param mod_quiz_view_object $viewobj
     */
    public function view_result_info($quiz, $context, $cm, $viewobj) {
        $output = '';
        if (!$viewobj->numattempts && !$viewobj->gradecolumn && is_null($viewobj->mygrade)) {
            return $output;
        }
        $resultinfo = '';

        if ($viewobj->overallstats) {
            if ($viewobj->moreattempts) {
                $a = new stdClass();
                $a->method = quiz_get_grading_option_name($quiz->grademethod);
                $a->mygrade = quiz_format_grade($quiz, $viewobj->mygrade);
                $a->quizgrade = quiz_format_grade($quiz, $quiz->grade);
                $resultinfo .= '<span class="d-block lead mb-4">' . get_string('gradesofar', 'quiz', $a) . '</span>';
            } else {
                $a = new stdClass();
                $a->grade = quiz_format_grade($quiz, $viewobj->mygrade);
                $a->maxgrade = quiz_format_grade($quiz, $quiz->grade);
                $a = get_string('outofshort', 'quiz', $a);
                $resultinfo .= '<span class="d-block lead mb-4">' . get_string('yourfinalgradeis', 'quiz', $a) . '</span>';
            }
        }

        if ($viewobj->mygradeoverridden) {

            $resultinfo .= html_writer::tag(
                'p',
                get_string('overriddennotice', 'grades'),
                array('class' => 'overriddennotice')
            ) . "\n";
        }
        if ($viewobj->gradebookfeedback) {
            $resultinfo .= $this->heading(get_string('comment', 'quiz'), 5);
            $resultinfo .= html_writer::div($viewobj->gradebookfeedback, 'rui-quizteacherfeedback') . "\n";
        }
        if ($viewobj->feedbackcolumn) {
            $resultinfo .= $this->heading(get_string('overallfeedback', 'quiz'), 5);
            $resultinfo .= html_writer::div(
                quiz_feedback_for_grade($viewobj->mygrade, $quiz, $context),
                'rui-quizgradefeedback mb-3 border-bottom'
            ) . "\n";
        }

        if ($resultinfo) {
            $output .= $this->space_box($resultinfo, 'generalbox', 'rui-feedback');
        }
        return $output;
    }


    /**
     * Generates the table of data
     *
     * @param array $quiz Array contining quiz data
     * @param int $context The page context ID
     * @param mod_quiz_view_object $viewobj
     */
    public function view_table($quiz, $context, $viewobj) {
        if (!$viewobj->attempts) {
            return '';
        }

        // Prepare table header.
        $table = new html_table();
        $table->attributes['class'] = 'generaltable rui-quizattemptsummary mt-4 mb-0';
        $table->head = array();
        $table->align = array();
        $table->size = array();
        if ($viewobj->attemptcolumn) {
            $table->head[] = get_string('attemptnumber', 'quiz');
            $table->size[] = '';
        }
        $table->head[] = get_string('attemptstate', 'quiz');
        $table->align[] = 'left';
        $table->size[] = '';
        if ($viewobj->markcolumn) {
            $table->head[] = get_string('marks', 'quiz') . ' / ' .
                quiz_format_grade($quiz, $quiz->sumgrades);
            $table->size[] = '';
        }
        if ($viewobj->gradecolumn) {
            $table->head[] = get_string('grade', 'quiz') . ' / ' .
                quiz_format_grade($quiz, $quiz->grade);
            $table->size[] = '';
        }
        if ($viewobj->canreviewmine) {
            $table->head[] = get_string('review', 'quiz');
            $table->size[] = '';
        }
        if ($viewobj->feedbackcolumn) {
            $table->head[] = get_string('feedback', 'quiz');
            $table->align[] = 'left';
            $table->size[] = '';
        }

        // One row for each attempt.
        foreach ($viewobj->attemptobjs as $attemptobj) {
            $attemptoptions = $attemptobj->get_display_options(true);
            $row = array();

            // Add the attempt number.
            if ($viewobj->attemptcolumn) {
                if ($attemptobj->is_preview()) {
                    $row[] = get_string('preview', 'quiz');
                } else {
                    $row[] = $attemptobj->get_attempt_number();
                }
            }

            $row[] = $this->attempt_state($attemptobj);

            if ($viewobj->markcolumn) {
                if (
                    $attemptoptions->marks >= question_display_options::MARK_AND_MAX &&
                    $attemptobj->is_finished()
                ) {
                    $row[] = quiz_format_grade($quiz, $attemptobj->get_sum_marks());
                } else {
                    $row[] = '';
                }
            }

            // Ouside the if because we may be showing feedback but not grades.
            $attemptgrade = quiz_rescale_grade($attemptobj->get_sum_marks(), $quiz, false);

            if ($viewobj->gradecolumn) {
                if (
                    $attemptoptions->marks >= question_display_options::MARK_AND_MAX &&
                    $attemptobj->is_finished()
                ) {

                    // Highlight the highest grade if appropriate.
                    if (
                        $viewobj->overallstats && !$attemptobj->is_preview()
                        && $viewobj->numattempts > 1 && !is_null($viewobj->mygrade)
                        && $attemptobj->get_state() == quiz_attempt::FINISHED
                        && $attemptgrade == $viewobj->mygrade
                        && $quiz->grademethod == QUIZ_GRADEHIGHEST
                    ) {
                        $table->rowclasses[$attemptobj->get_attempt_number()] = 'bestrow';
                    }

                    $row[] = quiz_format_grade($quiz, $attemptgrade);
                } else {
                    $row[] = '';
                }
            }

            if ($viewobj->canreviewmine) {
                $row[] = $viewobj->accessmanager->make_review_link(
                    $attemptobj->get_attempt(),
                    $attemptoptions,
                    $this
                );
            }

            if ($viewobj->feedbackcolumn && $attemptobj->is_finished()) {
                if ($attemptoptions->overallfeedback) {
                    $row[] = quiz_feedback_for_grade($attemptgrade, $quiz, $context);
                } else {
                    $row[] = '';
                }
            }

            if ($attemptobj->is_preview()) {
                $table->data['preview'] = $row;
            } else {
                $table->data[$attemptobj->get_attempt_number()] = $row;
            }
        } // End. of loop over attempts.

        $output = '';
        $output .= $this->view_table_heading();
        $output .= html_writer::start_tag('div', array('class' => 'table-overflow mb-4'));
        $output .= html_writer::table($table);
        $output .= html_writer::end_tag('div');
        return $output;
    }

    /*
     * View Page
     */
    /**
     * Generates the view page
     *
     * @param stdClass $course the course settings row from the database.
     * @param stdClass $quiz the quiz settings row from the database.
     * @param stdClass $cm the course_module settings row from the database.
     * @param context_module $context the quiz context.
     * @param mod_quiz_view_object $viewobj
     * @return string HTML to display
     */
    public function view_page($course, $quiz, $cm, $context, $viewobj) {
        $output = '';

        $output .= $this->view_page_tertiary_nav($viewobj);
        $output .= $this->view_information($quiz, $cm, $context, $viewobj->infomessages);
        $output .= $this->view_table($quiz, $context, $viewobj);
        $output .= $this->view_result_info($quiz, $context, $cm, $viewobj);
        $output .= $this->box($this->view_page_buttons($viewobj), 'rui-quizattempt');
        return $output;
    }

    /**
     * Work out, and render, whatever buttons, and surrounding info, should appear
     * at the end of the review page.
     *
     * @param mod_quiz_view_object $viewobj the information required to display the view page.
     * @return string HTML to output.
     */
    public function view_page_buttons(mod_quiz_view_object $viewobj) {
        $output = '';

        if (!$viewobj->quizhasquestions) {
            $output .= html_writer::div(
                $this->notification(get_string('noquestions', 'quiz'), 'warning', false),
                'text-left mb-3'
            );
        }
        $output .= $this->access_messages($viewobj->preventmessages);

        if ($viewobj->showbacktocourse) {
            $output .= $this->single_button(
                $viewobj->backtocourseurl,
                get_string('backtocourse', 'quiz'),
                'get',
                array('class' => 'rui-quiz-continuebutton')
            );
        }

        return $output;
    }


    /**
     * Generates the view attempt button
     *
     * @param string $buttontext the label to display on the button.
     * @param moodle_url $url The URL to POST to in order to start the attempt.
     * @param mod_quiz_preflight_check_form $preflightcheckform deprecated.
     * @param bool $popuprequired whether the attempt needs to be opened in a pop-up.
     * @param array $popupoptions the options to use if we are opening a popup.
     * @return string HTML fragment.
     */
    public function start_attempt_button(
        $buttontext,
        moodle_url $url,
        mod_quiz_preflight_check_form $preflightcheckform = null,
        $popuprequired = false,
        $popupoptions = null
    ) {

        if (is_string($preflightcheckform)) {
            // Calling code was not updated since the API change.
            debugging('The third argument to start_attempt_button should now be the ' .
                'mod_quiz_preflight_check_form from ' .
                'quiz_access_manager::get_preflight_check_form, not a warning message string.');
        }

        $button = new single_button($url, $buttontext, '', true);
        $button->class .= 'rui-quizstartbuttondiv quizstartbuttondiv mb-3';
        if ($popuprequired) {
            $button->class .= ' quizsecuremoderequired';
        }

        $popupjsoptions = null;
        if ($popuprequired && $popupoptions) {
            $action = new popup_action('click', $url, 'popup', $popupoptions);
            $popupjsoptions = $action->get_js_options();
        }

        if ($preflightcheckform) {
            $checkform = $preflightcheckform->render();
        } else {
            $checkform = null;
        }

        $this->page->requires->js_call_amd(
            'mod_quiz/preflightcheck',
            'init',
            array(
                '.quizstartbuttondiv [type=submit]',
                get_string('startattempt', 'quiz'),
                '#mod_quiz_preflight_form',
                $popupjsoptions
            )
        );

        return $this->render($button) . $checkform;
    }

    /**
     * Outputs the table containing data from summary data array
     *
     * @param array $summarydata contains row data for table
     * @param int $page contains the current page number
     */
    public function review_summary_table($summarydata, $page) {
        $summarydata = $this->filter_review_summary_table($summarydata, $page);
        if (empty($summarydata)) {
            return '';
        }

        $output = '';

        $output .= html_writer::start_tag('div', array('class' => 'rui-summary-table'));

        $output .= html_writer::start_tag('div', array('class' => 'rui-info-container rui-quizreviewsummary'));

        foreach ($summarydata as $rowdata => $val) {

            $csstitle = $rowdata;

            if ($val['title'] instanceof renderable) {
                $title = $this->render($val['title']);
            } else {
                $title = $val['title'];
            }

            if ($val['content'] instanceof renderable) {
                $content = $this->render($val['content']);
            } else {
                $content = $val['content'];
            }

            if ($val['title'] instanceof renderable) {
                $output .= html_writer::tag(
                    'div',
                    html_writer::tag('h5', $title, array('class' => 'rui-infobox-title')) .
                        html_writer::tag('div', $content, array('class' => 'rui-infobox-content--small')),
                    array('class' => 'rui-infobox rui-infobox--avatar')
                );
            } else {
                $output .= html_writer::tag(
                    'div',
                    html_writer::tag('h5', $title, array('class' => 'rui-infobox-title')) .
                        html_writer::tag('div', $content, array('class' => 'rui-infobox-content--small')),
                    array('class' => 'rui-infobox rui-infobox--' . strtolower(str_replace(' ', '', $csstitle)))
                );
            }
        }

        $output .= html_writer::end_tag('div');
        $output .= html_writer::end_tag('div');

        return $output;
    }


    /**
     * Creates any controls a the page should have.
     *
     * @param quiz_attempt $attemptobj
     */
    public function summary_page_controls($attemptobj) {
        $output = '';

        // Return to place button.
        if ($attemptobj->get_state() == quiz_attempt::IN_PROGRESS) {
            $button = new single_button(
                new moodle_url($attemptobj->attempt_url(null, $attemptobj->get_currentpage())),
                get_string('returnattempt', 'quiz')
            );
            $output .= $this->container($this->container(
                $this->render($button),
                'rui-controls'
            ), 'rui-submitbtns rui-submitbtns--back');
        }

        // Finish attempt button.
        $options = array(
            'attempt' => $attemptobj->get_attemptid(),
            'finishattempt' => 1,
            'timeup' => 0,
            'slots' => '',
            'cmid' => $attemptobj->get_cmid(),
            'sesskey' => sesskey(),
        );

        $button = new single_button(
            new moodle_url($attemptobj->processattempt_url(), $options),
            get_string('submitallandfinish', 'quiz'),
            null,
            true
        );
        $button->id = 'responseform';
        if ($attemptobj->get_state() == quiz_attempt::IN_PROGRESS) {
            $button->add_action(
                new confirm_action(
                    get_string('confirmclose', 'quiz'),
                    null,
                    get_string('submitallandfinish', 'quiz')
                )
            );
        }
        $button->primary = true;

        $duedate = $attemptobj->get_due_date();

        $output .= $this->countdown_timer($attemptobj, time());

        $message = '';
        if ($attemptobj->get_state() == quiz_attempt::OVERDUE) {
            $message = get_string('overduemustbesubmittedby', 'quiz', userdate($duedate));
            $output .= '<div class="alert alert-warning d-flex align-items-center">
            <svg class="mr-2" width="20" height="20" fill="none" viewBox="0 0 24 24">
            <path stroke="currentColor"
                stroke-linecap="round"
                stroke-linejoin="round"
                stroke-width="2"
                d="M4.9522 16.3536L10.2152 5.85658C10.9531 4.38481 13.0539 4.3852 13.7913
                5.85723L19.0495 16.3543C19.7156 17.6841 18.7487 19.25 17.2613 19.25H6.74007C5.25234
                19.25 4.2854 17.6835 4.9522 16.3536Z">
            </path>
            <path stroke="currentColor"
                stroke-linecap="round"
                stroke-linejoin="round"
                stroke-width="2"
                d="M12 10V12">
            </path>
            <circle cx="12" cy="16" r="1" fill="currentColor"></circle>
            </svg>' . $message . '</div>';
        } else if ($duedate) {
            $message = get_string('mustbesubmittedby', 'quiz', userdate($duedate));
            $output .= '<div class="alert alert-info d-flex align-items-center">
            <svg class="mr-2"
                width="20"
                height="20"
                fill="none"
                viewBox="0 0 24 24">
            <path stroke="currentColor"
                stroke-linecap="round"
                stroke-linejoin="round"
                stroke-width="2"
                d="M4.9522 16.3536L10.2152 5.85658C10.9531 4.38481 13.0539 4.3852 13.7913
                5.85723L19.0495 16.3543C19.7156 17.6841 18.7487 19.25 17.2613 19.25H6.74007C5.25234
                19.25 4.2854 17.6835 4.9522 16.3536Z">
            </path>
            <path stroke="currentColor"
                stroke-linecap="round"
                stroke-linejoin="round"
                stroke-width="2"
                d="M12 10V12">
            </path>
            <circle cx="12" cy="16" r="1" fill="currentColor"></circle></svg><span class="col">'
            . $message .
            '</span></div>';
        }

        $output .= $this->container($this->container($this->render($button), 'rui-controls'), 'rui-submitbtns');

        return $output;
    }



    /*
     * Summary Page
     */
    /**
     * Create the summary page
     *
     * @param quiz_attempt $attemptobj
     * @param mod_quiz_display_options $displayoptions
     */
    public function summary_page($attemptobj, $displayoptions) {
        $output = '';
        $output .= $this->header();
        $output .= $this->during_attempt_tertiary_nav($attemptobj->view_url());
        $output .= $this->heading(format_string($attemptobj->get_quiz_name()));
        $output .= $this->heading(get_string('summaryofattempt', 'quiz'), 4, array('class' => 'mt-3 mb-2'));
        $output .= $this->summary_table($attemptobj, $displayoptions);
        $output .= $this->summary_page_controls($attemptobj);
        $output .= $this->footer();
        return $output;
    }

    /**
     * Generates the table of summarydata
     *
     * @param quiz_attempt $attemptobj
     * @param mod_quiz_display_options $displayoptions
     */
    public function summary_table($attemptobj, $displayoptions) {
        // Prepare the summary table header.
        $table = new html_table();
        $table->attributes['class'] = 'generaltable quizsummaryofattempt';
        $table->head = array(get_string('question', 'quiz'), get_string('status', 'quiz'));
        $table->align = array('left', 'left');
        $table->size = array('', '');
        $markscolumn = $displayoptions->marks >= question_display_options::MARK_AND_MAX;
        if ($markscolumn) {
            $table->head[] = get_string('marks', 'quiz');
            $table->align[] = 'left';
            $table->size[] = '';
        }
        $tablewidth = count($table->align);
        $table->data = array();

        // Get the summary info for each question.
        $slots = $attemptobj->get_slots();
        foreach ($slots as $slot) {
            // Add a section headings if we need one here.
            $heading = $attemptobj->get_heading_before_slot($slot);

            if ($heading !== null) {
                // There is a heading here.
                $rowclasses = 'quizsummaryheading';
                if ($heading) {
                    $heading = format_string($heading);
                } else if (count($attemptobj->get_quizobj()->get_sections()) > 1) {
                    // If this is the start of an unnamed section, and the quiz has more
                    // than one section, then add a default heading.
                    $heading = get_string('sectionnoname', 'quiz');
                    $rowclasses .= ' dimmed_text';
                }
                $cell = new html_table_cell(format_string($heading));
                $cell->header = true;
                $cell->colspan = $tablewidth;
                $table->data[] = array($cell);
                $table->rowclasses[] = $rowclasses;
            }

            // Don't display information items.
            if (!$attemptobj->is_real_question($slot)) {
                continue;
            }

            $flag = '';

            // Real question, show it.
            if ($attemptobj->is_question_flagged($slot)) {
                // Quiz has custom JS manipulating these image tags - so we can't use the pix_icon method here.
                $flag = '<svg class="ml-2"
                    width="20"
                    height="20"
                    viewBox="0 0 24 24"
                    fill="none"
                    xmlns="http://www.w3.org/2000/svg"
                >
                <path d="M4.75 5.75V19.25"
                    stroke="currentColor"
                    stroke-width="2"
                    stroke-linecap="round"
                    stroke-linejoin="round">
                </path>
                <path d="M4.75 15.25V5.75C4.75 5.75 6 4.75 9 4.75C12 4.75 13.5 6.25 16 6.25C18.5
                6.25 19.25 5.75 19.25 5.75L15.75 10.5L19.25 15.25C19.25 15.25 18.5 16.25 16
                16.25C13.5 16.25 11.5 14.25 9 14.25C6.5 14.25 4.75 15.25 4.75 15.25Z"
                    stroke="currentColor"
                    stroke-width="2"
                    stroke-linecap="round"
                    stroke-linejoin="round"></path>
                </svg>';
            }
            if ($attemptobj->can_navigate_to($slot)) {
                $row = array(
                    html_writer::link(
                        $attemptobj->attempt_url($slot),
                        $attemptobj->get_question_number($slot) . $flag
                    ),
                    $attemptobj->get_question_status($slot, $displayoptions->correctness)
                );
            } else {
                $row = array(
                    $attemptobj->get_question_number($slot) . $flag,
                    $attemptobj->get_question_status($slot, $displayoptions->correctness)
                );
            }
            if ($markscolumn) {
                $row[] = $attemptobj->get_question_mark($slot);
            }
            $table->data[] = $row;
            $table->rowclasses[] = 'quizsummary' . $slot . ' ' . $attemptobj->get_question_state_class(
                $slot,
                $displayoptions->correctness
            );
        }

        // Print the summary table.
        $output = html_writer::table($table);

        return $output;
    }
}

require_once($CFG->dirroot . "/question/engine/renderer.php");
class theme_iomadmoon_core_question_renderer extends core_question_renderer {
    /**
     * Generate the information bit of the question display that contains the
     * metadata like the question number, current state, and mark.
     * @param question_attempt $qa the question attempt to display.
     * @param qbehaviour_renderer $behaviouroutput the renderer to output the behaviour
     *      specific parts.
     * @param qtype_renderer $qtoutput the renderer to output the question type
     *      specific parts.
     * @param question_display_options $options controls what should and should not be displayed.
     * @param string|null $number The question number to display. 'i' is a special
     *      value that gets displayed as Information. Null means no number is displayed.
     * @return HTML fragment.
     */
    protected function info(
        question_attempt $qa,
        qbehaviour_renderer $behaviouroutput,
        qtype_renderer $qtoutput,
        question_display_options $options,
        $number
    ) {
        $output = '';
        $output .= '<div class="d-flex align-items-center flex-wrap mb-sm-2 mb-md-0">' .
            $this->number($number) .
            '<div class="d-inline-flex align-items-center flex-wrap">' .
            $this->status($qa, $behaviouroutput, $options) .
            $this->mark_summary($qa, $behaviouroutput, $options) .
            '</div></div>';
        $output .= '<div>' .
            $this->question_flag($qa, $options->flags) .
            $this->edit_question_link($qa, $options) .
            '</div>';
        return $output;
    }

    /**
     * Generate the display of the question number.
     * @param string|null $number The question number to display. 'i' is a special
     *      value that gets displayed as Information. Null means no number is displayed.
     * @return HTML fragment.
     */
    protected function number($number) {
        if (trim($number) === '') {
            return '';
        }
        $numbertext = '';
        if (trim($number) === 'i') {
            $numbertext = get_string('information', 'question');
        } else {
            $numbertext = get_string(
                'questionx',
                'question',
                html_writer::tag('span', $number, array('class' => 'rui-qno'))
            );
        }
        return html_writer::tag('h4', $numbertext, array('class' => 'card-title w-100 mb-2'));
    }


    /**
     * Generate the display of the status line that gives the current state of
     * the question.
     * @param question_attempt $qa the question attempt to display.
     * @param qbehaviour_renderer $behaviouroutput the renderer to output the behaviour
     *      specific parts.
     * @param question_display_options $options controls what should and should not be displayed.
     * @return HTML fragment.
     */
    protected function status(
        question_attempt $qa,
        qbehaviour_renderer $behaviouroutput,
        question_display_options $options
    ) {
        return html_writer::tag(
            'div',
            $qa->get_state_string($options->correctness),
            array('class' => 'state mr-2 my-2')
        );
    }

    /**
     * Render the question flag, assuming $flagsoption allows it.
     *
     * @param question_attempt $qa the question attempt to display.
     * @param int $flagsoption the option that says whether flags should be displayed.
     */
    protected function question_flag(question_attempt $qa, $flagsoption) {
        global $CFG;

        $divattributes = array('class' => 'questionflag mx-1 d-none');

        switch ($flagsoption) {
            case question_display_options::VISIBLE:
                $flagcontent = $this->get_flag_html($qa->is_flagged());
                break;

            case question_display_options::EDITABLE:
                $id = $qa->get_flag_field_name();
                $checkboxattributes = array(
                    'type' => 'checkbox',
                    'id' => $id . 'checkbox',
                    'name' => $id,
                    'value' => 1,
                );
                if ($qa->is_flagged()) {
                    $checkboxattributes['checked'] = 'checked';
                }
                $postdata = question_flags::get_postdata($qa);

                $flagcontent = html_writer::empty_tag(
                    'input',
                    array('type' => 'hidden', 'name' => $id, 'value' => 0)
                ) .
                    html_writer::empty_tag('input', $checkboxattributes) .
                    html_writer::empty_tag(
                        'input',
                        array('type' => 'hidden', 'value' => $postdata, 'class' => 'questionflagpostdata')
                    ) .
                    html_writer::tag(
                        'label',
                        $this->get_flag_html($qa->is_flagged(), $id . 'img'),
                        array('id' => $id . 'label', 'for' => $id . 'checkbox')
                    ) . "\n";

                $divattributes = array(
                    'class' => 'questionflag mb-sm-2 mb-md-0 mx-md-2 editable d-inline-flex',
                    'aria-atomic' => 'true',
                    'aria-relevant' => 'text',
                    'aria-live' => 'assertive',
                );

                break;

            default:
                $flagcontent = '';
        }

        return html_writer::nonempty_tag('div', $flagcontent, $divattributes);
    }


    protected function edit_question_link(question_attempt $qa, question_display_options $options) {
        global $CFG;

        if (empty($options->editquestionparams)) {
            return '';
        }

        $params = $options->editquestionparams;
        if ($params['returnurl'] instanceof moodle_url) {
            $params['returnurl'] = $params['returnurl']->out_as_local_url(false);
        }
        $params['id'] = $qa->get_question_id();
        $editurl = new moodle_url('/question/bank/editquestion/question.php', $params);

        $icon = '<svg width="19" height="19" fill="none" viewBox="0 0 24 24">
        <path stroke="currentColor"
            stroke-linecap="round"
            stroke-linejoin="round"
            stroke-width="2"
            d="M4.75 19.25L9 18.25L18.2929 8.95711C18.6834 8.56658 18.6834
            7.93342 18.2929 7.54289L16.4571 5.70711C16.0666 5.31658 15.4334
            5.31658 15.0429 5.70711L5.75 15L4.75 19.25Z">
        </path>
        <path stroke="currentColor"
            stroke-linecap="round"
            stroke-linejoin="round"
            stroke-width="2"
            d="M19.25 19.25H13.75">
        </path>
        </svg>';

        return html_writer::link($editurl, $icon,
        array('class' => 'btn btn-icon btn-secondary editquestion line-height-1 ml-sm-2'));
    }
}

require_once($CFG->dirroot . "/question/type/multichoice/renderer.php");
class theme_iomadmoon_qtype_multichoice_single_renderer extends qtype_multichoice_single_renderer {
    public function after_choices(question_attempt $qa, question_display_options $options) {
        // Only load the clear choice feature if it's not read only.
        if ($options->readonly) {
            return '';
        }

        $question = $qa->get_question();
        $response = $question->get_response($qa);
        $hascheckedchoice = false;
        foreach ($question->get_order($qa) as $value => $ansid) {
            if ($question->is_choice_selected($response, $value)) {
                $hascheckedchoice = true;
                break;
            }
        }

        $clearchoiceid = $this->get_input_id($qa, -1);
        $clearchoicefieldname = $qa->get_qt_field_name('clearchoice');
        $clearchoiceradioattrs = [
            'type' => $this->get_input_type(),
            'name' => $qa->get_qt_field_name('answer'),
            'id' => $clearchoiceid,
            'value' => -1,
            'class' => 'sr-only',
            'aria-hidden' => 'true'
        ];
        $clearchoicewrapperattrs = [
            'id' => $clearchoicefieldname,
            'class' => 'qtype_multichoice_clearchoice',
        ];

        // When no choice selected during rendering, then hide the clear choice option.
        // We are using .sr-only and aria-hidden together so while the element is hidden
        // from both the monitor and the screen-reader, it is still tabbable.
        $linktabindex = 0;
        if (!$hascheckedchoice && $response == -1) {
            $clearchoicewrapperattrs['class'] .= ' sr-only';
            $clearchoicewrapperattrs['aria-hidden'] = 'true';
            $clearchoiceradioattrs['checked'] = 'checked';
            $linktabindex = -1;
        }
        // Adds an hidden radio that will be checked to give the impression the choice has been cleared.
        $clearchoiceradio = html_writer::empty_tag('input', $clearchoiceradioattrs);
        $clearchoice = html_writer::link(
            '#',
            get_string('clearchoice', 'qtype_multichoice'),
            ['tabindex' => $linktabindex, 'role' => 'button', 'class' => 'btn btn-sm btn-outline-danger']
        );
        $clearchoiceradio .= html_writer::label($clearchoice, $clearchoiceid);

        // Now wrap the radio and label inside a div.
        $result = html_writer::tag('div', $clearchoiceradio, $clearchoicewrapperattrs);

        // Load required clearchoice AMD module.
        $this->page->requires->js_call_amd(
            'qtype_multichoice/clearchoice',
            'init',
            [$qa->get_outer_question_div_unique_id(), $clearchoicefieldname]
        );

        return $result;
    }
}
