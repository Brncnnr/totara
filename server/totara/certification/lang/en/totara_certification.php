<?php
/*
 * This file is part of Totara LMS
 *
 * Copyright (C) 2010 onwards Totara Learning Solutions LTD
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Russell England <russell.england@catalyst-eu.net>
 * @package totara
 * @subpackage certification
 */

$string['activeperiod'] = 'Active Period';
$string['addcertifprogramcontent'] = 'Add certification content';
$string['addcohortstoprogram'] = 'Add audiences to certification';
$string['addindividualstoprogram'] = 'Add individuals to certification';
$string['addmanagerstoprogram'] = 'Add managers to certification';
$string['addorganisationstoprogram'] = 'Add organisations to certification';
$string['addpositionstoprogram'] = 'Add positions to certification';
$string['addcertifprogramcontent_help'] = 'By adding sets of courses you can build up the learning path of the certification.
Once sets are added the relationships between them can be defined. Sets are created from manually adding courses.

Once a number of sets have been created, set dividers are employed to allow the creation of sequences (i.e. dependencies) between each set.
An example certification with four course sets defined could have dependencies as follows:

* From set one the learner must complete one course (courseA or courseB) before proceeding to set two.
* From set two the learner must complete all courses (courseC and courseD and courseE) before proceeding to set three or set four.
* From set three the learner must complete one course (courseE) or all courses from set four (courseF and courseG).

Once the learning path is completed, the learner has finished the certification.';
$string['addnewcertification'] = 'Add new certification';
$string['allowextensionrequests'] = 'Allow extension requests';
$string['allowextensionrequests_help'] = 'When enabled, learners can submit extension requests for this certification which can then be approved or denied by their manager.';
$string['allowtimeforprogram'] = 'Allow at least {$a->num} {$a->periodstr} to complete this certification.';
$string['allowtimeforprogramdays'] = 'Allow at least {$a} day(s) to complete this certification.';
$string['allowtimeforprogramhours'] = 'Allow at least {$a} hour(s) to complete this certification.';
$string['allowtimeforprogrammonths'] = 'Allow at least {$a} month(s) to complete this certification.';
$string['allowtimeforprogramweeks'] = 'Allow at least {$a} week(s) to complete this certification.';
$string['allowtimeforprogramyears'] = 'Allow at least {$a} year(s) to complete this certification.';
$string['allowtimeforprograminfinity'] = 'There is no time limit to complete this certification.';
$string['assignmentadded'] = '\'{$a}\' has been added to the certification';
$string['assignmentcriterialearner'] = 'You are required to complete this certification under the following criteria:';
$string['assignmentcriteriamanager'] = 'The learner is required to complete this certification under the following criteria:';
$string['availablefrom'] = 'Available From';
$string['availableuntil'] = 'Available Until';
$string['beforewindowduetoclose'] = 'Before window is due to close';
$string['cancelcertificationmanagement'] = 'Clear unsaved changes';
$string['catalog_already_enrolled'] = 'You are already enrolled in this certification';
$string['catalog_cannot_view'] = 'You cannot view this certification';
$string['catalog_go_to_certification'] = 'Go to certification';
$string['catalog_not_enrolled'] = 'You are not enrolled in this certification';
$string['certassignmentduedates'] = 'Certification assignment due dates';
$string['certcomplete'] = 'Your certification is complete.';
$string['certexpired'] = 'Your certification has expired, you need to complete the original certification';
$string['certifcategories'] = 'Certification Categories';
$string['certification'] = 'Certification';
$string['certificationadministration'] = 'Certification Administration';
$string['certificationcontentsaved'] = 'Certification content saved successfully';
$string['certification:configurecertification'] = 'Configure certification';
$string['certification:configuredetails'] = 'Configure certification details';
$string['certification:createcertification'] = 'Create certification';
$string['certification:deletecertification'] = 'Delete certification';
$string['certification:viewhiddencertifications'] = 'View hidden certifications';
$string['certificationcontent'] = 'Define the certification content by adding sets of courses';
$string['certificationcreatesuccess'] = 'Certification created successfully';
$string['certificationdefaults'] = 'Certification default settings';

$string['certificationdeletesuccess'] = 'Certification "{$a}" deleted successfully';
$string['certificationdetailssaved'] = 'Certification details saved';
$string['certificationduex'] = 'Certification due {$a}';
$string['certificationexceptions'] = 'Certification exceptions';
$string['certificationhistory'] = 'Previous Certification';
$string['certificationlive'] = 'Caution: Certification is live - there are learners who will see or be affected by changes you make';
$string['certificationmembership'] = 'Certification Membership';
$string['certificationmessages'] = 'Certification messages';
$string['certificationnotavailable'] = 'Certification is not available to learners';
$string['certificationnotlive'] = 'Certification is not live';
$string['certifications'] = 'Certifications';
$string['certificationsdisabled'] = 'Certifications are not enabled on this site';
$string['certificationsinthiscategory'] = 'Certifications in this category';
$string['certifdeletefail'] = 'Could not delete certification "{$a}"';
$string['certified'] = 'Certified';
$string['certifname'] = 'Certification Name';
$string['certifprog'] = 'Certification program';
$string['certifcreatesuccess'] = 'Certification creation successful';
$string['certifsmovedout'] = 'Certifications moved out from {$a}';
$string['certinprogress'] = 'Your certification is in progress';
$string['checkcertificationdelete'] = 'Are you sure you want to delete this certification and all its related items?';
$string['checkcompletions'] = 'Check completions for problems';
$string['competency'] = 'Competency';
$string['completionapparentactiveperiod'] = 'Apparent active period';
$string['completionapparentactiveperiod_help'] = 'If the recertification method was set to **Use completion date** when the user certified, then this was the active period at that moment.

Otherwise, this is an estimate of the active period at that moment - it could have been greater or less depending on various certification settings and whether the user certified before or after their due date.

When this user recertifies, their new expiry date will be calculated using the Certification active period, as set in the certification settings.';
$string['completionapparentwindowperiod'] = 'Apparent window period';
$string['completionapparentwindowperiod_help'] = 'This is what the window period was at the time the user certified.

