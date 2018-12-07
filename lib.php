<?php
/**
* BAClickToReveal lib.php
*
* @copyright  Bright Alley Knowledge and Learning
* @author     Mannes Brak
* @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
*/

function qtype_ubhotspots_pluginfile($course, $cm, $context, $filearea, $args, $forcedownload, array $options=array()) {
    // Make sure the filearea is one of those used by the plugin.
    if ($filearea !== 'image') {
        return false;
    }

    // Make sure the user is logged in and has access to the module (plugins that are not course modules should leave out the 'cm' part).
    require_login($course, true, $cm);

    // Leave this line out if you set the itemid to null in make_pluginfile_url (set $itemid to 0 instead).
    $itemid = array_shift($args); // The first item in the $args array.

    // Extract the filename / filepath from the $args array.
    $filename = array_pop($args); // The last item in the $args array.
    if (!$args) {
        $filepath = '/'; // $args is empty => the path is '/'
    } else {
        $filepath = '/'.implode('/', $args).'/'; // $args contains elements of the filepath
    }

    // Retrieve the file from the Files API.
    $fs = get_file_storage();
    $file = $fs->get_file($context->id, 'qtype_ubhotspots', $filearea, $itemid, $filepath, $filename);

    if (!$file) {
        return false; // The file does not exist.
    }

    // We can now send the file back to the browser - in this case with a cache lifetime of 1 day and no filtering.
    // From Moodle 2.3, use send_stored_file instead.
    send_stored_file($file, 86400, 0, $forcedownload, $options);
}