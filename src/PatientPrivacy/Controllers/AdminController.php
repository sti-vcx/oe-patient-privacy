<?php
/**
 * Created by PhpStorm.
 * User: kchapple
 * Date: 7/29/19
 * Time: 11:00 AM
 */

namespace PatientPrivacy\Controllers;

use Mi2\DataTable\SearchFilter;
use Mi2\Framework\AbstractController;
use Mi2\Framework\Response;
use PatientPrivacy\PatientPrivacyService;

class AdminController extends AbstractController
{
    public function __construct()
    {
    }

    public function _action_patient_search()
    {
        $query = $this->request->getParam( 'query' );
        if ( strpos( $query, '/' ) !== false ) {
            $parts = explode("/", $query );
            $year = "";
            $month = "";
            $day = "";
            for ( $i = 0; $i < count($parts); $i++ ) {
                switch ( $i ) {
                    case 0: $month = $parts[0];
                        break;
                    case 1: $day = $parts[1];
                        break;
                    case 2: $year = $parts[2];
                        break;
                }
            }

            $dob = "%-$month-%";
            if ( $day ) {
                $dob = "%-$month-$day";
            }

            if ( $year ) {
                $dob = "%$year-$month-$day%";
            }

            $statement = "SELECT PD.*, ( SELECT left(FE.date,10) FROM form_encounter FE WHERE PD.pid = FE.pid ORDER BY FE.date DESC LIMIT 1 ) AS last_encounter FROM patient_data PD WHERE PD.DOB LIKE ?";
            $result = sqlStatement( $statement, array( $dob ) );
        } else if ( strpos( $query, ',' ) !== false ) {
            $parts = explode( ',', $query );
            $fname = trim( $parts[1] );
            $lname = trim( $parts[0] );
            $statement = "SELECT PD.*, ( SELECT left(FE.date,10) FROM form_encounter FE WHERE PD.pid = FE.pid ORDER BY FE.date DESC LIMIT 1 ) AS last_encounter FROM patient_data PD WHERE PD.lname = ? AND PD.fname LIKE ?";
            $result = sqlStatement( $statement, array( $lname, "$fname%" ) );
        } else {
            $lname = trim( $query );
            $statement = "SELECT PD.*, ( SELECT left(FE.date,10) FROM form_encounter FE WHERE PD.pid = FE.pid ORDER BY FE.date DESC LIMIT 1 ) AS last_encounter FROM patient_data PD WHERE PD.lname LIKE ?";
            $result = sqlStatement( $statement, array( "$lname%" ) );
        }

        $patients = array();
        while ( $row = sqlFetchArray( $result ) ) {
            $patients []= array(
                'id' => $row['pid'],
                'name' => $row['lname'].", ".$row['fname'],
                'DOB' => $row['DOB'],
                'sex' => $row['sex'],
                'pid' => $row['pid'],
                'lastEncounter' => $row['last_encounter'],
                'displayKey' => $row['lname'].", ".$row['fname']." (".$row['pid']." ".$row['DOB'].") "
            );
        }

        echo json_encode( $patients );
        exit;
    }

    /**
     * This is the "home" page for the Patient Privacy settings
     */
    public function _action_index()
    {
        $this->setViewScript( 'admin/settings.php', 'layout.php' );
        $this->view->patientDataTable = PatientPrivacyService::makePatientDataTable();
        $this->view->providerDataTable = PatientPrivacyService::makeProviderDataTable();
        $this->view->providers = PatientPrivacyService::fetchProviders();
        $this->view->roles = PatientPrivacyService::fetchAllRoles();
        $this->view->title = "Patient Privacy Settings";
    }

    /**
     * AJAX endpoint
     *
     * Given a patient ID, get the patient name
     */
    public function _action_fetch_patient()
    {
        $pid = $this->request->getParam('pid');
        $patient = PatientPrivacyService::fetchPatient($pid);
        echo stripslashes(json_encode($patient));
    }

    public function _action_fetch_provider()
    {
        $provider_id = $this->request->getParam('provider_id');
        $provider = PatientPrivacyService::fetchProvider($provider_id);
        echo stripslashes(json_encode($provider));
    }

