<?php

Class MatchCandidate {
  var $poiuuid;
  var $geouuid;
  var $locuuid;
  var $dist;
	var $distscore;
  var $maxdistance = 100;
  var $labels = array(); // associative array of label => scores
  var $score;
  
  /**
   * Score of 0 = perfect match; 1 is no match
   */
  function computeScore($distmodifier=NULL) {
    if ( $distmodifier == NULL ) 
      $distmodifier = $this->maxdistance;
    // distance of 0 = perfect match (dscore of 0), >distmodifier is a 1 match
    // (dscore is from 0 to 1)
    $d = $this->dist;
    if ( $d > $distmodifier ) $d = $distmodifier;
    $this->distscore = $d / $distmodifier;
    // echo "dscore: $this->distscore\n";
    
    // label match: take the lowest score for any of the labels
    // (label score is from 0 to 1)
    $lscore = 1.0;
    foreach( $this->labels as $l => $s) {
      $lscore = $s < $lscore ? $s : $lscore;
    }
    
    // average distance and label scores
    $this->score = ($lscore + $this->distscore) / 2;
    return $this->score;
  }

  function __construct($g, $maxdistance) {
    $this->geouuid = $g;
    if ( $maxdistance != null ) $this->maxdistance = $maxdistance;
  }
  
}

?>