<?php

function glossary_show_entry_TEMPLATE($course, $cm, $glossary, $entry, $mode='', $hook='', $printicons=1, $aliases=true) {
    global $CFG, $USER, $DB, $OUTPUT;


    $user = $DB->get_record('user', array('id'=>$entry->userid));
    $strby = get_string('writtenby', 'glossary');

    if ($entry) {

        echo '<table class="glossarypost TEMPLATE">';
        echo '<tr>';
        echo '<td class="entryheader">';

        //Use this function to show author's image
        //Comments: Configuration not supported
        echo $OUTPUT->user_picture($user, array('courseid'=>$course->id));

        //Line separator to show this template fine. :-)
        echo '<br />';

        //Use this code to show author's name
        //Comments: Configuration not supported
        $fullname = fullname($user);
        $by = new stdClass();
        $by->name = '<a href="'.$CFG->wwwroot.'/user/view.php?id='.$user->id.'&amp;course='.$course->id.'">'.$fullname.'</a>';
        $by->date = userdate($entry->timemodified);
        echo '<span class="author">'.get_string('bynameondate', 'forum', $by).'</span>' . '<br />';

        //Use this code to show modification date
        //Comments: Configuration not supported
        echo get_string('lastedited').': '. userdate($entry->timemodified) . '<br /></span>';

        //Use this function to show the approval button. It'll be shown if necessary
        //Comments: You can configure this parameters:
        //----Define where to show the approval button
        $approvalalign = 'right'; //Values: left, center and right (default right)
        //----Define if the approval button must be showed into a 100% width table
        $approvalinsidetable = true; //Values: true, false (default true)
        //Call the function
        glossary_print_entry_approval($cm, $entry, $mode, $approvalalign, $approvalinsidetable);

        //Line separator to show this template fine. :-)
        echo '<br />';

        echo '</td>';

        echo '<td class="entryattachment">';

        //Line separator to show this template fine. :-)
        echo "<br />\n";

        echo '</td></tr>';

        echo '<tr valign="top">';
        echo '<td class="entry">';

        //Use this function to print the concept in a heading <h3>
        //Comments: Configuration not supported
        glossary_print_entry_concept($entry);

        //Line separator not normally needed now.
        //echo "<br />\n";

        //Use this function to show the definition
        //Comments: Configuration not supported
        glossary_print_entry_definition($entry, $glossary, $cm);

        // Use this function to show the attachment. It'll be shown if necessary.
        glossary_print_entry_attachment($entry, $cm, 'html');

        //Line separator to show this template fine. :-)
        echo "<br />\n";

        //Use this function to show aliases, editing icons and ratings (all know as the 'lower section')
        //Comments: You can configure this parameters:
        //----Define when to show the aliases popup
        //    use it only if you are really sure!
        //$aliases = true; //Values: true, false (Default: true)
        //----Uncoment this line to avoid editing icons being showed
        //    use it only if you are really sure!
        //$printicons = false;
        glossary_print_entry_lower_section($course, $cm, $glossary, $entry, $mode, $hook, $printicons, $aliases);

        echo '</td>';
        echo '</tr>';
        echo "</table>\n";
    } else {
        echo '<div style="text-align:center">';
        print_string('noentry', 'glossary');
        echo '</div>';
    }
}

function glossary_print_entry_TEMPLATE($course, $cm, $glossary, $entry, $mode='', $hook='', $printicons=1) {

    //The print view for this format is exactly the normal view, so we use it
    //Anyway, you can modify this to use your own print format!!

    //Take out autolinking in definitions in print view
    $entry->definition = '<span class="nolink">'.$entry->definition.'</span>';

    //Call to view function (without icons, ratings and aliases) and return its result
    return glossary_show_entry_TEMPLATE($course, $cm, $glossary, $entry, $mode, $hook, false, false, false);

}