    /**
     * AJAX endpoint
     *
     * Given a patient ID, get the providers that have Direct access (not through Supervisor)
     */
    public function _action_fetch_providers_for_patient()
    {
        $pid = $this->request->getParam('pid');
        $providersForPatient = PatientPrivacyService::fetchProvidersForPatient($pid);
        echo stripslashes(json_encode($providersForPatient));
    }

    public function _action_attach_patient_to_provider()
    {
        $pid = $this->request->getParam('pid');
        $providerId = $this->request->getParam('provider_id');

        PatientPrivacyService::attachPatientToProvider($pid, $providerId);
        echo (new Response(200, "Success"))->toJSON();
    }

    public function _action_detach_provider_from_supervisor()
    {
        $providerId = $this->request->getParam('provider_id');
        $supervisorId = $this->request->getParam('supervisor_id');

        PatientPrivacyService::detachProviderFromSupervisor($providerId, $supervisorId);
        echo (new Response(200, "Success"))->toJSON();
    }

    /**
     * @param $pid
     *
     * Given a pid, return all of the providers who have access because they are a supervisor,
     * and the providers they are supervising that give them access
     */
    public function _action_fetch_supervisors_for_patient()
    {
        $pid = $this->request->getParam('pid');
        $supervisors = PatientPrivacyService::fetchSupervisorsForPatient($pid);
        echo stripslashes(json_encode($supervisors));
    }

    public function _action_fetch_supervisors_for_provider()
    {
        $provider_id = $this->request->getParam('provider_id');
        $supervisors = PatientPrivacyService::fetchSupervisorsForProvider($provider_id);
        echo stripslashes(json_encode($supervisors));
    }

    public function _action_attach_providers_to_supervisors()
    {
        // We get an array from the POST
        $supervisors = $this->request->getParam('supervisors');
        $providers = $this->request->getParam('providers');
        $pid = $this->request->getParam('pid');

        if (is_array($supervisors)) {
            foreach ($supervisors as $supervisor) {
                PatientPrivacyService::attachProviderToSupervisor($supervisor['provider_id'], $supervisor['supervisor_id']);
            }
        }

        if (is_array($providers)) {
            // We have to delete all first
            PatientPrivacyService::deleteAllProviderAccess($pid);
            foreach ($providers as $provider_id) {
                PatientPrivacyService::attachPatientToProvider($pid, $provider_id);
            }
        }
        echo (new Response(200, "Success"))->toJSON();
    }

    /**
     * Given one provider ID, attach an array of supervisors
     */
    public function _action_attach_provider_to_supervisors()
    {
        // We get an array from the POST
        $supervisors = $this->request->getParam('supervisors');
        $provider_id = $this->request->getParam('provider_id');

        if (is_array($supervisors)) {
            PatientPrivacyService::deleteAllSupervisors($provider_id);
            foreach ($supervisors as $supervisor_id) {
                PatientPrivacyService::attachProviderToSupervisor($provider_id, $supervisor_id);
            }
        }
    }

    /**
     * Set the roles excluded from patient privacy (can see all patients)
     */
    public function _action_set_excluded_roles()
    {
        $roles = $this->request->getParam('roles');
        if (is_array($roles)) {
            PatientPrivacyService::deleteAllExcludedRoles();
            foreach ($roles as $role_id) {
                PatientPrivacyService::insertExcludedRole($role_id);
            }
        }
    }

    /**
     * AJAX endpoint
     */
    public function _action_patient_data()
    {
        $dt = PatientPrivacyService::makePatientDataTable();
        if ($this->request->getParam('provider_filter')) {
            $dt->addSearchFilter(new SearchFilter('provider_id', $this->request->getParam('provider_filter'), SearchFilter::TYPE_STRICT));
        }
        $results = $dt->getResults($this->request);
        echo $results->toJson();
    }

    public function _action_provider_data()
    {
        $dt = PatientPrivacyService::makeProviderDataTable();
        $results = $dt->getResults($this->request);
        echo $results->toJson();
    }
}
