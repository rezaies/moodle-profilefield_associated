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
 * Associated profile field definition.
 *
 * @package    profilefield_associated
 * @copyright  2015 onwards Shamim Rezaie {@link http://foodle.org}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Class profile_define_associated
 *
 * @package    profilefield_associated
 * @copyright  2015 onwards Shamim Rezaie {@link http://foodle.org}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class profile_define_associated extends profile_define_base {

    /**
     * Add elements for creating/editing an associated profile field.
     * @param MoodleQuickForm $form
     */
    public function define_form_specific($form) {
        // Default data.
        $form->addElement('text', 'defaultdata', get_string('profiledefaultdata', 'admin'), 'size="50"');
        $form->setType('defaultdata', PARAM_TEXT);

        // Param 1 for associated type detemines which optional element should this be associated to.
        $targetoptions = [
            ''              => get_string('choose'),
            'city'          => get_string('city', ''),
            'country'       => get_string('country', ''),
            'url'           => get_string('webpage'),
            'icq'           => get_string('icqnumber'),
            'skype'         => get_string('skypeid'),
            'aim'           => get_string('aimid'),
            'yahoo'         => get_string('yahooid'),
            'msn'           => get_string('msnid'),
            'idnumber'      => get_string('idnumber', ''),
            'institution'   => get_string('institution', ''),
            'department'    => get_string('department', ''),
            'phone1'        => get_string('phone', ''),
            'phone2'        => get_string('phone2', ''),
            'address'       => get_string('address', '')
        ];
        $form->addElement('select', 'param1', get_string('associatedfield', 'profilefield_associated'), $targetoptions);
        $form->addRule('param1', get_string('required'), 'required', null, 'client');
        $form->setType('param1', PARAM_RAW);

        // Param 2 for associated type determines if this should replace the original field or not.
        $form->addElement('selectyesno', 'param2', get_string('useoriginal', 'profilefield_associated'));
        $form->setDefault('param2', 0); // Defaults to 'no'.
        $form->setType('param2', PARAM_INT);
    }

    /**
     * Alter form based on submitted or existing data
     * @param MoodleQuickForm $mform
     */
    public function define_after_data(&$mform) {
        $mform->addHelpButton('signup', 'signup', 'profilefield_associated');
        $mform->setDefault('signup', '1');
    }

    /**
     * Validate the data from the add/edit profile field form
     * that is specific to the current data type
     * @param stdClass $data
     * @param array $files
     * @return  array    associative array of error messages
     */
    public function define_validate_specific($data, $files) {
        global $DB;

        $error = parent::define_validate_specific($data, $files);

        $select = $DB->sql_compare_text('datatype') . " = ? AND " . $DB->sql_compare_text('param1') . " = ?";
        $fields = $DB->get_records_select('user_info_field', $select, array('associated', $data->param1));

        foreach ($fields as $field) {
            if ($field->id != $data->id) {
                $error['param1'] = get_string('dupplicateassociated', 'profilefield_associated');
            }
        }
        return $error;
    }
}