When this user recertifies, their new window open date will be calculated using the Certification window period, as set in the certification settings.';
$string['completionbaselinetimeexpires'] = 'Baseline expiry date';
$string['completionbaselinetimeexpires_help'] = 'The baseline expiry date is the date used to calculate subsequent expiry dates. In most cases, this should be the same as the expiry date, but may be different if the expiry date has been extended.';
$string['completioncertificationpath'] = 'Certification path';
$string['completioncertificationactiveperiod'] = 'Certification active period';
$string['completioncertificationwindowperiod'] = 'Certification window period';
$string['completionchangeconfirm'] = 'All dates will be rounded down to the nearest five minutes.<br>Are you sure you want to apply these changes?';
$string['completionchangecron'] = 'The next time completion data is processed by cron, the following will occur:';
$string['completionchangecronexpire'] = 'The user\'s courses will expire, because their Expiry date is in the past, changing the user from the recertification path to the primary certification path.';
$string['completionchangecronwindowopen'] = 'The user\'s recertification window will open, because their Window open date is in the past, causing their courses to be reset, and they will become due to complete recertification. Once a user\'s course progress is reset, it cannot be undone.';
$string['completionchangestates'] = 'You are changing the state of this completion record from <strong>{$a->from}</strong> to <strong>{$a->to}</strong>.';
$string['completionchangeuser'] = 'For the user, the consequences of these changes are:';
$string['completionchangeusercoursesetreset'] = 'The course set completion records will be reset. If the courses are still complete then the course set records will be recreated automatically by cron.';
$string['completionchangeusercoursesnotreset'] = 'Their courses will not be reset, and existing course progress may contribute to recertification, possibly triggering completion immediately (by cron). If you want to have the courses reset, set the Certification completion state to \'Certified, before window opens\' and allow cron to automatically open the window for the user.';
$string['completionchangeusercoursesreset'] = 'Their courses will be reset again when the window opens. Any progress they have made will be lost.';
$string['completionchangeusercompletionarchived'] = 'Another (possibly duplicate) completion record will be created when the window opens.';
$string['completionchangeusercompletionnotarchived'] = 'Their new completion record will not be archived when they next complete the certification. To have the completion history automatically created, set the Certification completion state to \'Certified, before window opens\' and allow cron to automatically update the user\'s state.';
$string['completionchangeuserdue'] = 'They will now be due to complete certification.';
$string['completionchangeuserenableextensions'] = 'It will be possible for them to request and be granted an extension.';
$string['completionchangeusernotdue'] = 'They will no longer be due to complete certification.';
$string['completionchangeuserpathcerttorecert'] = 'They will change from the primary certification path to the recertification path.';
$string['completionchangeuserpathrecerttocert'] = 'They will change from the recertification path to the primary certification path.';
$string['completionfilterbycertification'] = 'Filter by certification: {$a}';
$string['completionfilterbyuser'] = 'Filter by user: {$a}';
$string['completionhistorystate'] = 'Completion history state';
$string['completionhistorystate_help'] = 'Certification completion history records are normally created just before a user\'s recertification window opens, so should normally have the state **Certified, before window opens**.';
$string['completioninprogress'] = 'In progress';
$string['completioninprogress_help'] = 'Whether or not the learner has completed one or more of the course sets required for their current certification path.';
$string['completioninprogressnotapplicable'] = 'Not applicable';
$string['completionpath'] = 'Path';
$string['completionpath_help'] = 'The certification path that the user is currently on. Users who are waiting for their window to open are considered on the recertification path.';
$string['completionprogstatus'] = 'Program status';
$string['completionprogstatus_help'] = 'When a user is certified, before the recertification window has opened, the program status should be **Program complete**, otherwise it should be **Program incomplete**.

This field is only used internally - it is not displayed to the learner or in reports. It is presented here to ensure that it has the correct value.';
$string['completionstateedited'] = 'Certification completion state edited';
$string['completionprogtimecompleted'] = 'Program completion date';
$string['completionprogtimecompleted_help'] = 'When a user is certified, before the recertification window has opened, the program completion date should be equal to the certification completion date, otherwise it should be empty.

This field is only used internally - it is not displayed to the learner or in reports. It is presented here to ensure that it has the correct value.';
$string['completionprogtimecompletedsameascert'] = 'Automatically set to the certification Completion date';
$string['completionrecordcountproblem'] = 'Problem records: {$a}';
$string['completionrecordcounttotal'] = 'Total records: {$a}';
$string['completionrenewalstatus'] = 'Renewal status';
$string['completionreturntocertification'] = 'Return to certification';
$string['completions'] = 'Completions';
$string['completionstate'] = 'Certification completion state';
$string['completionstate_help'] = 'Select the state of the user\'s certification. The options below may be changed or limited depending on this choice.';
$string['completioncertstatus'] = 'Certification status';
$string['completioncertstatus_help'] = 'This is the status as it appears in the **Record of Learning: Certifications** report.';
$string['completionswithproblems'] = 'Certification completion records with problems';
$string['completiontimeduesameasexpiry'] = 'Automatically set to the Expiry date';
$string['completiontimeexpires'] = 'Effective Expiry date';
$string['completiontimewindowopens'] = 'Window open date';
$string['completionunassigned'] = 'Unassigned';
$string['completionunassigned_help'] = 'If **yes**, then the record was created when the user was unassigned from the certification. There should be at most one unassigned certification completion history record. The **Unassigned** record is used when re-assigning users to certifications which they were removed from.';
$string['comptype'] = 'Certification type';
$string['comptype_help'] = 'Select required Learning Component (currently just program).';
$string['comptypenotimplemented'] = 'Certification type not implemented';
$string['confirmchanges'] = 'Confirm certification changes';
$string['course'] = 'Course';
$string['createnewcertification'] = 'Create new certification';
$string['createnewcertif'] = 'Create new certification';
$string['currentlycertified'] = 'You are currently certified - you do not need to work on this certification until the recertification window opens. Progress made before the recertification window opens will likely be lost.';
$string['days'] = 'Day(s)';
$string['defaultcertprogramfullname'] = 'Certification fullname 101';
$string['defaultcertprogramshortname'] = 'CP101';
$string['deletecertificationbutton'] = 'Delete certification';
$string['image'] = 'Image';
$string['image_help'] = 'Upload an image that will get displayed with the certification. If no image is uploaded a default image will be used.';
$string['imagedefault'] = 'Default image';
$string['imagedefault_help'] = 'Set a default image to display if no image is uploaded by the certification creator.';
$string['imagedefaultlink'] = 'Set default image for all certifications';
$string['instructions:assignments1'] = 'Categories can be used to assign the certification to sets of learners.';

