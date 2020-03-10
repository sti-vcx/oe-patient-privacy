<?php


namespace PatientPrivacy;


class UserService extends \OpenEMR\Services\UserService
{
    public static function isExcluded($userId)
    {
        $sql = "SELECT U.id AS user_id, U.username, ARO.id, ARO.value, MAP.group_id, G.value FROM users U
          JOIN gacl_aro ARO ON U.username = ARO.value
          JOIN gacl_groups_aro_map MAP ON ARO.id = MAP.aro_id
          JOIN gacl_aro_groups G ON MAP.group_id = G.id
          WHERE G.id IN ( SELECT gid FROM mi2_exclude_roles ) AND U.id = ? LIMIT 1";
        $result = sqlQuery($sql, [$userId]);
        if ($result) {
            return true;
        }

        return false;
    }
}
