<?php

class HfGoals implements Hf_iGoals {
    private $Database;
    private $View;
    private $ContentManagementSystem;
    private $Messenger;

    function __construct( Hf_iMessenger $Messenger, Hf_iContentManagementSystem $ContentManagementSystem, Hf_iMarkupGenerator $View, Hf_iDatabase $Database ) {
        $this->Messenger               = $Messenger;
        $this->ContentManagementSystem = $ContentManagementSystem;
        $this->View                    = $View;
        $this->Database                = $Database;
    }

    function generateGoalCard( $sub ) {
        $goalID        = intval( $sub->goalID );
        $userID        = intval( $sub->userID );
        $goal          = $this->Database->getRow( 'hf_goal', 'goalID = ' . $goalID );
        $daysOfSuccess = $this->daysOfSuccess( $goalID, $userID );
        $level         = $this->Database->level( $daysOfSuccess );
        $wrapperOpen   = '<div class="report-card">';
        $info          = '<div class="about"><h2>' . $goal->title . '</h2>';
        if ( $goal->description != '' ) {
            $info .= '<p>' . $goal->description . '</p></div>';
        } else {
            $info .= '</div>';
        }

        $controls     = "<div class='controls'>
					<label><input type='radio' name='" . $goalID . "' value='0'> &#x2714;</label>
					<label><input type='radio' name='" . $goalID . "' value='1'> &#x2718;</label>
				</div>";
        $report       = "<div class='report'>Have you fallen since your last check-in?" . $controls . "</div>";
        $main         = '<div class="main">' . $info . $report . '</div>';
        $stat1        = '<p class="stat">Level <span class="number">' . $level->levelID . '</span> ' . $level->title . '</p>';
        $stat2        = '<p class="stat">Level <span class="number">' . round( $this->levelPercentComplete( $goalID, $userID ), 1 ) . '%</span> Complete</p>';
        $stat3        = '<p class="stat">Days to <span class="number">' . round( $this->daysToNextLevel( $goalID, $userID ) ) . '</span> Next Level</p>';
        $bar          = $this->levelBarForGoal( $goalID, $userID );
        $stats        = '<div class="stats">' . $stat1 . $stat2 . $stat3 . $bar . '</div>';
        $wrapperClose = '</div>';

        return $wrapperOpen . $main . $stats . $wrapperClose;
    }

    function levelPercentComplete( $goalId, $userId ) {
        $daysOfSuccess = $this->daysOfSuccess( $goalId, $userId );

        return ( $this->daysOfSuccess( $goalId, $userId ) / $this->currentLevelTarget( $daysOfSuccess ) ) * 100;
    }

    function daysOfSuccess( $goalId, $userId ) {
        $dateInSecondsOfFirstSuccess = $this->Database->timeOfFirstSuccess( $goalId, $userId );
        $dateInSecondsOfLastSuccess  = $this->Database->timeOfLastSuccess( $goalId, $userId );
        $dateInSecondsOfLastFail     = $this->Database->timeOfLastFail( $goalId, $userId );

        $secondsInADay = 86400;

        if ( !$dateInSecondsOfLastSuccess ) {
            $daysOfSuccess = 0;
        } elseif ( !$dateInSecondsOfLastFail ) {
            $daysOfSuccess = ( $dateInSecondsOfLastSuccess - $dateInSecondsOfFirstSuccess ) / $secondsInADay;
        } else {
            $difference    = $dateInSecondsOfLastSuccess - $dateInSecondsOfLastFail;
            $daysOfSuccess = $difference / $secondsInADay;
            if ( $daysOfSuccess < 0 ) {
                $daysOfSuccess = 0;
            }
        }

        return $daysOfSuccess;
    }

    function currentLevelTarget( $daysOfSuccess ) {
        $level = $this->Database->level( $daysOfSuccess );

        return $level->target;
    }

    function daysToNextLevel( $goalId, $userId ) {
        $daysOfSuccess = $this->daysOfSuccess( $goalId, $userId );
        $target        = $this->currentLevelTarget( $daysOfSuccess );

        return $target - $daysOfSuccess;
    }

    function levelBarForGoal( $goalId, $userId ) {
        $percent = $this->levelPercentComplete( $goalId, $userId );

        return $this->View->progressBar( $percent, '' );
    }

    function sendReportRequestEmails() {
        $users = $this->ContentManagementSystem->getSubscribedUsers();

        foreach ( $users as $user ) {
            if ( $this->isAnyGoalDue( $user->ID ) and !$this->Messenger->isThrottled( $user->ID ) ) {
                $this->Messenger->sendReportRequestEmail( $user->ID );
            }
        }
    }

    private function isAnyGoalDue( $userId ) {
        $goalSubs = $this->Database->getRows( 'hf_user_goal', 'userID = ' . $userId );
        foreach ( $goalSubs as $goalSub ) {
            if ( $this->isGoalDue( $goalSub->goalID, $userId ) ) {
                return true;
            }
        }

        return false;
    }

    private function isGoalDue( $goalId, $userId ) {
        $daysOfSuccess       = $this->daysOfSuccess( $goalId, $userId );
        $level               = $this->Database->level( $goalId, $userId, $daysOfSuccess );
        $emailInterval       = $level->emailInterval;
        $daysSinceLastReport = $this->Database->daysSinceLastReport( $goalId, $userId );

        return $daysSinceLastReport > $emailInterval;
    }

    public function getGoalTitle( $goalId ) {
        return $this->Database->getGoal( $goalId )->title;
    }

    public function recordAccountabilityReport( $userId, $goalId, $isSuccessful, $emailId = null ) {
        $this->Database->recordAccountabilityReport( $userId, $goalId, $isSuccessful, $emailId );
    }

    public function getGoalSubscriptions( $userId ) {
        return $this->Database->getGoalSubscriptions( $userId );
    }
} 