$string['instructions:certificationmessages'] = 'Define certification messages and reminders as required';
$string['instructions:messages1'] = 'Configure event and reminder triggers associated with the certification.';
$string['duedate'] = 'Due date';
$string['editcertif'] = 'Edit certification details';
$string['editcertification'] = 'Edit certification';
$string['editcertificationcontent'] = 'Edit certification content';
$string['editcertificationmessages'] = 'Edit certification messages';
$string['editdetailsactive'] = 'Certification is active for';
$string['editdetailsactive_help'] = 'The period the certification is active for, before it expires.';
$string['editdetailsactivep'] = 'Active Period';
$string['editdetailsdesc'] = 'Define the recertification details rules for all learners assigned to the certification';
$string['editdetailshdr'] = 'Recertification Details';
$string['editdetailsminimumactive'] = 'Minimum active period';
$string['editdetailsminimumactive_help'] = 'When **Use fixed expiry date** is set, this option allows you to specify the minimum length of time that the certification will be guaranteed to be active. If a user completes their certification and there is less than this amount of time until it would be due to expire, the expiry date is pushed forward by another whole active period.

For example:

* If a user was newly assigned to a certification in July, the active period is 1 year, the window opens 2 months before expiring, the minimum active period is 6 months and they have an assignment due date of 1 December, then when they complete in August, their expiry date will be set to 1 December of the following year (actual active period of about 16 months).
* If a user in the same certification completes their recertification in November while the window is open, then their certification would be set to expire on 1 December of the following year (actual active period of about 13 months).
* If a user in the same certification failed to recertify on time and subsequently completed the primary certification in April, then their certification would be set to expire on 1 December of that year (actual active period of about 8 months).';
$string['editdetailsrccmpl'] = 'Use certification completion date';
$string['editdetailsrcexp'] = 'Use certification expiry date';
$string['editdetailsrcfixed'] = 'Use fixed expiry date';
$string['editdetailsrcopt'] = 'Recertification date';
$string['editdetailsrcopt_help'] = 'This setting determines how the expiry date is calculated when a user completes primary certification or recertifies.

