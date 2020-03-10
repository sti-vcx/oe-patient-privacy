<?php

namespace PatientPrivacy;

use Mi2\DataTable\DataTable;

class PatientPrivacyService
{
    public static function makeProviderDataTable()
    {
        $dt = DataTable::make(
            "SELECT U.id, U.lname, U.fname, U.username, U.active, G.name AS role FROM users U
            JOIN gacl_aro ARO ON U.username = ARO.value
            JOIN gacl_groups_aro_map MAP ON ARO.id = MAP.aro_id
            JOIN gacl_aro_groups G ON MAP.group_id = G.id
            WHERE U.active = '1'",
            "provider-data-table",
            "/interface/modules/custom_modules/oe-patient-privacy/index.php?action=admin!provider_data",
            new ProviderRowAttributeFilter())
            ->addColumn(["title" => "ID", "field" => "id"])
            ->addColumn(["title" => "Last Name", "field" => "lname"])
            ->addColumn(["title" => "First Name", "field" => "fname"])
            ->addColumn(["title" => "Username", "field" => "username"])
            ->addColumn(["title" => "Role", "field" => "role"]);
        return $dt;
    }

    public static function makePatientDataTable()
    {
        // This query gets all providers (and their supervisors) and the patient they access.
        $dt = DataTable::make(
            "SELECT provider_id, provider, lname, fname, DOB, pid
                FROM (
                    SELECT U.id AS provider_id, CONCAT(U.lname, ', ', U.fname) AS provider, PD.lname, PD.fname, PD.DOB, PD.pid
                    FROM patient_data PD
                    LEFT JOIN mi2_users_patients MUP ON PD.pid = MUP.pid
                    LEFT JOIN users U on U.id = MUP.user_id
                    GROUP BY PD.pid
                    UNION ALL
                    SELECT S.id AS provider_id, CONCAT(S.lname, ', ', S.fname) AS provider, PD.lname, PD.fname, PD.DOB, PD.pid
                    FROM patient_data PD
                    LEFT JOIN mi2_users_patients MUP ON PD.pid = MUP.pid
                    LEFT JOIN mi2_users_supervisors MUS ON MUP.user_id = MUS.user_id
                    LEFT JOIN users S ON S.id = MUS.super_user_id
                    GROUP BY PD.pid
                ) T",
            "patient-data-table",
            "/interface/modules/custom_modules/oe-patient-privacy/index.php?action=admin!patient_data",
            new PatientRowAttributeFilter())
            ->addColumn(["title" => "Provider ID", "field" => "provider_id", "visible" => false])
            ->addColumn(["title" => "Provider", "field" => "provider", "visible" => false])
            ->addColumn(["title" => "Last Name", "field" => "lname"])
            ->addColumn(["title" => "First Name", "field" => "fname"])
            ->addColumn(["title" => "DOB", "field" => "DOB"])
            ->addColumn(["title" => "PID", "field" => "pid"])
            ->groupBy("pid");
        return $dt;
    }

    public static function attachPatientToProvider($patientId, $providerId)
    {
        $sql = "REPLACE INTO mi2_users_patients (pid, user_id) VALUES (?,?)";
        $result = sqlInsert($sql, [$patientId, $providerId]);
        return $result;
    }

    public static function deleteAllSupervisors($provider_id)
    {
        $sql = "DELETE FROM mi2_users_supervisors WHERE user_id = ?";
        $result = sqlStatement($sql, [$provider_id]);
        return $result;
    }

    public static function deleteAllProviderAccess($pid)
    {
        $sql = "DELETE FROM mi2_users_patients WHERE pid = ?";
        $result = sqlStatement($sql, [$pid]);
        return $result;
    }

    public static function attachProviderToSupervisor($providerId, $supervisorId)
    {
        // We use REPLACE because we have a unique index on user_id/super_user_id combos
        // This way, we won't have duplicate entries
        $sql = "REPLACE INTO mi2_users_supervisors (user_id, super_user_id) VALUES (?,?)";
        $result = sqlInsert($sql, [$providerId, $supervisorId]);
        return $result;
    }

    public static function detachProviderFromSupervisor($providerId, $supervisorId)
    {
        $sql = "DELETE FROM mi2_users_supervisors WHERE user_id = ? AND super_user_id = ?";
        $result = sqlStatement($sql, [$providerId, $supervisorId]);
        return $result;
    }

    public static function fetchProviders()
    {
        $sql = "SELECT CONCAT(U.lname, ', ', U.fname) AS name, U.id
            FROM users U
            WHERE U.active = '1'
            ORDER BY U.lname";

        $providersForPatient = [];
        $result = sqlStatement($sql);
        while ($row = sqlFetchArray($result)) {
            $providersForPatient[]= $row;
        }

        return $providersForPatient;
    }

