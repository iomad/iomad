<?php

use mod_glossary\output\renderer;

/**
 * Template function that can be used for displaying glossary entries on a different format.
 *
 * @param stdClass $course The course object.
 * @param stdClass $cm The course module object.
 * @param stdClass $glossary The glossary object.
 * @param stdClass $entry The glossary entry object.
 * @param string $mode The mode in which the entry is being displayed.
 * @param string $hook
 * @param int $printicons Whether to print editing icons.
 * @param bool $aliases Whether to show aliases popup.
 * @param int $conceptheadinglevel The heading level to use for rendering the concept within the heading element.
 * @return void
 * @package mod_glossary
 */
function glossary_show_entry_TEMPLATE(
    $course,
    $cm,
    $glossary,
    $entry,
    $mode = '',
    $hook = '',
    $printicons = 1,
    $aliases = true,
    $conceptheadinglevel = 3,
) {
    global $DB, $PAGE;

    if ($entry) {
        $user = $DB->get_record('user', ['id' => $entry->userid]);

        echo '<div class="glossarypost TEMPLATE">';

        // Use this function to show author's image and name along with the concept name.
        /** @var renderer $renderer */
        $renderer = $PAGE->get_renderer('mod_glossary');
        echo $renderer->concept_entry_header($entry, $mode, $conceptheadinglevel, $user, $course->id);

        // Use this code to show modification date.
        // Comments: Configuration not supported.
        echo get_string('lastedited') . ': ' . userdate($entry->timemodified) . '<br />';

        echo '<div class="entryattachment">';
        echo '</div>';

        echo '<div class="entry">';

        // Use this function to print the concept in a heading <h3>.
        // Comments: Configuration not supported.
        glossary_print_entry_concept($entry, headinglevel: $conceptheadinglevel);

        // Use this function to show the definition.
        // Comments: Configuration not supported.
        glossary_print_entry_definition($entry, $glossary, $cm);

        // Use this function to show the attachment. It'll be shown if necessary.
        glossary_print_entry_attachment($entry, $cm, 'html');

        // Use this function to show aliases, editing icons and ratings (all know as the 'lower section').
        // Comments: You can configure these parameters:
        // ----Define when to show the aliases popup. Use it only if you are really sure!
        // $aliases = true; // Values: true, false (Default: true).
        // ----Uncomment this line to avoid editing icons being shown. Use it only if you are really sure!
        // $printicons = false; // true/1, false/0.
        glossary_print_entry_lower_section($course, $cm, $glossary, $entry, $mode, $hook, $printicons, $aliases);

        echo '</div>';
        echo "</div>";
    } else {
        echo html_writer::div(get_string('noentry', 'glossary'), 'text-center');
    }
}

/**
 * Template function that can be used for displaying glossary entries for printing.
 *
 * @param stdClass $course The course object.
 * @param stdClass $cm The course module object.
 * @param stdClass $glossary The glossary object.
 * @param stdClass $entry The glossary entry object.
 * @param string $mode The mode in which the entry is being displayed.
 * @param string $hook
 * @param int $printicons Whether to print editing icons.
 * @param int $conceptheadinglevel The heading level to use for rendering the concept within the heading element.
 * @package mod_glossary
 */
function glossary_print_entry_TEMPLATE(
    $course,
    $cm,
    $glossary,
    $entry,
    $mode = '',
    $hook = '',
    $printicons = 1,
    $conceptheadinglevel = 3
) {
    // The print view for this format is exactly the normal view, so we use it.
    // Anyway, you can modify this to use your own print format!!

    // Take out auto-linking in definitions in print view.
    $entry->definition = '<span class="nolink">' . $entry->definition . '</span>';

    // Call to view function (without icons, ratings and aliases) and return its result.
    glossary_show_entry_TEMPLATE($course, $cm, $glossary, $entry, $mode, $hook, false, false, $conceptheadinglevel);
}


