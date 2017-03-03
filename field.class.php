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
 * Associated profile field.
 *
 * @package    profilefield_associated
 * @copyright  2015 onwards Shamim Rezaie {@link http://foodle.org}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Class profile_field_associated
 *
 * @copyright  2015 onwards Shamim Rezaie {@link http://foodle.org}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class profile_field_associated extends profile_field_base {

    /**
     * Add fields for editing a associated profile field.
     * @param MoodleQuickForm $mform
     */
    public function edit_field_add($mform) {
        $associatedfield = $this->field->param1;
        $useoriginal = $this->field->param2;

        if ($useoriginal) {
            // Create the form field only if the original field does not exist, i.e. if we are on the signup page.
            if (!$mform->elementExists($associatedfield)) {
                // Create the form field.
                $mform->addElement('text', $this->inputname, format_string($this->field->name));
                $mform->setType($this->inputname, PARAM_TEXT);
            }
        } else {
            // Create the form field.
            $mform->addElement('text', $this->inputname, format_string($this->field->name));
            $mform->setType($this->inputname, PARAM_TEXT);

            // Remove the original field if it exists, i.e. we are not on the signup page.
            if ($mform->elementExists($associatedfield)) {
                $mform->removeElement($associatedfield);
            }
        }
    }

    /**
     * Tweaks the edit form
     * @param MoodleQuickForm $mform instance of the moodleform class
     * @return bool
     */
    public function edit_after_data($mform) {
        if ($this->field->visible == PROFILE_VISIBLE_NONE
          and !has_capability('moodle/user:update', context_system::instance())) {

            $associatedfield = $this->field->param1;
            if ($mform->elementExists($associatedfield)) {
                $mform->removeElement($associatedfield);
            }
        }
        return parent::edit_after_data($mform);
    }

    /**
     * Saves the data coming from form
     * @param stdClass $usernew data coming from the form
     * @return mixed returns data id if success of db insert/update, false on fail, 0 if not permitted
     */
    public function edit_save_data($usernew) {
        global $DB;

        $associatedfield = $this->field->param1;
        $useoriginal = $this->field->param2;

        if ($useoriginal && isset($usernew->{$associatedfield})) {
            $usernew->{$this->inputname} = $usernew->{$associatedfield};
        }

        if (!isset($usernew->{$this->inputname})) {
            // Field not present in form, probably locked and invisible - skip it.
            return null;
        }

        if (!$useoriginal) {    // Preventing 1 redundant update.
            $DB->set_field('user', $this->field->param1, $usernew->{$this->inputname}, array('id' => $usernew->id));
        }

        return parent::edit_save_data($usernew);
    }

    /**
     * Validate the form field from profile page
     *
     * @param stdClass $usernew
     * @return  string  contains error message otherwise null
     */
    public function edit_validate_field($usernew) {
        global $DB;

        $errors = array();
        $associatedfield = $this->field->param1;
        $useoriginal = $this->field->param2;

        // Get input value.
        if ($useoriginal) {
            $value = isset($usernew->{$associatedfield}) ? $usernew->{$associatedfield} : '';
        } else {
            $value = isset($usernew->{$this->inputname}) ? $usernew->{$this->inputname} : '';
        }

        // Check for uniqueness of data if required.
        if ($this->is_unique() && (($value !== '') || $this->is_required())) {
            $data = $DB->get_records('user', array($associatedfield => $value), '', 'id');
            if ($data) {
                $existing = false;
                foreach ($data as $v) {
                    if ($v->id == $usernew->id) {
                        $existing = true;
                        break;
                    }
                }
                if (!$existing) {
                    if (isset($usernew->{$associatedfield})) {
                        $errors[$associatedfield] = get_string('valuealreadyused');
                    } else {
                        $errors[$this->inputname] = get_string('valuealreadyused');
                    }
                }
            }
        }
        return $errors;
    }

    /**
     * Sets the default data for the field in the form object
     * @param  MoodleQuickForm $mform instance of the moodleform class
     */
    public function edit_field_set_default($mform) {
        $associatedfield = $this->field->param1;

        if (!empty($this->field->defaultdata) && empty($this->userid)) {
            if ($mform->elementExists($associatedfield)) {
                $mform->setDefault($associatedfield, $this->field->defaultdata);
            } else {
                $mform->setDefault($this->inputname, $this->field->defaultdata);
            }
        }
    }

    /**
     * Sets the required flag for the field in the form object
     *
     * @param MoodleQuickForm $mform instance of the moodleform class
     */
    public function edit_field_set_required($mform) {
        global $USER;

        $associatedfield = $this->field->param1;
        $useoriginal = $this->field->param2;

        if ($this->is_required() && ($this->userid == $USER->id)) {
            if ($useoriginal && $mform->elementExists($associatedfield)) {
                $mform->addRule($associatedfield, get_string('required'), 'required', null, 'client');
            } else {
                $mform->addRule($this->inputname, get_string('required'), 'required', null, 'client');
            }
        }
    }

    /**
     * HardFreeze the field if locked.
     * @param MoodleQuickForm $mform instance of the moodleform class
     */
    public function edit_field_set_locked($mform) {

        $associatedfield = $this->field->param1;

        if ($this->is_locked() and !has_capability('moodle/user:update', context_system::instance())) {
            if ($mform->elementExists($this->inputname)) {
                $mform->hardFreeze($this->inputname);
                $mform->setConstant($this->inputname, $this->data);
            }
            if ($mform->elementExists($associatedfield)) {
                $mform->hardFreeze($associatedfield);
                $mform->setConstant($associatedfield, $this->data);
            }
        }
    }

    /**
     * Accessor method: Load the field record and user data associated with the
     * object's fieldid and userid
     */
    public function load_data() {
        global $DB;

        // Load the field object.
        if (($this->fieldid == 0) or (!($field = $DB->get_record('user_info_field', array('id' => $this->fieldid))))) {
            $this->field = null;
            $this->inputname = '';
        } else {
            $this->field = $field;
            $this->inputname = 'profile_field_'.$field->shortname;
        }

        if (!empty($this->field)) {
            if ($data = $DB->get_field('user', $this->field->param1, array('id' => $this->userid))) {
                $this->data = $data;
            } else {
                $this->data = null;
            }
            $this->dataformat = FORMAT_HTML;
        } else {
            $this->data = null;
        }
    }
}