    /**
     * @return array
     *
     * Fetch an array of objects that represent all roles in the system that can
     * be assigned to users.
     *
     * We join with the excluded roles table so we can also return which roles are excluded
     * from patient privacy.
     */
    public static function fetchAllRoles()
    {
        $roles = [];
        $sql = "SELECT G.id, G.name, (MER.id IS NOT NULL) as excluded FROM gacl_aro_groups G
            LEFT JOIN mi2_exclude_roles MER ON G.id = MER.gid
            ORDER BY G.id ASC";
        $result = sqlStatement($sql);
        while ($row = sqlFetchArray($result)) {
            $role = new \stdClass();
            $role->id = $row['id'];
            $role->title = $row['name'];
            $role->excluded = $row['excluded'];
            $roles[] = $role;
        }
        return $roles;
    }

    public static function deleteAllExcludedRoles()
    {
        $sql = "DELETE FROM mi2_exclude_roles WHERE 1";
        $result = sqlStatement($sql);
        return $result;
    }

    public static function insertExcludedRole($role_id)
    {
        $sql = "INSERT INTO mi2_exclude_roles (gid) VALUES (?)";
        $result = sqlInsert($sql, [$role_id]);
        return $result;
    }

    public static function fetchProvider($provider_id)
    {
        $sql = "SELECT CONCAT(U.lname, ', ', U.fname) AS name, U.id
            FROM users U
            WHERE U.active = '1' AND U.id = ?
            ORDER BY U.lname";

        $provider = sqlQuery($sql, [$provider_id]);
        return $provider;
    }

    public static function fetchPatient($pid)
    {
        $sql = "SELECT pid, CONCAT(lname, ', ', fname) as name FROM patient_data WHERE pid = ?";
        $patient = sqlQuery($sql, [$pid]);
        return $patient;
    }

    /**
     * @param $pid
     *
     * Given a patient, show an array of all providers, along with a flag for who can
     * access. For the multiselect box on Provider Access popup.
     *
     * If the join results in a null value for pid, we know they do not have access
     */
    public static function fetchProvidersForPatient($pid)
    {
        $sql = "SELECT CONCAT(U.lname, ', ', U.fname) AS name, U.id, (MUP.pid IS NOT NULL) AS has_access
            FROM users U
            LEFT OUTER JOIN mi2_users_patients MUP ON MUP.user_id = U.id AND MUP.pid = ?
            WHERE U.active = '1'
            ORDER BY U.lname";

        $providersForPatient = [];
        $result = sqlStatement($sql, [$pid]);
        while ($row = sqlFetchArray($result)) {
            $providersForPatient[]= $row;
        }

        return $providersForPatient;
    }

    /**
     * @param $pid
     * @return array
     *
     * Given a patient, get all the providers who are supervisors that have access to this patient record
     * through their supervisor relationship with their underling
     */
    public static function fetchSupervisorsForPatient($pid)
    {
        $sql = "SELECT CONCAT(U.lname, ', ', U.fname) AS provider_name, CONCAT(S.lname, ', ',S.fname) AS supervisor_name, MUS.super_user_id AS supervisor_id, MUS.user_id AS provider_id
            FROM mi2_users_supervisors AS MUS
            JOIN users U ON U.id = MUS.user_id
            JOIN users S ON S.id = MUS.super_user_id
            JOIN mi2_users_patients MUP ON MUP.user_id = U.id AND MUP.pid = ?
            WHERE S.active = '1'";

        $supervisorsForPatient = [];
        $result = sqlStatement($sql, [$pid]);
        while ($row = sqlFetchArray($result)) {
            $supervisorsForPatient[]= $row;
        }

        return $supervisorsForPatient;
    }

    /**
     * @param $provider_id
     * @return array
     *
     * Given a provider ID, fetch all the providers, and flag the ones that are this provider's supervisor
     * with the 'is_supervisor' being 1
     */
    public static function fetchSupervisorsForProvider($provider_id)
    {
        $sql = "SELECT CONCAT(U.lname, ', ', U.fname) AS name, U.id, (MUS.super_user_id IS NOT NULL) AS is_supervisor
            FROM users U
            LEFT OUTER JOIN mi2_users_supervisors MUS ON MUS.user_id = ? AND MUS.super_user_id = U.id
            WHERE U.active = '1'
            ORDER BY U.lname";

        $supervisorsForProvider = [];
        $result = sqlStatement($sql, [$provider_id]);
        while ($row = sqlFetchArray($result)) {
            if ($row['id'] == $provider_id) {
                continue;
            }

            $supervisorsForProvider[]= $row;
        }

        return $supervisorsForProvider;
    }

    public static function getPrivacyFilterForUser($userId)
    {
        $privacyFilter = new PatientPrivacyFilter($userId);
        return $privacyFilter;
    }

}
