<?php
namespace Gold\Engine;

class LalrStateStruct { /* Grammar.LalrArray[] */

    public /* int */ $ActionCount;       /* Number of items in Actions[] array. */
    public /* class Actionclass* */ $Actions;  /* Array of Actionclass. */

    function __construct($ActionCount,$Actions) {
		$this->ActionCount = $ActionCount;
		$this->Actions = $Actions;
    }

}

?>