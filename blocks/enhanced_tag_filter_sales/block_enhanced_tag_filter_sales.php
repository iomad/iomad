<?php 
class block_enhanced_tag_filter_sales extends block_base {
    public function init() {
        $this->title = get_string('plugintext', 'block_enhanced_tag_filter_sales');
    }

    public function get_content() { 
        global $CFG, $DB;
        $companyid = iomad::get_my_companyid(context_system::instance(), false); // get company

	$resetfilterlabel = get_string('resetfilter', 'block_enhanced_tag_filter_sales');
        $this->content = new stdClass;
        $this->content->text = '';
        $this->content->footer = '';
        $title = ''; //initialise tag text

        // Execute the SQL query to fetch tags with their counts
        $sql = "SELECT t.id, t.name, t.rawname, t.description, tc.correlatedtags, COUNT(c.id) AS course_count
                FROM {tag} t
                LEFT JOIN {tag_instance} ti ON ti.tagid = t.id
                LEFT JOIN {tag_correlation} tc ON tc.tagid = t.id
                LEFT JOIN {course} c ON c.id = ti.itemid
                LEFT JOIN {context} ctx ON ctx.instanceid = c.id AND ctx.contextlevel = :contextlevel
                LEFT JOIN {course_categories} cc ON cc.id = c.category
                WHERE (t.isstandard = :isstandard OR t.name LIKE '[%]') -- Include tags in brackets []
                AND ctx.id IS NOT NULL
                GROUP BY t.id, t.name
                ORDER BY 
                  CASE
                    WHEN t.name LIKE '%[%' THEN 1  -- Tags containing '['
                    ELSE 2                          -- Other tags
                  END,
                  t.name ASC";
                  
        $params = array(
            'isstandard' => 1,
            'contextlevel' => CONTEXT_COURSE,
            'companyid' => $companyid,
        );
        $standardTags = $DB->get_records_sql($sql, $params);

        // Output the reset filter button and necessary scripts and styles
        $this->content->text .= '<button id="reset-filter" style="position: absolute; top: 10px; right: 10px; border-radius: 4px; font-size: 0.875rem; font-weight: 500; background-color: #75be39; color: white; border: none; padding: 6px 12px; cursor: pointer; transition: background-color 0.3s, color 0.3s;">' . $resetfilterlabel . '</button>';
        $this->content->text .= '<link rel="stylesheet" type="text/css" href="'.$CFG->wwwroot.'/blocks/enhanced_tag_filter/styles/style.css">';
        $this->content->text .= '<script>
        document.addEventListener("DOMContentLoaded", function() {
            var resetButton = document.getElementById("reset-filter");
            if (resetButton) {
                resetButton.addEventListener("click", function() {
                    document.getElementById("filtered-results").innerHTML = "";
                    var selectedTags = document.querySelectorAll(".selected");
                    selectedTags.forEach(function(tag) {
                        tag.classList.remove("selected");
                        tag.removeAttribute("data-tag");
                    });
                });
            }
        });
        </script>';

        // Display the tags with varying font sizes based on count
        $this->content->text .= '<div class="tag_cloud" ><ul class="inline-list">';
        foreach ($standardTags as $tag){
            $urlEncodedString = urlencode($tag->rawname);
            $urlEncodedString = str_replace('+', '%20', $urlEncodedString);
            if (isset($tag->description) && $tag->description !== '') {
                $title = format_string($tag->description, $striplinks = false); // if a multilingual tag description is defined, take it
            } else {
                $title = $tag->rawname; // if not, take the english tag name
            }
                        $this->content->text .= "<li><a href='javascript:void(0)' data-id='{$tag->id}' class='standardtags' title='{$tag->rawname} ({$tag->course_count})'  data-tag='{$urlEncodedString}'>{$title}</a></li>";
        }
        
        $this->content->text .= "</ul></div>";

        // Include necessary JavaScript
        $this->content->text .= '<script src="' . $CFG->wwwroot . '/blocks/enhanced_tag_filter/script.js"></script>';

        // Output the container for filtered results
        $this->content->text .= "<div id='filtered-results'></div>";

        return $this->content;
    }

    /**
     * Return the plugin config settings for external functions.
     *
     * @return stdClass The configs for both the block instance and plugin.
     */
    public function get_config_for_external() {
        // Return all settings for all users since it is safe (no private keys, etc..).
        $configs = !empty($this->config) ? $this->config : new stdClass();

        return (object) [
            'instance' => $configs,
            'plugin' => new stdClass(),
        ];
    }

    /**
     * This block shouldn't be added to a page if the tags advanced feature is disabled.
     *
     * @param moodle_page $page
     * @return bool
     */
    public function can_block_be_added(moodle_page $page): bool {
        global $CFG;

        return $CFG->usetags;
    }
}
