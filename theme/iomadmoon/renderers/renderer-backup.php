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


// Backup.
require_once($CFG->dirroot . "/backup/util/ui/renderer.php");
class theme_iomadmoon_core_backup_renderer extends core_backup_renderer {

    /**
     * Renderers a progress bar for the backup or restore given the items that make it up.
     *
     * @param array $items An array of items
     * @return string
     */
    public function progress_bar(array $items) {
        foreach ($items as &$item) {
            $text = $item['text'];
            unset($item['text']);
            if (array_key_exists('link', $item)) {
                $link = $item['link'];
                unset($item['link']);
                $item = html_writer::link($link, $text, $item);
            } else {
                $item = html_writer::tag('span', $text, $item);
            }
        }
        return html_writer::tag('div', join($items), array('class' => 'rui-backup-progress wrapper-fw'));
    }

    /**
     * The backup and restore pages may display a log (if any) in a scrolling box.
     *
     * @param string $loghtml Log content in HTML format
     * @return string HTML content that shows the log
     */
    public function log_display($loghtml) {
        $out = html_writer::start_div('backup_log wrapper-fw');
        $out .= $this->output->heading(get_string('backuplog', 'backup'));
        $out .= html_writer::start_div('backup_log_contents wrapper-fw mt-4');
        $out .= $loghtml;
        $out .= html_writer::end_div();
        $out .= html_writer::end_div();
        return $out;
    }
}
