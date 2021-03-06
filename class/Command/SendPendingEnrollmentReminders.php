<?php
namespace Intern\Command;

use Intern\WorkflowStateFactory;
use Intern\ChangeHistory;
use Intern\TermProviderFactory;
use Intern\Term;
use Intern\InternshipFactory;
use Intern\Email;

/**
 * @author Chris Detsch
 */
class SendPendingEnrollmentReminders
{

    public function __construct()
    {

    }

    public function execute()
    {
        // Get the list of future terms
        $provider = TermProviderFactory::getProvider();
        $terms = array_keys(Term::getFutureTermsAssoc());

        foreach ($terms as $term) {
            // Get the pending internships for this term
            $pendingInternships = InternshipFactory::getPendingInternshipsByTerm($term);

            // Get the census date for this term
            $termInfo = $provider->getTerm($term);
            $censusDate = $termInfo->getCensusDate();
            $censusTimestamp = strtotime($censusDate);

            // Double check that we have a valid census timestamp. Try to avoid sending emails with the date set to December 31, 1969
            if($censusTimestamp === 0 || $censusTimestamp === '' || $censusTimestamp === null || !isset($censusTimestamp) || empty($censusTimestamp)){
                throw new \InvalidArgumentException("Census timestamp is 0, null, empty, or not set for $term.");
            }

            // Calculate timestamps for 1 week and 4 weeks into the future
            $oneWeekOut = strtotime('+1 week');
            $fourWeeksOut = strtotime('+4 weeks');

            if($oneWeekOut > $censusTimestamp){
                // We're within one week of census
                $withinOneWeek = true;
            }else if ($fourWeeksOut > $censusTimestamp){
                // We're more than one week, but less than 4 weeks from census
                $withinOneWeek = false;
            }else{
                // If we're not within four weeks, then we can skip this term completely
                continue;
            }

            // Loop over each pending internship in this term
            foreach ($pendingInternships as $i) {

                // If there is a faculty member, email them.. There may not always be one.
                $faculty = $i->getFaculty();
                $currState = WorkflowStateFactory::getState($i->getStateName());
                if(!is_null($faculty)){
                    if($withinOneWeek){
                        Email::sendEnrollmentReminderEmail($i, $censusTimestamp, $faculty->getUsername(), 'FacultyReminderEmail1Week.tpl');
                        $ch = new ChangeHistory($i, null, time(), $currState, $currState, 'Faculty 1-Week Census Date Reminder Sent');
                    }else{
                        Email::sendEnrollmentReminderEmail($i, $censusTimestamp, $faculty->getUsername(), 'FacultyReminderEmail4Weeks.tpl');
                        $ch = new ChangeHistory($i, null, time(), $currState, $currState, 'Faculty Census Date Reminder Sent');
                    }

                    $ch->save();
                }

                // Email the student
                if($withinOneWeek){
                    Email::sendEnrollmentReminderEmail($i, $censusTimestamp, $i->getEmailAddress(), 'StudentReminderEmail1Week.tpl');
                    $ch = new ChangeHistory($i, null, time(), $currState, $currState, 'Student 1-Week Census Date Reminder Sent');
                }else{
                    Email::sendEnrollmentReminderEmail($i, $censusTimestamp, $i->getEmailAddress(), 'StudentReminderEmail4Weeks.tpl');
                    $ch = new ChangeHistory($i, null, time(), $currState, $currState, 'Student Census Date Reminder Sent');
                }
                $ch->save();
            }
        }
    }

    public static function pulseIsStatic(){
        require_once(PHPWS_SOURCE_DIR . 'inc/intern_defines.php');

        \PHPWS_Core::initModClass('users', 'Users.php');
        \PHPWS_Core::initModClass('users', 'Current_User.php');
        $user = new \PHPWS_User(0, 'jb67803');
        //$user->login();
        \Current_User::init($user->id);



        $obj = new SendPendingEnrollmentReminders();
        $obj->execute();
    }

}
