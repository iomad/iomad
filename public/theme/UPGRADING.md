# theme (plugin type) Upgrade notes

## 5.2

### Added

- The manual completion button and activity dates have been moved to the activity header to improve visibility and proximity to the activity name. A new theme layout option, `activityinfoinheader`, has been introduced to control this behaviour and is enabled by default. Themes that set `activityinfoinheader` to false must manually override the relevant template (such as `activity_header` or `activity_info`) to ensure the completion information and the activity dates are displayed correctly.

  For more information see [MDL-87662](https://tracker.moodle.org/browse/MDL-87662)
- The `core_courseformat\base` class now includes `set_show_restrictions_expanded()` and `get_show_restrictions_expanded()` to allow course formats to define whether restrictions are displayed as expanded (defaulting to collapsed).

  For more information see [MDL-87929](https://tracker.moodle.org/browse/MDL-87929)

### Deprecated

- These icons are no longer in use and have been deprecated:
    - `core:t/blocks_drawer`
    - `core:t/blocks_drawer_rtl`
    - `core:t/index_drawer`

  For more information see [MDL-88085](https://tracker.moodle.org/browse/MDL-88085)

## 5.1

### Deprecated

- These icons are no longer in use and have been deprecated:
    - core:e/insert_col_after
    - core:e/insert_col_before
    - core:e/split_cells
    - core:e/text_color
    - core:t/locktime
    - tool_policy/level

  For more information see [MDL-85436](https://tracker.moodle.org/browse/MDL-85436)

## 4.5

### Added

- Added a new `\renderer_base::get_page` getter method.

  For more information see [MDL-81597](https://tracker.moodle.org/browse/MDL-81597)
- New `core/context_header` mustache template has been added. This template can be overridden by themes to modify the context header.

  For more information see [MDL-81597](https://tracker.moodle.org/browse/MDL-81597)

### Deprecated

- The method `\core\output\core_renderer::render_context_header` has been deprecated please use `\core\output\core_renderer::render($contextheader)` instead

  For more information see [MDL-82160](https://tracker.moodle.org/browse/MDL-82160)

### Removed

- Removed all references to `iconhelp`, `icon-pre`, `icon-post`, `iconlarge`, and `iconsort` CSS classes.

  For more information see [MDL-74251](https://tracker.moodle.org/browse/MDL-74251)
