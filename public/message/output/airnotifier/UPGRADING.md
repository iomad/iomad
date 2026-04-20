# message_airnotifier Upgrade notes

## 5.2

### Added

- A new scheduled task, `message_airnotifier\task\cleanup_task`, has been added. This task removes orphaned records in the `message_airnotifier_devices` table.

  For more information see [MDL-87795](https://tracker.moodle.org/browse/MDL-87795)