* **Use certification completion date**: The active period is added to the completion date. Over the span of several recertifications, this option can cause the expiry date to drift backwards (when completed early) and forwards (when completed overdue).
* **Use certification expiry date**: Uses the last expiry date to calculate the next. The first time this is calculated, it adds the active period to the assignment due date if there is one, otherwise it uses the date the certification was completed. If the user is overdue or expired, it adds the **active period** to the date the primary certification was just completed (as if the user was newly assigned), so that certifications are active for a minimum of the active period. Over the span of several recertifications, this option can cause the expiry date to drift forwards (when completed overdue) but not backwards.
* **Use fixed expiry date**: This option causes the expiry time to be calculated based on the specified assignment due date (if available, otherwise the **first** completion date), and subsequent completion expiry dates will be calculated using that same base date, regardless of whether they are late or early. The active period is repeatedly added to the base date until the first future date is discovered. The **Minimum active period** is available only with this setting, and must be at least as big as the **Recertification Window** (see the **Minimum active period** help for more information). Over the span of several recertifications, this option will prevent the expiry date from drifting forwards or backwards.';
$string['editdetailsrcwin'] = 'Recertification Window';
$string['editdetailsvalid'] = 'Define how long the certification should be valid once complete';
$string['editdetailswindow'] = 'Period window opens before expiration';
$string['editdetailswindowupdate'] = 'Any changes to these details will not affect the data of users who are currently certified. The new values will be used the next time a user certifies, to calculate their next window open and expiry dates.';
$string['editdetailswindow_help'] = 'The period before certification expires that a learner can start recertifying. When the window opens, all course and activity completion data will be reset, including courses and activities completed by RPL.';
$string['editprogramassignments'] = 'Edit certification assignments';
$string['endnote'] = 'Endnote';
$string['endnote_help'] = 'Note to be displayed at the end of the certification.';
$string['error:cannotcreatecompletion'] = 'Failure attempting to insert the certification completion record into the database';
$string['error:categoryidwasincorrect'] = 'Category ID was incorrect';
$string['error:categorymustbespecified'] = 'Category must be specified';
$string['error:certifsnotmoved'] = 'Error, certifications not moved from {$a}!';
$string['error:complalreadyexists'] = 'Attempting to create certification completion record that already exists for certifid={$a->certifid} userid={$a->userid}';
$string['error:completionhistorydatesnotunique'] = 'The combination of completion date and expiry date must be unique for this user and certification.';
$string['error:completionstatusunset'] = 'Completion status should never be \'Unset\'.';
$string['error:info_fixcertifiedprogincomplete'] = 'There are two known possible causes for this problem.<br>
The certification completion record was set to complete, but the program completion record was not.<br>
The recertification window opened and the program completion record was updated, but the certification completion record wasn\'t.<br>
In either case, the suggested solution is to change the program completion record to indicate \'complete\'. This will set the users into the \'certified\' state.<br>
In the second scenario, the recertification window will most likely reopen again the next time cron runs, and the user\'s courses will be reset. Any course progress will be lost! In this scenario, it is likely that course reset would not have occurred when it should have, in which case the courses need to be reset. If you believe that course reset has already occurred then this fix should not be used.';
$string['error:info_fixcombination'] = 'This is a combination of two or more different problems. Each problem should be fixed individually. We recommend that the fix below be applied first.';
$string['error:info_fixduedate'] = 'Users affected by this problem will see the incorrect due date in their Record of Learning (and potentially other places). This problem can safely be fixed automatically, by setting the due date to be equal to the expiry date.';
$string['error:info_fixduedatemismatchexpiry'] = 'This problem may have occurred in the past due to a bug when an extension was granted. If this was the case then it can be fixed by replacing the Expiry Date with the Due Date.';
$string['error:info_fixexpiredmissingtimedue'] = 'Either manually set the due date, or apply the automated fix below. The automated fix will set the due date to the expiry date of the latest history record which is before the current date. If no such record exists then a log message will be created explaining the problem and the due date will need to be set manually.';
$string['error:info_fixmismatchedexpiredtimeduefromassignment'] = 'This problem may have been caused by additional user assignments causing the due date to be updated after a user had certified. If this was the case then this can be fixed by replacing the due date with the expiry date';
$string['error:info_fixmissingcompletion'] = 'The user is assigned, but the prog_completion and/or certif_completion record is missing. This fix will create the missing records.';
$string['error:info_fixprogcompletiondatematchpart1'] = 'The \'Program completion date\' is not displayed to users or processed internally in relation to certifications. This check was performed because, under normal circumstances, the two dates should be the same, and indicates that some problem has occurred.<br>
One known cause of this problem is when the incorrect completion date is calculated for the certification completion record. In this situation, the solution is to copy the \'Program completion date\' to the \'Completion date\'. The automatic fix will do this. Additionally, if the certification is currently set to \'Use certification completion date\' to calculate expiry, the \'Window open date\' and \'Expiry date\' will automatically be recalculated using the current settings. Otherwise, the window and expiry dates are left unchanged - this could result in inaccurate window open and expiry dates (e.g. when a user had no due date at the time of certification, or certification settings are different now than when the user certified) or could prevent the fix from being applied (when program completion date is greater than the window open date) - both of these cases must be fixed manually.';
$string['error:info_fixprogcompletiondatematchpart2'] = 'The alternative solution is to simply copy the \'Completion date\' to the \'Program completion date\'.';
$string['error:info_fixprogincompletecause'] = 'The program completion record appears complete, but the certification completion appears incomplete. This problem could have been caused in previous versions when a user was certified, before the recertification window opened, the user was then unassigned, and later re-assigned.';
$string['error:info_fixprogincompletenohistory'] = 'If no history record exists, use this automatic fix to change the program completion record to \'Incomplete\'. Users will be put into the primary certification path, but might automatically be marked certified again by cron, depending on the state of their courses (which are likely still marked complete). Beware that clicking this link will apply the change to all currently selected records. If some records need to be fixed using a different method then they should be done first, or you could activate this fix while editing a single completion record.';
$string['error:info_fixprogincompletewithhistory'] = 'If there is a history completion record which has a matching expiry date, is \'Unassigned\' and \'Certified, before window opens\' then this automated fix can be applied. It will copy the details of the history record over the current completion record and then remove the history record. The result is that the user will be set to \'Certified, before window opens\'. Note that if the window open date or expiry date have passed then the user will progress through these states (including course reset when the recertification window opens) when cron next runs.';
$string['error:info_fixprogstatusreset'] = 'This solution will simply change the program status to \'Program incomplete\' and completion date to empty (0). This should only be done if you are sure that the user\'s courses were reset correctly when the recertification window opened.';
$string['error:info_fixunassignedcertifcompletionrecord'] = 'The user has a current certif_completion record, but they are not assigned. This fix will remove (and move to history, where appropriate) the completion records.';
$string['error:info_fixwindowreopen'] = 'Users affected by this problem will see the incorrect certification status in various places, such as the Progress bar in the Record of Learning: Certifications report.<br>
There are two solutions to this problem. The first is to move the state of the certification backwards, so that it is before the recertification window had opened. Then, when cron runs, the window will re-open, causing the program status and completion date to be set correctly AND the user\'s courses will be reset. Any course progress will be lost! This solution should only be used if the user\'s courses were NOT correctly reset when the recertification window last opened.';
$string['error:histalreadyexists'] = 'Certification history already exists certifid={$a->certifid}, timeexpires={$a->timeexpires}';
$string['error:incorrectcertifid'] = 'Incorrect certification ID certifid={$a}';
$string['error:incorrectid'] = 'Incorrect certification completion ID or user ID';
$string['error:invalidaction'] = 'Invalid action: {$a}';
$string['error:invalidunassignedhist'] = 'Only one historical record can be marked as unassigned, and only if there is no current assignment';
$string['error:cannotloadcompletionrecords'] = 'Tried to load certif_completion and prog_completion records which don\'t exist for programid: {$a->programid}, userid: {$a->userid}';
$string['error:minimumactiveperiod'] = 'Active period must be greater than the recertification window period';
$string['error:minimumactiveperiodactive'] = 'Minimum active period cannot be greater than the active period';
$string['error:minimumactiveperiodwindow'] = 'Minimum active period cannot be less than the recertification window period';
$string['error:minimumwindowperiod'] = 'Recertification window period must be at least {$a}';
$string['error:missingcertifid'] = 'Attempting to create certification completion record for non-certification program.';
$string['error:missingcompletion'] = 'Completion records missing';
$string['error:missingprogcompletion'] = 'Missing program completion record for certifid={$a->certifid} userid={$a->userid}';
$string['error:multipleunassignedhistoryrecords'] = 'There is already an unassigned history record for certifid={$a->certifid} userid={$a->userid}';
$string['error:mustbepositive'] = 'Number must be positive';
$string['error:nullactiveperiod'] = 'Recertification active period is not set';
$string['error:nullwindowperiod'] = 'Recertification window period is not set';
$string['error:progstatusinvalid'] = 'Program status for courseset 0 record must be STATUS_PROGRAM_INCOMPLETE or STATUS_PROGRAM_COMPLETE! This indicates a potentially major problem and should be reported to Totara support.';
$string['error:stateassigned-baselinetimeexpiresnotempty'] = 'Baseline expiry date should be empty when user is newly assigned.';
$string['error:stateassigned-pathincorrect'] = 'Certification path should be \'Certification\' when user is newly assigned.';
$string['error:stateassigned-progstatusincorrect'] = 'Program status should be \'Program incomplete\' when user is newly assigned.';
$string['error:stateassigned-progtimecompletednotempty'] = 'Program completion date should be empty when user is newly assigned.';
$string['error:stateassigned-renewalstatusincorrect'] = 'Renewal status should be \'Not due for renewal\' when user is newly assigned.';
$string['error:stateassigned-timecompletednotempty'] = 'Completion date should be empty when user is newly assigned.';
$string['error:stateassigned-timeexpiresnotempty'] = 'Expiry date should be empty when user is newly assigned.';
$string['error:stateassigned-timewindowopensnotempty'] = 'Window open date should be empty when user is newly assigned.';
$string['error:stateassigned-timedueunknown'] = 'Due date should not be \'UNKNOWN\' (0).';
$string['error:statecertified-baselinetimeexpiresempty'] = 'Baseline expiry date should not be empty when user is certified and recertification window has not yet opened.';
$string['error:statecertified-baselinetimeexpirestimewindowopensnotordered'] = 'Baseline expiry date should not be before Window open date when user is certified and recertification window has not yet opened.';
$string['error:statecertified-certprogtimecompleteddifferent'] = 'Program completion date should be the same as Completion date when user is certified and recertification window has not yet opened.';
$string['error:statecertified-pathincorrect'] = 'Certification path should be \'Recertification\' when user is certified and recertification window has not yet opened.';
$string['error:statecertified-progstatusincorrect'] = 'Program status should be \'Program complete\' when user is certified and recertification window has not yet opened.';
$string['error:statecertified-progtimecompletedempty'] = 'Program completion date should not be empty when user is certified and recertification window has not yet opened.';
$string['error:statecertified-renewalstatusincorrect'] = 'Renewal status should be \'Not due for renewal\' when user is certified and recertification window has not yet opened.';
$string['error:statecertified-timecompletedempty'] = 'Completion date should not be empty when user is certified and recertification window has not yet opened.';
$string['error:statecertified-timedueempty'] = 'Due date should not be empty when user is certified and recertification window has not yet opened.';
$string['error:statecertified-timeexpiresempty'] = 'Expiry date should not be empty when user is certified and recertification window has not yet opened.';
$string['error:statecertified-timeexpirestimeduedifferent'] = 'Due date should be the same as Expiry date when user is certified and recertification window has not yet opened.';
$string['error:statecertified-timeexpirestimewindowopensnotordered'] = 'Expiry date should not be before Window open date when user is certified and recertification window has not yet opened.';
$string['error:statecertified-timewindowopensempty'] = 'Window open date should not be empty when user is certified and recertification window has not yet opened.';
$string['error:statecertified-timewindowopenstimecompletednotordered'] = 'Window open date should not be before Completion date when user is certified and recertification window has not yet opened.';
$string['error:stateexpired-baselinetimeexpiresnotempty'] = 'Baseline expiry date should be empty when user\'s certification has expired.';
$string['error:stateexpired-pathincorrect'] = 'Certification path should be \'Certification\' when user\'s certification has expired.';
$string['error:stateexpired-progstatusincorrect'] = 'Program status should be \'Program incomplete\' when user\'s certification has expired.';
$string['error:stateexpired-progtimecompletednotempty'] = 'Program completion date should be empty when user\'s certification has expired.';
$string['error:stateexpired-renewalstatusincorrect'] = 'Renewal status should be \'Renewal expired\' when user\'s certification has expired.';
$string['error:stateexpired-timecompletednotempty'] = 'Completion date should be empty when user\'s certification has expired.';
$string['error:stateexpired-timedueempty'] = 'Due date should not be empty when user\'s certification has expired.';
$string['error:stateexpired-timeexpiresnotempty'] = 'Expiry date should be empty when user\'s certification has expired.';
$string['error:stateexpired-timewindowopensnotempty'] = 'Window open date should be empty when user\'s certification has expired.';
$string['error:stateinvalidstatus'] = 'A valid status must be set (select a Certification completion state above)';
$string['error:statewindowopen-baselinetimeexpiresempty'] = 'Baseline expiry date should not be empty when user is certified and recertification window is open.';
$string['error:statewindowopen-baselinetimeexpirestimewindowopensnotordered'] = 'Baseline expiry date should not be before Window open date when user is certified and recertification window is open.';
$string['error:statewindowopen-pathincorrect'] = 'Certification path should be \'Recertification\' when user is certified and recertification window is open.';
$string['error:statewindowopen-progstatusincorrect'] = 'Program status should be \'Program incomplete\' when user is certified and recertification window is open.';
$string['error:statewindowopen-progtimecompletednotempty'] = 'Program completion date should be empty when user is certified and recertification window is open.';
$string['error:statewindowopen-renewalstatusincorrect'] = 'Renewal status should be \'Due for renewal\' when user is certified and recertification window is open.';
$string['error:statewindowopen-timecompletedempty'] = 'Completion date should not be empty when user is certified and recertification window is open.';
$string['error:statewindowopen-timedueempty'] = 'Due date should not be empty when user is certified and recertification window is open.';
$string['error:statewindowopen-timeexpiresempty'] = 'Expiry date should not be empty when user is certified and recertification window is open.';
$string['error:statewindowopen-timeexpirestimeduedifferent'] = 'Due date should be the same as Expiry date when user is certified and recertification window is open.';
$string['error:statewindowopen-timeexpirestimewindowopensnotordered'] = 'Expiry date should not be before Window open date when user is certified and recertification window is open.';
$string['error:statewindowopen-timewindowopensempty'] = 'Window open date should not be empty when user is certified and recertification window is open.';
$string['error:statewindowopen-timewindowopenstimecompletednotordered'] = 'Window open date must be after Completion date when user is certified and recertification window is open.';
$string['error:timeexpiresbeforetimecompleted'] = 'Expiry date must be after completion date';
$string['error:unassignedcertifcompletion'] = 'Completion exists for unassigned user';
$string['error:updatinginvalidcompletionrecords'] = 'Call to certif_write_completion with completion records that do not match each other or the existing records';
$string['error:updatinginvalidcompletionhistoryrecord'] = 'Call to certif_write_completion_history with completion record that does not match the existing record';
$string['error:useralreadyassigned'] = 'user already assigned for certifid={$a->certifid} userid={$a->userid}';
$string['error:validationfailureassign'] = 'user assignment validation failure(s) while attempting to assign userid={$a->userid} to certifid={$a->certifid}';
$string['eventcompletionhistoryadded'] = 'Certification completion history added';
$string['eventcompletionhistorydeleted'] = 'Certification completion history deleted';
$string['eventcompletionhistoryedited'] = 'Certification completion history edited';
$string['eventexpired'] = 'Certification expired';
$string['eventupdated'] = 'Certification\'s setting updated';
$string['eventwindowopened'] = 'Certification window opened';
$string['exceptiontypealreadyassigned'] = 'Already assigned to certification';
$string['findcertifications'] = 'Find certifications';
$string['header:certification'] = '{$a} (Certification)';
$string['header:certificationcontent'] = 'Certification content';
$string['learningcomptype'] = 'Learning component';
$string['legend:recertfailrecertmessage'] = 'FAILURE TO RECERTIFY MESSAGE';
$string['legend:recertwindowdueclosemessage'] = 'RECERTIFICATION WINDOW DUE TO CLOSE MESSAGE';
$string['legend:recertwindowopenmessage'] = 'RECERTIFICATION WINDOW OPEN MESSAGE';
$string['managecertifications'] = 'Manage certifications';
$string['managecertifsinthiscat'] = 'Manage certifications in this category';
$string['minprogramtimerequired'] = 'Certifications total minimum time required: ';
$string['minimumtimerequired'] = 'Minimum time required';
$string['minimumtimerequired_help'] = 'This value indicates a minimum amount of time that a user might realistically need to be able to complete the course set. It is used to determine if the completion period set on the **assignments** tab is realistic for a particular group of users. If the assignment is not realistic, a **time allowance** exception will be generated and the user will not be assigned to the certification until the exception has been resolved.

