<?php

class RoundGenerator {
	public $aPossibleMatches = array();	
	public $aPlayedMatches = array();
		
	public $aTeams = array();
	
	private $recurseMatchResult = false;

	public function __construct($aTeams, $aPlayedMatches, $iRound)
	{
		$this->aPlayedMatches = $aPlayedMatches;
		$this->aTeams = $aTeams;
	}
	
	public function execute($ignoreMatchesAlreadyPlayed = false)
	{
		$this->aPossibleMatches = array();

		// Generate possible new round
		for($i = 0; $i < count($this->aTeams); $i++) {
		    $j = $i + 1;
		    
		    if(!$this->recurseMatch($i, $j, $ignoreMatchesAlreadyPlayed)) {
		    	// recurseMatch returned false, this means the round it was building wasn't possible at a certain level
		    	
		    	// Remove a higher level and choose an lower opponent, then recheck if the round is possible
		    	$aLastMatchPossibleMatches = array_pop($this->aPossibleMatches);
		    	if(empty($this->aPossibleMatches)) {				        
				    	$this->execute(true);
			    } else {
			    	$this->recurseMatch($aLastMatchPossibleMatches["team1"], $aLastMatchPossibleMatches["team2"] + 1, $ignoreMatchesAlreadyPlayed);
			    }
		    	
		    	$i--;
		    }
		}
		
		return $this->aPossibleMatches;
	}
	
	private function recurseMatch($currentTeamId, $opponentTeamId, $ignoreMatchesAlreadyPlayed = false)
	{
		// If current team already in possibleMatches it doesn't have to be set anymore
	    if(!$this->idExistsInPossibleMatches($this->aTeams[$currentTeamId]["id"])) {
	    	// If the array id of the opponent exceeds the number of teams, reset it to nill
			if($opponentTeamId >= count($this->aTeams)) $opponentTeamId = 0;

	        // Penalty based on distance teams, this is absolute to prevent negative values
       		$iPenalty =  abs($opponentTeamId - $currentTeamId);
        	$aMatchOption = array("team1" => $this->aTeams[$currentTeamId]["id"], "team2" => $this->aTeams[$opponentTeamId]["id"], "penalty" => $iPenalty);

			if($this->matchIsValid($aMatchOption, $currentTeamId, $opponentTeamId, $ignoreMatchesAlreadyPlayed)) {
				// Match is possible, set it
	            $this->aPossibleMatches[] = $aMatchOption;
	            $this->recurseMatchResult = true;
		    } else {
		    	if($this->aTeams[$currentTeamId]["id"] != $this->aTeams[$opponentTeamId]["id"]) {
		    		// The match isn't possible between these opponents, but we can still try to match with an other opponent
		            $this->recurseMatch($currentTeamId, $opponentTeamId + 1, $ignoreMatchesAlreadyPlayed);
		        } else {
			        // Match cannot be matched in this possibility with the current upper matchlevels, no other opponents left
		            $this->recurseMatchResult = false;
		        }
			}
	    }
	    
	    //Jippie, match set!
	    return $this->recurseMatchResult;
	}
	
	private function matchIsValid($aMatchOption, $currentTeamId, $opponentTeamId, $ignoreMatchesAlreadyPlayed = false)
	{
		if($this->idExistsInPossibleMatches($this->aTeams[$opponentTeamId]["id"])) return false;
		// This one is important because it stops the loop when the id returns to itself, so it recurses the complete array just once
		if($this->aTeams[$currentTeamId]["id"] == $this->aTeams[$opponentTeamId]["id"]) return false;
		
		if($this->matchExistsInPlayedMatches($aMatchOption) && $ignoreMatchesAlreadyPlayed == false) return false;
		
		return true;
	}
	
	private function matchExistsInPlayedMatches($aMatch)
	{
		unset($aMatch["penalty"]);
		
	    foreach($this->aPlayedMatches as $aPlayedMatch) {
	        if(count(array_intersect($aPlayedMatch, $aMatch)) == count($aMatch)) {
	            // ID already in possible matches array
	            return true;
	        }
	    }
	    
	    return false;
	}
	
	private function idExistsInPossibleMatches($id)
	{
	    $bResult = false;
	    foreach($this->aPossibleMatches as $aPossibleMatch) {
	        if(in_array($id, $aPossibleMatch) && array_search($id, $aPossibleMatch) != "penalty") {
	            // ID already in possible matches array
	            $bResult = true;
	        }
	    }
	    return $bResult;
	}
}

// aTeam may just contain team id's
//$aTeams = array(array("id"=> "1"), array("id"=> "2"), array("id"=> "3"), array("id"=> "4"), array("id"=> "5"), array("id"=> "6"));
// aPlayedMatches contains team id's
// PreDeathlock (algorithm chooses correct): $aPlayedMatches = array(array("team1" => 1, "team2" => 2), array("team1" => 3, "team2" => 4), array("team1" => 5, "team2" => 6), array("team1" => 1, "team2" => 6), array("team1" => 2, "team2" => 3), array("team1" => 4, "team2" => 5));
//PreDeathlock (algorithm chooses wrong in first instance, but correct after deadlock check): $aPlayedMatches = array(array("team1" => 1, "team2" => 4), array("team1" => 1, "team2" => 6), array("team1" => 2, "team2" => 3), array("team1" => 2, "team2" => 5), array("team1" => 3, "team2" => 6), array("team1" => 4, "team2" => 5));
//Deadlock occured: $aPlayedMatches = array(array("team1" => 1, "team2" => 4), array("team1" => 1, "team2" => 6), array("team1" => 2, "team2" => 3), array("team1" => 2, "team2" => 5), array("team1" => 3, "team2" => 6), array("team1" => 4, "team2" => 5), array("team1" => 1, "team2" => 2), array("team1" => 3, "team2" => 4), array("team1" => 5, "team2" => 6));


//$oRoundGenerator = new RoundGenerator($aTeams, $aPlayedMatches);
//$aPossibleMatches = $oRoundGenerator->execute();

//echo "<pre>";
//var_dump($aPossibleMatches);
//echo "</pre>";
