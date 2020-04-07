<?php


namespace PatientPrivacy;


class PatientPrivacyFilter
{
    protected $filterClause;
    protected $boundValues;

    public function __construct($userId)
    {
        // Here we build a filter for patients this user has visibility to.
        // We look in the users-patient table, but we also have to see if this user
        // is a supervisor, and we also show all of the patients under the "supervised" user as well.

        // First, we check if this user is excluded from privacy rules. If so, it's all good!
        if (UserService::isExcluded($userId)) {

            $this->boundValues = [];
            $this->filterClause = "1";

        } else {

            $sql = "SELECT pid
            FROM (
                SELECT PD.pid FROM patient_data PD
                JOIN mi2_users_patients MUP ON PD.pid = MUP.pid
                WHERE MUP.user_id = ?
                GROUP BY pid

                UNION ALL

                SELECT PD.pid FROM patient_data PD
                JOIN mi2_users_patients MUP ON PD.pid = MUP.pid
                JOIN mi2_users_supervisors MUS ON MUP.user_id = MUS.user_id
                WHERE MUS.super_user_id = ?
                GROUP BY pid
                ) T GROUP BY pid
        ";

            error_log($sql);
            // If there are patients to show, build a filter
            $result = sqlStatement($sql, [$userId, $userId]);
            $binds = [];
            $filterString = "(";
            while ($patient_row = sqlFetchArray($result)) {
                $filterString .= "?,";
                $binds [] = $patient_row['pid'];
            }

            if (count($binds) > 0) {
                $filterString = rtrim($filterString, ",");
                $filterString .= ")";

                $this->boundValues = $binds;
                $this->filterClause = " patient_data.pid IN $filterString ";
            } else {
                $this->boundValues = [];
                $this->filterClause = "0";
            }
        }
    }

    /**
     * @return string
     */
    public function getFilterClause(): string
    {
        return $this->filterClause;
    }

    /**
     * @return array
     */
    public function getBoundValues(): array
    {
        return $this->boundValues;
    }


}