For example, consider a certification consisting of a single course set with a minimum time required of 10 days. If a user was assigned with completion criteria that required them to complete it in less than 10 days, then it would raise an exception report for that user.

When using completion criteria relative to a user, it is possible for some users to generate exceptions but not others. For example, when using the **days since first login** criteria, each user would have their own deadline that may or may not be realistic.

When multiple course sets exist in a certification the overall minimum time required for the certification is calculated based on the worst-case scenario taking into account the course set logic. For example if a certification consists of:

Course set1 [10 days] THEN Course set2 [5 days] OR Course set3 [7 days]

then the overall time allowance would be 17 days.

This minimum time value is also used to determine when the \'Course set due message\' and \'Course set overdue message\' should be sent.';
$string['months'] = 'Month(s)';
$string['moveselectedcertificationsto'] = 'Move selected certifications to...';
$string['na'] = 'N/A'; // Abbreviation for not applicable.
$string['nocertificationcontent'] = 'Certification does not contain any content';
$string['nocertifdetailsfound'] = 'No certification details setup';
$string['nocertifications'] = 'No certifications';
$string['nolongeravailabletolearners'] = 'This certification is no longer available to learners.';
$string['noprogramassignments'] = 'Certification does not contain any assignments';
$string['notapplicable'] = 'Not applicable';
$string['notassigned'] = 'Not assigned';
$string['notcertified'] = 'Not certified';
$string['notduetostartuntil'] = 'This certification is not yet available. Learner assignments will be applied following the start date.';
$string['notification_assigned_for_managers_body'] = 'Your team member [subject:full_name_link] is now assigned on [certification:full_name].

