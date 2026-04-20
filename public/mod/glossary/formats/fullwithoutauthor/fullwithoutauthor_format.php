<?php

use mod_glossary\output\renderer;

/**
 * Displays a glossary entry in full format but without the author information.
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
function glossary_show_entry_fullwithoutauthor(
    $course,
    $cm,
    $glossary,
    $entry,
    $mode = "",
    $hook = "",
    $printicons = 1,
    $aliases = true,
    $conceptheadinglevel = 3,
) {
    global $OUTPUT, $PAGE;

    if ($entry) {
        echo '<div class="glossarypost fullwithoutauthor">';
        /** @var renderer $renderer */
        $renderer = $PAGE->get_renderer('mod_glossary');
        echo $renderer->concept_entry_header($entry, $mode, $conceptheadinglevel, showlastedited: true);

        echo '<div class="entry">';
        glossary_print_entry_definition($entry, $glossary, $cm);
        glossary_print_entry_attachment($entry, $cm, 'html');
        if (core_tag_tag::is_enabled('mod_glossary', 'glossary_entries')) {
            echo $OUTPUT->tag_list(
                core_tag_tag::get_item_tags('mod_glossary', 'glossary_entries', $entry->id), null, 'glossary-tags');
        }
        echo '</div>';

        echo '<div class="entrylowersection">';
        glossary_print_entry_lower_section($course, $cm, $glossary, $entry, $mode, $hook, $printicons, $aliases);
        echo '</div>';

        echo "</div>";
    } else {
        echo html_writer::div(get_string('noentry', 'glossary'), 'text-center');
    }
}

/**
 * Display entries in the full, without author glossary format for printing.
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
function glossary_print_entry_fullwithoutauthor(
    $course,
    $cm,
    $glossary,
    $entry,
    $mode = "",
    $hook = "",
    $printicons = 1,
    $conceptheadinglevel = 3
) {
    // The print view for this format is exactly the normal view, so we use it.

    // Take out auto-linking in definitions in print view.
    $entry->definition = '<span class="nolink">' . $entry->definition . '</span>';

    // Call to view function (without icons, ratings and aliases) and return its result.
    glossary_show_entry_fullwithoutauthor($course, $cm, $glossary, $entry, $mode, $hook, false, false, $conceptheadinglevel);
}


