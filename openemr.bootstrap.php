<?php
/**
 * Bootstrap custom Patient Privacy module.
 *
 * @package   OpenEMR
 * @link      http://www.open-emr.org
 * @author    Ken Chapple <ken@mi-squared.com>
 * @copyright Copyright (c) 2020 Ken Chapple <ken@mi-squared.com>
 * @license   https://github.com/openemr/openemr/blob/master/LICENSE GNU General Public License 3
 */
//namespace PatientPrivacy;
//require_once __DIR__.'/vendor/autoload.php';

use OpenEMR\Events\PatientFinder\PatientFinderFilterEvent;
use Symfony\Component\EventDispatcher\Event;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use OpenEMR\Menu\MenuEvent;
use PatientPrivacy\PatientPrivacyService;

function oe_module_patient_privacy_add_menu_item(MenuEvent $event)
{
    $menu = $event->getMenu();

    $menuItem = new stdClass();
    $menuItem->requirement = 0;
    $menuItem->target = 'adm';
    $menuItem->menu_id = 'adm0';
    $menuItem->label = xlt("Patient Privacy");
    $menuItem->url = "/interface/modules/custom_modules/oe-patient-privacy/index.php?action=admin";
    $menuItem->children = [];
    $menuItem->acl_req = ["admin", "super"];

    foreach ($menu as $item) {
        if ($item->menu_id == 'admimg') {
            array_unshift($item->children, $menuItem);
            break;
        }
    }

    $event->setMenu($menu);

    return $event;
}

// Listen for the menu update event so we can dynamically add our patient privacy menu item
$eventDispatcher->addListener(MenuEvent::MENU_UPDATE, 'oe_module_patient_privacy_add_menu_item');

/**
 * @param PatientFinderFilterEvent $event
 */
function oe_module_patient_privacy_filter_by_user(PatientFinderFilterEvent $event)
{
    $userService = new \OpenEMR\Services\UserService();
    $user = $userService->getCurrentlyLoggedInUser();
    $patientPrivacyFilter = PatientPrivacyService::getPrivacyFilterForUser($user['id']);

    // Get filter obj from our event, and by default, don't show any patients
    $boundFilter = $event->getBoundFilter();

    // Set the query part we constructed as the custom where, which will be appended to patient filter query
    $boundFilter->setFilterClause($patientPrivacyFilter->getFilterClause());
    $boundFilter->setBoundValues($patientPrivacyFilter->getBoundValues());

    return $event;
}
// listen for the filter event in the patient finder (hook located in main/finder/dynamic_finder_ajax.php)
// Our handler will filter out patients that aren't associated with the logged-in users' facility list
$eventDispatcher->addListener(PatientFinderFilterEvent::EVENT_HANDLE, 'oe_module_patient_privacy_filter_by_user');

/**
 * @param ViewEvent $event
 * @return ViewEvent
 *
 * Handler for the view event in patient demographics. If the patient is in the logged-in user's
 * blacklist, they will not have access.
 */
function oe_module_patient_privacy_checkUserForViewAuth(\OpenEMR\Events\PatientDemographics\ViewEvent $event)
{
    $userService = new \PatientPrivacy\UserService();
    $user = $userService->getCurrentlyLoggedInUser();

    if (\PatientPrivacy\UserService::isExcluded($user['id'])) {
        $event->setAuthorized(true);
    } else {

        $providers = PatientPrivacyService::fetchProvidersForPatient($event->getPid());
        $supervisors = PatientPrivacyService::fetchSupervisorsForPatient($event->getPid());

        if (in_array($user['id'], $providers) ||
            in_array($user['id'], $supervisors)) {
            $event->setAuthorized(true);
        } else {
            $event->setAuthorized(false);
        }
    }

    return $event;
}

// listen for view and update events on the patient demographics screen (hooks located in
// interface/patient_file/summary/demogrphics.php and
// interface/patient_file/summary/demogrphics_full.php
$eventDispatcher->addListener(\OpenEMR\Events\PatientDemographics\ViewEvent::EVENT_HANDLE, 'oe_module_patient_privacy_checkUserForViewAuth');

/**
 * @param UpdateEvent $event
 * @return UpdateEvent
 *
 * Handler for the update event in patient demographics. If the patient is in the logged-in user's
 * blacklist, they will not have access.
 */
function oe_module_patient_privacy_checkUserForUpdateAuth(\OpenEMR\Events\PatientDemographics\UpdateEvent $event)
{
    $patientPrivacyService = new PatientPrivacyService();

    $userService = new \PatientPrivacy\UserService();
    $user = $userService->getCurrentlyLoggedInUser();

    if (\PatientPrivacy\UserService::isExcluded($user['id'])) {
        $event->setAuthorized(true);
    } else {
        $providers = PatientPrivacyService::fetchProvidersForPatient($event->getPid());
        $supervisors = PatientPrivacyService::fetchSupervisorsForPatient($event->getPid());

        if (in_array($user['id'], $providers) ||
            in_array($user['id'], $supervisors)) {
            $event->setAuthorized(true);
        } else {
            $event->setAuthorized(false);
        }
    }

    return $event;
}

// listen for view and update events on the patient demographics screen (hooks located in
// interface/patient_file/summary/demogrphics.php and
// interface/patient_file/summary/demogrphics_full.php
$eventDispatcher->addListener(\OpenEMR\Events\PatientDemographics\UpdateEvent::EVENT_HANDLE, 'oe_module_patient_privacy_checkUserForUpdateAuth');