Go to [certification:full_name_link]';
$string['notification_assigned_for_managers_subject'] = '[subject:full_name] has been assigned in [certification:full_name]';
$string['notification_assigned_for_managers_title'] = 'Team member assigned in certification';
$string['notification_assigned_for_subject_body'] = 'You are now assigned on certification [certification:full_name].

Go to [certification:full_name_link]';
$string['notification_assigned_for_subject_subject'] = 'You have been assigned in [certification:full_name]';
$string['notification_assigned_for_subject_title'] = 'Learner assigned in certification';
$string['notification_assigned_resolver_title'] = 'Learner assigned in certification';
$string['notification_certification_placeholder_group'] = 'Certification {$a}';
$string['notification_completed_for_subject_body'] = 'You have completed [certification:full_name].

Go to certification: [certification:full_name_link]';
$string['notification_completed_for_subject_subject'] = 'You have completed [certification:full_name]';
$string['notification_completed_for_subject_title'] = 'Certification completed';
$string['notification_completed_for_managers_body'] = 'Your team member [subject:full_name_link] has completed [certification:full_name].

Go to program: [certification:full_name_link]';
$string['notification_completed_for_managers_subject'] = '[subject:full_name] has completed [certification:full_name]';
$string['notification_completed_for_managers_title'] = 'Certification completed';
$string['notification_completed_resolver_title'] = 'Certification completed';
$string['notification_course_set_completed_resolver_title'] = 'Course set completed';
$string['notification_course_set_completed_for_subject_body'] = 'You have completed [course_set:label] in [certification:full_name].

Go to certification: [certification:full_name_link]';
$string['notification_course_set_completed_for_subject_subject'] = 'You have completed [course_set:label] in [certification:full_name]';
$string['notification_course_set_completed_for_subject_title'] = 'Certification course set completed';
$string['notification_course_set_completed_for_managers_body'] = 'Your team member [subject:full_name_link] has completed [course_set:label] in [certification:full_name].

Go to certification: [certification:full_name_link]';
$string['notification_course_set_completed_for_managers_subject'] = '[subject:full_name] has completed [course_set:label] in [certification:full_name]';
$string['notification_course_set_completed_for_managers_title'] = 'Certification course set completed';
$string['notification_course_set_due_date_resolver_title'] = 'Course set due date';
$string['notification_due_date_resolver_title'] = 'Certification due date';
$string['notification_expiry_date_resolver_title'] = 'Certification expiry date';
$string['notification_failure_to_recertify_for_subject_body'] = '[certification:full_name] is overdue.

