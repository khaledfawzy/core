<?php

namespace FormTools;

use Exception;


class Upgrade
{
    public static function upgrade()
    {
        $current_version_date = Core::getReleaseDate();
        $last_version_date_in_db = Settings::get("release_date");

        $is_upgraded = false;
        $success = true;
        $error_msg = "";

        // any time the version changes, update the list of hooks in the DB
        if ($current_version_date > $last_version_date_in_db) {
            Hooks::updateAvailableHooks();
        }

        // if the files have been updated but the DB is older, the user is upgrading
        if ($current_version_date > $last_version_date_in_db) {
            if ($current_version_date <= 20180318) {
                list ($success, $error_msg) = self::upgradeTo3_0_0();

                // additional patch for old alpha/beta versions. This is benign & can be executed multiple times
                self::patchFieldTypeOptionListSettingMapping();
            }

            if ($success) {
                Settings::set(array(
                    "release_date" => $current_version_date,
                    "program_version" => Core::getCoreVersion(),
                    "release_type" => Core::getReleaseType()
                ));

                $is_upgraded = true;
                $success = true;
            }
        }

        return array(
            "upgraded" => $is_upgraded,
            "success" => $success,
            "error_msg" => $error_msg
        );
    }


    /**
     * Handles upgrading from FT2 2.2.5, 2.2.6 or 2.2.7 to 3.0.0.
     *
     * These methods can safely be executed multiple times (but should still only fire once).
     */
    private static function upgradeTo3_0_0()
    {
        $db = Core::$db;

        $success = true;
        $error_msg = "";
        try {
            $db->query("
                ALTER TABLE {PREFIX}forms CHANGE add_submission_button_label add_submission_button_label VARCHAR(255)
            ");
            $db->execute();

            General::deleteColumnIfExists("modules", "is_premium");
            Settings::set(array(
                "edit_submission_onload_resources" => Installation::getEditSubmissionOnloadResources()
            ), "core");

            // reset all core field types to their factory defaults
            FieldTypes::resetFieldTypes();

        } catch (Exception $e) {
            $success = false;
            $error_msg = $e->getMessage();
        }

        return array($success, $error_msg);
    }

    /**
     * FT3 alpha/betas were failing to map the ID of the field type setting ID for select/checkboxes/multi-select/radios
     * that houses the option list info to the original field. This caused a few minor issues in the UI including
     * adding an external form. See: https://github.com/formtools/core/issues/166 (indirect issue)
     */
    private static function patchFieldTypeOptionListSettingMapping ()
    {
        $db = Core::$db;

        $field_types = FieldTypes::get(true);
        foreach ($field_types as $field_type) {
            if (!in_array($field_type["field_type_identifier"], array("dropdown", "multi_select_dropdown", "radio_buttons", "checkboxes"))) {
                continue;
            }

            // In the original data, the setting that's going to store the option list has a "use_for_option_list_map"
            // boolean, but that's not available here. Luckily all 4 of these field types have a field_type value of
            // "option_list_or_form_field" so we use that to locate the DB record here
            $setting_id = null;
            foreach ($field_type["settings"] as $setting_info) {
                if ($setting_info["field_type"] == "option_list_or_form_field") {
                    $setting_id = $setting_info["setting_id"];
                    break;
                }
            }

            $db->query("
                UPDATE {PREFIX}field_types
                SET    raw_field_type_map_multi_select_id = :raw_field_type_map_multi_select_id
                WHERE  field_type_id = :field_type_id
            ");
            $db->bind("raw_field_type_map_multi_select_id", $setting_id);
            $db->bind("field_type_id", $field_type["field_type_id"]);
            $db->execute();
        }
    }
}
