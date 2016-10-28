<?php
namespace Gold\Engine;

/* FIFO (first in first out) stack of Tokens. */

class TokenStackStruct {

    public /* struct TokenStruct* */ $Token;
    public /* int */ $LalrState;       /* Index into Grammar.LalrArray[]. */
    public /* struct TokenStackStruct* */ $NextToken; /* Pointer to next item. */

}

?>