Go to [certification:full_name_link] to complete your learning.';
$string['notification_failure_to_recertify_for_subject_subject'] = '[certification:full_name] is overdue';
$string['notification_failure_to_recertify_for_subject_title'] = 'Failed to re-certify';
$string['notification_failure_to_recertify_resolver_title'] = 'Failure to re-certify';
$string['notification_log_assigned_resolver'] = '{$a->resolver_title}: Learner "{$a->user}", certification "{$a->cert}"';
$string['notification_log_completed_resolver'] = '{$a->resolver_title}: Learner "{$a->user}", certification "{$a->cert}"';
$string['notification_log_course_set_completed_resolver'] = '{$a->resolver_title}: Learner "{$a->user}", certification "{$a->cert}", course set "{$a->courseset}"';
$string['notification_log_course_set_due_date_resolver'] = '{$a->resolver_title}: Learner "{$a->user}", certification "{$a->cert}", course set "{$a->courseset}"';
$string['notification_log_due_date_resolver'] = '{$a->resolver_title}: Learner "{$a->user}", certification "{$a->cert}"';
$string['notification_log_expiry_date_resolver'] = '{$a->resolver_title}: Learner "{$a->user}", certification "{$a->cert}';
$string['notification_log_failure_to_recertify_resolver'] = '{$a->resolver_title}: Learner "{$a->user}", certification "{$a->cert}"';
$string['notification_log_new_exception_resolver'] = '{$a->resolver_title}: Certification "{$a->cert}"';
$string['notification_log_unassigned_resolver'] = '{$a->resolver_title}: Learner "{$a->user}", certification "{$a->cert}"';
$string['notification_log_window_open_date_resolver'] = '{$a->resolver_title}: Certification "{$a->cert}"';
$string['notification_new_exception_for_site_admin_body'] = 'There are exceptions in certification [certification:full_name] which need to be resolved.

Go to certification: [certification:full_name_link]';
$string['notification_new_exception_for_site_admin_subject'] = 'Exceptions require attention in certification [certification:full_name]';
$string['notification_new_exception_for_site_admin_title'] = 'New exception reported';
$string['notification_new_exception_resolver_title'] = 'New exception reported';
$string['notification_unassigned_for_managers_body'] = 'Your team member [subject:full_name_link] is un-assigned from [certification:full_name].

Go to certification: [certification:full_name_link]';
$string['notification_unassigned_for_managers_subject'] = '[subject:full_name] has been un-assigned from [certification:full_name]';
$string['notification_unassigned_for_managers_title'] = 'User unassignment';
$string['notification_unassigned_for_subject_body'] = 'You are no longer assigned on [certification:full_name].';
$string['notification_unassigned_for_subject_subject'] = 'You have been un-assigned from [certification:full_name]';
$string['notification_unassigned_for_subject_title'] = 'User unassignment';
$string['notification_unassigned_resolver_title'] = 'Learner un-assigned from certification';
$string['notification_window_open_date_for_subject_body'] = '[certification:full_name] is ready for renewal.

Go to [certification:full_name_link] to complete your learning.';
$string['notification_window_open_date_for_subject_subject'] = '[certification:full_name] is ready for renewal';
$string['notification_window_open_date_for_subject_title'] = 'Recertification window opened';
$string['notification_window_open_date_resolver_title'] = 'Recertification window open date';
$string['oricertpath'] = 'Original certification path';
$string['oricertpathdesc'] = 'Define the content required for the original certification path.';
$string['perioddays'] = '{$a} day(s)';
$string['periodmonths'] = '{$a} month(s)';
$string['periodweeks'] = '{$a} week(s)';
$string['periodyears'] = '{$a} year(s)';
$string['pluginname'] = 'Certification';
$string['prog_recert_failrecert_message'] = 'Program recertification failure message';
$string['prog_recert_windowdueclose_message'] = 'Program Recertification Window due close message';
$string['prog_recert_windowopen_message'] = 'Program recertification window open message';
$string['program'] = 'Program';
$string['programassignments'] = 'Certification assignments';
$string['programavailability'] = 'Certification Availability';
$string['programavailability_help'] = 'This option allows you to hide your certification completely.

It will not appear on any certification listings, except to administrators.

Even if learners try to access the certification URL directly, they will not be allowed to enter.

If you set the **Available from** and **Available until** dates, learners will be able to find and enter the certification during the period specified by the dates but will be prevented from accessing the certification outside of those dates.';
$string['programdetails'] = 'Certification details';
$string['programfullname'] = 'Certification Fullname';
$string['programfullname_help'] = 'The full name of the certification is displayed at the top of the screen and in the certification listings.';
$string['programidnumber'] = 'Certification ID number';
$string['programidnumber_help'] = 'The ID number of a certification is only used when matching this course against external systems - it is never displayed within Totara. If you have an official code name for this program then use it here, otherwise you can leave it blank.';
$string['programoverviewfiles'] = 'Summary files';
$string['programoverviewfiles_help'] = 'Certification summary files, such as images, are displayed in the list of certifications together with the summary.';
$string['programshortname'] = 'Certification Short Name';
$string['programshortname_help'] = 'The certification shortname will be used in several places where the full name isn\'t appropriate (such us in the subject line of an alert message).';
$string['programvisibility'] = 'Certification Visibility';
$string['programvisibility_help'] = 'If the certification is visible, it will appear in certification listings and search results and learners will be able to view the certification contents.

If the certification is not visble, it will not appear in certification listings or search results but the certification will still be displayed in the learning plans of any learners who have been assigned to the certification and learners can still access the certification if they know the certification\'s URL.';
$string['instructions:programdetails'] = 'Define the certification name, availability and description';

$string['programcategory'] = 'Certification Category';
$string['programcategory_help'] = 'Your Totara administrator may have set up several certification/course categories.

For example, \'Human Resources\', \'Software development\', \'Marketing\' etc.

