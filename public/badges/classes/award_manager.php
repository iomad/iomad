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

namespace core_badges;

/**
 * Badge award manager class.
 *
 * @package    core_badges
 * @copyright  2012 onwards Totara Learning Solutions Ltd {@link http://www.totaralms.com/}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author     Dai Nguyen Trong <ngtrdai@hotmail.com> based on Yuliya Bozhko <yuliya.bozhko@totaralms.com> code
 */
class award_manager {
    /**
     * Process manual badge award.
     *
     * @param int $recipientid The ID of the user receiving the badge.
     * @param int $issuerid The ID of the user issuing the badge.
     * @param int $issuerrole The role ID of the issuer.
     * @param int $badgeid The ID of the badge being awarded.
     * @return bool True if the award was processed successfully, false otherwise.
     */
    public static function process_manual_award(
        int $recipientid,
        int $issuerid,
        int $issuerrole,
        int $badgeid
    ): bool {
        global $DB;

        $params = [
            'badgeid' => $badgeid,
            'issuerid' => $issuerid,
            'issuerrole' => $issuerrole,
            'recipientid' => $recipientid,
        ];

        if (!$DB->record_exists('badge_manual_award', $params)) {
            $award = new \stdClass();
            $award->badgeid = $badgeid;
            $award->issuerid = $issuerid;
            $award->issuerrole = $issuerrole;
            $award->recipientid = $recipientid;
            $award->datemet = time();

            return (bool)$DB->insert_record('badge_manual_award', $award);
        }

        return false;
    }

    /**
     * Process manual badge revocation.
     *
     * @param int $recipientid The ID of the user whose badge is being revoked.
     * @param int $issuerid The ID of the user revoking the badge (if 0, issuer will be ignored).
     * @param int $issuerrole The role ID of the issuer.
     * @param int $badgeid The ID of the badge being revoked.
     * @return bool True if the revocation was processed successfully, false otherwise.
     * @throws \moodle_exception If the badge award record is not found.
     */
    public static function process_manual_revoke(
        int $recipientid,
        int $issuerid,
        int $issuerrole,
        int $badgeid
    ): bool {
        global $DB;

        $params = [
            'badgeid' => $badgeid,
            'issuerrole' => $issuerrole,
            'recipientid' => $recipientid,
        ];

        if (!empty($issuerid)) {
            $params['issuerid'] = $issuerid;
        }

        if (!$DB->record_exists('badge_manual_award', $params)) {
            throw new \moodle_exception('error:badgenotfound', 'badges');
        }

        $success = $DB->delete_records('badge_manual_award', $params);

        $success &= $DB->delete_records('badge_issued', [
            'badgeid' => $badgeid,
            'userid' => $recipientid,
        ]);

        if ($success) {
            $badge = new \badge($badgeid);
            $eventparams = [
                'objectid' => $badgeid,
                'relateduserid' => $recipientid,
                'context' => $badge->get_context(),
            ];
            $event = \core\event\badge_revoked::create($eventparams);
            $event->trigger();
        }

        return (bool)$success;
    }
}