Choose the one most applicable for your certification. This choice will affect where your certification is displayed on the certification listing and may make it easier for learners to find your certification.';
$string['programenrolledincohort'] = 'Certification with enrolled audience(s)';
$string['programexpandlink'] = 'Certification Name (expanding details)';
$string['programname'] = 'Certification Name';
$string['programshortname'] = 'Certification Short Name';
$string['programidnumber'] = 'Certification ID number';
$string['programid'] = 'Certification ID';
$string['programsummary'] = 'Certification Summary';
$string['programvisible'] = 'Certification Visible';
$string['programvisibledisabled'] = 'Certification Visible (not applicable)';
$string['prognamelinkedicon'] = 'Certification Name and Linked Icon';
$string['recertdatetype'] = 'Recertification method';
$string['recertfailrecert'] = 'Failure to recertify';
$string['recertfailrecertmessage'] = 'Failure to recertify message';
$string['recertfailrecertmessage_help'] = 'This message will be sent when a recertification period has expired and the learner will need to repeat the original certification.';
$string['recertification'] = 'Recertification';
$string['recertpath'] = 'Recertification path';
$string['recertpathdesc'] = 'Define the recertification path';
$string['recertwindowdueclose'] = 'Recertification window due to close';
$string['recertwindowdueclosemessage'] = 'Recertification window due to close message';
$string['recertwindowdueclosemessage_help'] = 'This message will be sent when a recertification period is about to expire.';
$string['recertwindowexpiredate'] = ' Your certification will expire on {$a}';
$string['recertwindowopen'] = 'Recertification window open.';
$string['recertwindowopendate'] = ' The recertification window will open on {$a}';
$string['recertwindowopendatelate'] = 'The recertification window was due to open on {$a} but has not yet occurred. Window opening normally occurs automatically within one day of the window open date, so will likely occur soon. You should wait until this problem is resolved before making progress towards recertification. Progress made before the recertification window opens will likely be lost.';
$string['recertwindowopendateverylate'] = 'ATTENTION! The recertification window was due to open on {$a} but has not yet occurred. Window opening should normally have occurred automatically by now, as more than one day has passed. You should contact your site administrator and inform them of this message. You should wait until this problem is resolved before making progress towards recertification. Progress made before the recertification window opens will likely be lost.';
$string['recertwindowopenmessage'] = 'Recertification window open message';
$string['recertwindowopenmessage_help'] = 'This message will be sent when a learner has entered the period when they can recertify.';
$string['removedfromprogram'] = '\'{$a}\' has been removed from the certification';
$string['renewalstatus_dueforrenewal'] = 'Due for renewal';
$string['renewalstatus_expired'] = 'Renewal expired';
$string['renewalstatus_notdue'] = 'Not due for renewal';
$string['returntomembership'] = 'Return to certification membership';
$string['sameascert'] = 'Use the existing certification content';
$string['saveallchanges'] = 'Save all changes';
$string['searchcertifications'] = 'Search certifications';
$string['stateassigned'] = 'Newly assigned';
$string['statecertified'] = 'Certified, before window opens';
$string['stateexpired'] = 'Expired';
$string['stateinvalid'] = 'Invalid state - select a valid state';
$string['statewindowopen'] = 'Certified, window is open';
$string['status_assigned'] = 'Assigned';
$string['status_certified'] = 'Certified';
$string['status_completed'] = 'Completed';
$string['status_expired'] = 'Expired';
$string['status_inprogress'] = 'In progress';
$string['status_notcertified'] = 'Not certified';
$string['status_unset'] = 'Unset';
$string['summary'] = 'Summary';
$string['summary_help'] = 'Summary description of the certification.';
$string['timeallowance'] = 'Minimum time required for recertification is {$a->timestring}';
$string['tocertification'] = 'to certification';
$string['tosaveall'] = 'To save all changes, click \'Save all changes\'. To edit click \'Edit certification details\'. Saving changes cannot be undone.';
$string['totallearnersassigned'] = 'Total learners assigned';
$string['totallearnersassigned_help'] = 'This total may differ from the sum of learners in groups below. This is because some users\' assignments may not have been processed by the system or the certification may not currently be active. Some users may also be in multiple assigned groups, but they are only assigned once.';
$string['type_competency'] = 'Competency';
$string['type_course'] = 'Course';
$string['type_program'] = 'Program';
$string['type_unset'] = 'Unset';
$string['unset'] = 'Unset';
$string['updatecertificationstask'] = 'Update certifications';
$string['userdataitemassignment_completion'] = 'Certification assignments and completion';
$string['userdataitemassignment_completion_help'] = 'When purging, a new assignment may be triggered after purging is complete if the user still meets the assignment criteria (due to audience, organisation, position or management hierarchy).';
$string['viewallcertifications'] = 'View all certifications';
$string['viewcertification'] = 'View certification';
$string['weeks'] = 'Week(s)';
$string['windowopen'] = 'Open';
$string['windowopenin1day'] = 'Opens in 1 day';
$string['windowopeninxdays'] = 'Opens in {$a} days';
$string['windowperiod'] = 'Window Period';
$string['years'] = 'Year(s)';
$string['youhaveunsavedchanges'] = 'You have unsaved changes.';
$string['youareassigned'] = 'You are assigned to this certification';

// Deprecated since Totara 13
$string['error:info_fixprogincomplete'] = 'There are two known methods of resolving this problem.<br>
It could have been caused in previous versions when a user was certified, before the recertification window opened, the user was then unassigned, and later re-assigned. If the record has a history completion record (most likely marked \'unassigned\') then this is likely the cause. In this case, you should manually edit the completion record, copying the details of the history record, then remove the history record. This will restore the user into the certification in the state that they were in before being unassigned.<br>
If no history record exists, use the automatic fix to change the program completion record to \'incomplete\'. Users will be put into the primary certification path, but might automatically be marked certified again by cron, depending on the state of their courses (which are likely still marked complete). Beware that clicking this link will apply the change to all currently selected records. If some records need to be fixed using the method above, they should be done first, or you could activate this fix while editing a single completion record.';
$string['createnewcertifprog'] = 'Create new certification program';
$string['certifprogramcreatesuccess'] = 'Certification program creation successful';
