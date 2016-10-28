<?php

namespace Gold;

use Gold\Engine\TokenStackStruct;
use Gold\Engine\TokenStruct;     

abstract class AbstractParser {

	const BUFSIZ = 512; 

	/* Return values of the Parse() function. */
	const PARSEACCEPT = 0; /* Input parsed, no errors. */
	const PARSELEXICALERROR = 1;   /* Input could not be tokenized. */
	const PARSETOKENERROR = 2; /* Input is an invalid token. */
	const PARSESYNTAXERROR = 3;  /* Input does not match any rule. */
	const PARSECOMMENTERROR = 4;   /* A comment was started but not finished. */
	const PARSEMEMORYERROR = 5;  /* Insufficient memory. */

	/* Symbolclass types (defined by GOLD). */
	const SYMBOLNONTERMINAL = 0;
	const SYMBOLTERMINAL = 1;
	const SYMBOLWHITESPACE = 2;
	const SYMBOLEOF = 3;
	const SYMBOLCOMMENTSTART = 4;
	const SYMBOLCOMMENTEND = 5;
	const SYMBOLCOMMENTLINE = 6;
	const SYMBOLERROR = 7;

	/* Actionclass types (defined by GOLD). */
	const ACTIONSHIFT = 1;
	const ACTIONREDUCE = 2;
	const ACTIONGOTO = 3;
	const ACTIONACCEPT = 4;

	/* LALR state machine. Depending on the Token->Symbol the machine will
	  change it's state and perform actions, such as reduce the TokenStack and
	  iteratively call itself. */
	const LALRMEMORYERROR = 0;
	const LALRSYNTAXERROR = 1;
	const LALRACCEPT = 2;
	const LALRSHIFT = 3;
	const LALRGOTO = 4;

	var $InputSize;
	var $InputHere;
	var $Line;
	var $Column;
	var $Length;

	var $LalrState;
	var $TokenStack; 
	var $InputToken; 
	var $TrimReductions;
	var $Debug = 0;

	var $Grammar;
	var $RuleJumpTable = array();

	public function __construct($InputBuf,$TrimReductions,$Debug){
		$this->InputBuf = $InputBuf; // Pointer to the input data. 
		$this->InputSize = strlen($InputBuf) ; // Number of characters in the input. 
		$this->TrimReductions = $TrimReductions;
		$this->Debug = $Debug; 
		$this->FirstToken = NULL;
		$this->initGrammar();
	}

	abstract  public function initGrammar();


	public function ReadString() {

		//$String = str_pad('',$this->Length);
		$String = '';
		$i;

		//String = (wchar_t *)malloc((Length + 1) * sizeof(wchar_t));

		//if ($String == NULL)
		//    return(NULL);
		
		$Length = $this->Length;
		for ($i = 0; $i < $Length; $i++) {
			if ($this->InputHere < $this->InputSize) {
				if ($this->InputBuf[$this->InputHere] == "\r") {
					if (($this->InputHere + 1 < $this->InputSize) &&
							($this->InputBuf[$this->InputHere + 1] != "\n")) {
						$this->Line = $this->Line + 1;
						$this->Column = 0;
					}
				}
				if ($this->InputBuf[$this->InputHere] == "\n") {
					$this->Line = $this->Line + 1;
					$this->Column = 0;
				}
				$String[$i] = $this->InputBuf[$this->InputHere];
				$this->InputHere = $this->InputHere + 1;
				$this->Column = $this->Column + 1;
			} else {
				$String[$i] = "\0";
			}
		}
		
		//$String[$i] = "\0";
		$str = '';
		foreach($String as $c){
			$str .= $c;
		}
		

		return $str;
	}



	/* Search for a character in a characterset. Return 1 if found,
	  0 if not found. */

	//int FindChar(wchar_t ThisChar, wchar_t *CharacterSet, long Count)
	function FindChar($ThisChar, &$CharacterSet, $Count) {

		$ThisChar = ord($ThisChar);
		$Here = 0;
		$Interval = 0;

		$str = "";
		foreach($CharacterSet as $char){
			$str .= chr($char);
		}
		
	 
		/* Use wcschr() for charactersets with a length of up to 11
		  characters. */
		
		if ($Count < 11) {
			//if (wcschr(CharacterSet,ThisChar) != NULL) return(1);
			if (strpos($str, chr($ThisChar))  !== false){
				return(1);
			}
			return(0);
		}

		/* Binary search the characterset for the character. This method is
		  possible because GOLD always produces sorted charactersets.
		  Measurements show that although the code is more complex, this
		  binary search is faster than wcschr() for charactersets longer
		  than 11 characters. At 100 characters it's 4 times faster. */
		$Interval = 32768;
		while ($Interval > $Count)
			$Interval = ($Interval >> 1);
		$Here = $Interval - 1;
		$Interval = ($Interval >> 1);
		while ($Interval > 0) {
			if ($CharacterSet[$Here] == $ThisChar)
				return(1);
			if ($CharacterSet[$Here] > $ThisChar) {
				$Here = $Here - $Interval;
			} else {
				while ($Here + $Interval >= $Count) {
					$Interval = ($Interval >> 1);
					if ($Interval == 0)
						return(0);
				}
				$Here = $Here + $Interval;
			}

			$Interval = ($Interval >> 1);
		}

		if ($CharacterSet[$Here] == $ThisChar)
			return(1);
		return(0);
	}



	function RetrieveToken() {

		$DfaIndex = 0;   /* Index into $this->Grammar->DfaArray[]-> */
		$this->Length = 0;  /* Number of processed characters from Data->InputBuf. */
		$AcceptIndex = 0;  /* Longest found symbol so far. */
		$AcceptLength = 0; /* Length of longest found symbol. */
		$i = 0;

		/* Sanity check (no input). */
		//if (($this->InputBuf == NULL) || ($this->InputBuf[0] == 0))
		if ($this->InputBuf == NULL) {
			$this->Symbol = 0;
			return(NULL);
		}

		/* If there are no more characters in the input then return self::SYMBOLEOF
		  and NULL. */
		if ($this->InputHere >= $this->InputSize) {
			$this->Symbol = 0;
			return(NULL);
		}

		/* Compare characters from the input with the DFA charactersets until
		  not found. */
		$DfaIndex = $this->Grammar->InitialDfaState;
		$AcceptLength = 0;
		$AcceptIndex = -1;
		while ($this->InputHere + $this->Length < $this->InputSize) {
			/* If this is a valid symbol-terminal then save it. We know the
			  input matches the symbol, but there may be a longer symbol that
			  matches so we have to keep scanning. */
			if ($this->Grammar->DfaArray[$DfaIndex]->AcceptSymbol >= 0) {
				$AcceptIndex = $DfaIndex;
				$AcceptLength = $this->Length;
			}

			/* Walk through the edges and scan the characterset of each edge for
			  the current character. */
			for ($i = 0; $i < $this->Grammar->DfaArray[$DfaIndex]->EdgeCount; $i++) {
				if ($this->FindChar($this->InputBuf[$this->InputHere + $this->Length], $this->Grammar->DfaArray[$DfaIndex]->Edges[$i]->CharacterSet, $this->Grammar->DfaArray[$DfaIndex]->Edges[$i]->CharCount) == 1)
					break;
			}

			/* If not found then exit the loop. */
			if ($i >= $this->Grammar->DfaArray[$DfaIndex]->EdgeCount)
				break;

			/* Jump to the TargetState, which points to another set of DFA edges
			  describing the next character. */
			$DfaIndex = $this->Grammar->DfaArray[$DfaIndex]->Edges[$i]->TargetState;

			/* Increment the Length, we have handled the character. */
			$this->Length = $this->Length + 1;
		}

		/* If the DFA is a terminal then return the Symbol, and Length characters
		  from the input. */
		if ($this->Grammar->DfaArray[$DfaIndex]->AcceptSymbol >= 0) {
			$this->Symbol = $this->Grammar->DfaArray[$DfaIndex]->AcceptSymbol;
			return($this->ReadString());
		}

		/* If we found a shorter terminal before, then return that Symbol, and
		  it's characters. */
		if ($AcceptIndex >= 0) {
			$this->Symbol = $this->Grammar->DfaArray[$AcceptIndex]->AcceptSymbol;
			$this->Length = $AcceptLength;
			return($this->ReadString());
		}

		/* Return self::SYMBOLERROR and a string with 1 character from the input. */
		$this->Symbol = 1;
		$this->Length = 1;
		return($this->ReadString());
	}

	function ParseToken() {

		/* struct TokenStackStruct* */ 
		$PopToken = NULL;
		/* struct TokenStackStruct* */ 
		$Reduction = NULL;
		
		$Action = 0;
		$Rule = 0;
		$i = 0;

		/* Find the Token->Symbol in the LALR table. */
		$Action = 0;
		while ($Action < $this->Grammar->LalrArray[$this->LalrState]->ActionCount) {
			if ($this->Grammar->LalrArray[$this->LalrState]->Actions[$Action]->Entry == $this->InputToken->Token->Symbol) {
				break;
			}
			$Action++;
		}

		/* If not found then exit with SYNTAXERROR. The Token is not allowed in this
		  context. */
		if ($Action >= $this->Grammar->LalrArray[$this->LalrState]->ActionCount) {
			if ($this->Debug > 0) {
				printf("LALR Syntax error: symbol %d not found in LALR table %d.\n", $this->InputToken->Token->Symbol, $this->LalrState);
			}
			return(self::LALRSYNTAXERROR);
		}

		/* self::ACTIONACCEPT: exit. We're finished parsing the input. */
		if ($this->Grammar->LalrArray[$this->LalrState]->Actions[$Action]->Action == self::ACTIONACCEPT) {
			if ($this->Debug > 0) {
				printf("LALR Accept: Target=%d\n", $this->Grammar->LalrArray[$this->LalrState]->Actions[$Action]->Target);
			}
			return(self::LALRACCEPT);
		}

		/* self::ACTIONSHIFT: switch the LALR state and return. We're ready to accept
		  the next token. */
		if ($this->Grammar->LalrArray[$this->LalrState]->Actions[$Action]->Action == self::ACTIONSHIFT) {
			$this->LalrState = $this->Grammar->LalrArray[$this->LalrState]->Actions[$Action]->Target;
			if ($this->Debug > 0) {
				printf("LALR Shift: Lalr=%d\n", $this->LalrState);
			}
			return(self::LALRSHIFT);
		}

		/* self::ACTIONGOTO: switch the LALR state and return. We're ready to accept
		  the next token.
		  Note: In my implementation SHIFT and GOTO do the exact same thing. As far
		  as I can tell GOTO only happens just after a reduction. Perhaps GOLD makes
		  the difference to allow the program to perform special actions, which my
		  implementation does not need. */
		if ($this->Grammar->LalrArray[$this->LalrState]->Actions[$Action]->Action == self::ACTIONGOTO) {
			$this->LalrState = $this->Grammar->LalrArray[$this->LalrState]->Actions[$Action]->Target;
			if ($this->Debug > 0) {
				printf("LALR Goto: Lalr=%d\n", $this->LalrState);
			}
			return(self::LALRGOTO);
		}

		/* self::ACTIONREDUCE:
		  Create a new Reduction according to the Rule that is specified by the action.
		  - Create a new Reduction in the ReductionArray.
		  - Pop tokens from the TokenStack and add them to the Reduction.
		  - Push a new token on the TokenStack for the Reduction.
		  - Iterate.
		 */
		$Rule = $this->Grammar->LalrArray[$this->LalrState]->Actions[$Action]->Target;
		if ($this->Debug > 0) {
			printf("LALR Reduce: Lalr=%d TargetRule=%S[%d] ==> %S\n", $this->LalrState, $this->Grammar->SymbolArray[$this->Grammar->RuleArray[$Rule]->Head]->Name, $this->Grammar->RuleArray[$Rule]->Head, $this->Grammar->RuleArray[$Rule]->Description);
		}

		/* If TrimReductions is active, and the Rule contains a single non-terminal,
		  then eleminate the unneeded reduction by modifying the Rule on the stack
		  into this Rule.
		 */
		if (($this->TrimReductions != 0) &&
				($this->Grammar->RuleArray[$Rule]->SymbolsCount == 1) &&
				($this->Grammar->SymbolArray[$this->Grammar->RuleArray[$Rule]->Symbols[0]]->Kind == self::SYMBOLNONTERMINAL)) {
			if ($this->Debug > 0) {
				printf("LALR TrimReduction.\n");
			}

			/* Pop the Rule from the TokenStack. */
			$PopToken = $this->TokenStack;
			$this->TokenStack = $PopToken->NextToken;

			/* Rewind the LALR state. */
			$this->LalrState = $PopToken->LalrState;

			/* Change the Token into the Rule. */
			$PopToken->Token->Symbol = $this->Grammar->RuleArray[$Rule]->Head;

			$oldInputToken = $this->InputToken;
			$this->InputToken = $PopToken;
			/* Feed the Token to the LALR state machine. */
			$this->ParseToken();
			$this->InputToken = $oldInputToken;

			/* Push the modified Token back onto the TokenStack. */
			$PopToken->NextToken = $this->TokenStack;
			$this->TokenStack = $PopToken;

			/* Save the new LALR state in the input token. */
			$this->InputToken->LalrState = $this->LalrState;

			/* Feed the input Token to the LALR state machine and exit. */
			return($this->ParseToken());
		}

		/* Allocate and initialize memory for the Reduction. */
		//Reduction = (struct TokenStackStruct *)malloc(sizeof(struct TokenStackStruct));
		
		$Reduction = new TokenStackStruct();
		/*
		if ($Reduction == NULL)
			return(self::LALRMEMORYERROR);
		*/
		$Reduction->Token = new TokenStruct();
		/*
		if ($Reduction->Token == NULL) {
			unset($Reduction);
			return(self::LALRMEMORYERROR);
		}
		*/

		$Reduction->Token->ReductionRule = $Rule;
		//Reduction->Token->Tokens = (struct TokenStruct **)malloc(sizeof(struct TokenStruct *) * $this->Grammar->RuleArray[Rule]->SymbolsCount);
		$Reduction->Token->Tokens = array();
		/*
		if ($Reduction->Token->Tokens == NULL) {
			unset($Reduction->Token);
			unset($Reduction);
			return(self::LALRMEMORYERROR);
		}
		*/

		$Reduction->Token->Symbol = $this->Grammar->RuleArray[$Rule]->Head;
		$Reduction->Token->Data = NULL;
		$Reduction->Token->Line = $this->InputToken->Token->Line;
		$Reduction->Token->Column = $this->InputToken->Token->Column;
		$Reduction->LalrState = $this->LalrState;
		$Reduction->NextToken = NULL;

		/* Reduce tokens from the TokenStack by moving them to the Reduction.
		  The Lalr state will be rewound to the state it was for the first
		  symbol of the rule. */
		for ($i = $this->Grammar->RuleArray[$Rule]->SymbolsCount; $i > 0; $i--) {
			$PopToken = $this->TokenStack;
			$this->TokenStack = $PopToken->NextToken;
			$PopToken->NextToken = NULL;
			if ($this->Debug > 0) {
				if ($PopToken->Token->Data != NULL) {
					printf("  + Symbol=%S[%d] RuleSymbol=%S[%d] Value='%S' Lalr=%d\n", $this->Grammar->SymbolArray[$PopToken->Token->Symbol]->Name, $PopToken->Token->Symbol, $this->Grammar->SymbolArray[$this->Grammar->RuleArray[$Rule]->Symbols[$i - 1]]->Name, $this->Grammar->RuleArray[$Rule]->Symbols[$i - 1], $PopToken->Token->Data, $PopToken->LalrState
					);
				} else {
					printf("  + Symbol=%S[%d] RuleSymbol=%S[%d] Lalr=%d\n", $this->Grammar->SymbolArray[$PopToken->Token->Symbol]->Name, $PopToken->Token->Symbol, $this->Grammar->SymbolArray[$this->Grammar->RuleArray[$Rule]->Symbols[$i - 1]]->Name, $this->Grammar->RuleArray[$Rule]->Symbols[$i - 1], $PopToken->LalrState
					);
				}
			}
			$Reduction->Token->Tokens[$i - 1] = $PopToken->Token;
			$this->LalrState = $PopToken->LalrState;
			$Reduction->LalrState = $PopToken->LalrState;
			$Reduction->Token->Line = $PopToken->Token->Line;
			$Reduction->Token->Column = $PopToken->Token->Column;
			unset($PopToken);
		}

		/* Call the LALR state machine with the Symbol of the Rule. */
		if ($this->Debug > 0) {
			printf("Calling Lalr 1: Lalr=%d Symbol=%S[%d]\n", $this->LalrState, $this->Grammar->SymbolArray[$this->Grammar->RuleArray[$Rule]->Head]->Name, $this->Grammar->RuleArray[$Rule]->Head
			);
		}
		
		$oldInputToken = $this->InputToken;
		$this->InputToken = $Reduction;
		$this->ParseToken();
		$this->InputToken = $oldInputToken;

		/* Push new Token on the TokenStack for the Reduction. */
		$Reduction->NextToken = $this->TokenStack;
		$this->TokenStack = $Reduction;

		/* Save the current LALR state in the InputToken. We need this to be
		  able to rewind the state when reducing. */
		$this->InputToken->LalrState = $this->LalrState;

		/* Call the LALR state machine with the InputToken. The state has
		  changed because of the reduction, so we must accept the token
		  again. */
		if ($this->Debug > 0) {
			printf("Calling Lalr 2: Lalr=%d Symbol=%S[%d]\n", $this->LalrState, $this->Grammar->SymbolArray[$this->InputToken->Token->Symbol]->Name, $this->InputToken->Token->Symbol);
		}
		return($this->ParseToken());
	}

	function DeleteTokens($Token) {

		$i = 0;

		if ($Token == NULL)
			return;
		if ($Token->Data != NULL)
			unset($Token->Data);
		if ($Token->ReductionRule >= 0) {
			for ($i = 0; $i < $this->Grammar->RuleArray[$Token->ReductionRule]->SymbolsCount; $i++) {
				$this->DeleteTokens($Token->Tokens[$i]);
			}
			unset($Token->Tokens);
		}
	}

	function ParseCleanup(&$Top, &$New) {

		/* struct TokenStackStruct* */ 
		$OldTop = NULL;

		$this->FirstToken = NULL;
		if ($Top != NULL) {
			$this->FirstToken = $Top->Token;
			$OldTop = $Top;
			$Top = $Top->NextToken;
			unset($OldTop);
		}

		if ($New != NULL) {
			$this->DeleteTokens($this->FirstToken);
			$this->FirstToken = $New->Token;
			unset($New);
		}

		while ($Top != NULL) {
			$this->DeleteTokens($Top->Token);
			$OldTop = $Top;
			$Top = $Top->NextToken;
			unset($OldTop);
		}
	}

	/* Parse the input data.
	  Returns a pointer to a ParserData struct, NULL if insufficient memory.
	  The Data->Result value will be one of these values:
	  self::PARSEACCEPT			  Input parsed, no errors.
	  self::PARSELEXICALERROR		  Input could not be tokenized.
	  self::PARSETOKENERROR		  Input is an invalid token.
	  self::PARSESYNTAXERROR		  Input does not match any rule.
	  self::PARSECOMMENTERROR		  A comment was started but not finished.
	  self::PARSEMEMORYERROR		  Insufficient memory.
	 */

	function Parse() {

		/* Index into $this->Grammar->LalrArray[]-> */
		$this->LalrState = 0;          
		
		/* struct TokenStackStruct* */ 
		$this->TokenStack = NULL;   /* Stack of Tokens. */
		
		/* struct TokenStackStruct* */ 
		$Work = NULL;    /* Current token. */
		
		$this->InputHere = 0;          /* Index into input. */
		$this->Line = 1;           /* Line number. */
		$this->Column = 1;          /* Column number. */
		$CommentLevel = 0;         /* Used when skipping comments, nested comment count. */
		$Result = 0;          /* Result from ParseToken(). */

		/* Initialize variables. */
		$this->LalrState = $this->Grammar->InitialLalrState;
		$this->TokenStack = NULL;
		$this->FirstToken = NULL;

		/* Sanity check. */
		if (($this->InputBuf == NULL) || ($this->InputSize == 0)) {
			return(self::PARSEACCEPT);
		}

		/* Accept tokens until finished. */
		while (1) {

			/* Create a new Token. Exit if out of memory. */
			//Work = (struct TokenStackStruct *)malloc(sizeof(struct TokenStackStruct));
			$Work = new TokenStackStruct();
			/*
			if ($Work == NULL) {
				ParseCleanup($this->TokenStack, NULL, $this->FirstToken);
				return(self::PARSEMEMORYERROR);
			}
			*/

			$Work->LalrState = $this->LalrState;
			$Work->NextToken = NULL;
			$Work->Token = new TokenStruct();

			/*
			if ($Work->Token == NULL) {
				unset($Work);
				ParseCleanup($this->TokenStack, NULL, $this->FirstToken);
				return(self::PARSEMEMORYERROR);
			}*/

			$Work->Token->ReductionRule = -1;
			$Work->Token->Tokens = NULL;
			$Work->Token->Line = $this->Line;
			$this->Symbol = $Work->Token->Symbol;

			/* Call the DFA tokenizer and parse a token from the input. */
			$Work->Token->Data = $this->RetrieveToken();
			$Work->Token->Symbol = $this->Symbol;
			//echo $Work->Token->Data."\n";
			if (($Work->Token->Data == NULL) && ($Work->Token->Symbol != 0)) {
				$this->ParseCleanup($this->TokenStack, $Work);
				return(self::PARSEMEMORYERROR);
			}

			/* If we are inside a comment then ignore everything except the end
			  of the comment, or the start of a nested comment. */
			if ($CommentLevel > 0) {
				/* Begin of nested comment: */
				if ($this->Grammar->SymbolArray[$Work->Token->Symbol]->Kind == self::SYMBOLCOMMENTSTART) {
					/* Push the Token on the TokenStack to keep track of line+column. */
					$Work->NextToken = $this->TokenStack;
					$this->TokenStack = $Work;

					$CommentLevel = $CommentLevel + 1;
					continue;
				}

				/* End of comment: */
				if ($this->Grammar->SymbolArray[$Work->Token->Symbol]->Kind == self::SYMBOLCOMMENTEND) {
					/* Delete the Token. */
					if ($Work->Token->Data != NULL)
						unset($Work->Token->Data);
					unset($Work->Token);
					unset($Work);

					/* Pop the comment-start Token from the TokenStack and delete
					  that as well. */
					$Work = $this->TokenStack;
					$this->TokenStack = $Work->NextToken;
					if ($Work->Token->Data != NULL)
						unset($Work->Token->Data);
					unset($Work->Token);
					unset($Work);

					$CommentLevel = $CommentLevel - 1;
					continue;
				}

				/* End of file: Error exit. A comment was started but not finished. */
				if ($this->Grammar->SymbolArray[$Work->Token->Symbol]->Kind == self::SYMBOLEOF) {
					if ($Work->Token->Data != NULL)
						unset($Work->Token->Data);
					unset($Work->Token);
					unset($Work);
					$Temp = NULL;
					$this->ParseCleanup($this->TokenStack, $Temp);
					return(self::PARSECOMMENTERROR);
				}

				/* Any other Token: delete and loop. */
				if ($Work->Token->Data != NULL)
					unset($Work->Token->Data);
				unset($Work->Token);
				unset($Work);

				continue;
			}

			/* If the token is the start of a comment then increment the
			  CommentLevel and loop. The routine will keep reading tokens
			  until the end of the comment. */
			if ($this->Grammar->SymbolArray[$Work->Token->Symbol]->Kind == self::SYMBOLCOMMENTSTART) {
				if ($this->Debug > 0)
					printf("Parse: skipping comment.\n");

				/* Push the Token on the TokenStack to keep track of line+column. */
				$Work->NextToken = $this->TokenStack;
				$this->TokenStack = $Work;

				$CommentLevel = $CommentLevel + 1;
				continue;
			}

			/* If the token is the start of a linecomment then skip the rest
			  of the line. */
			if ($this->Grammar->SymbolArray[$Work->Token->Symbol]->Kind == self::SYMBOLCOMMENTLINE) {
				if ($Work->Token->Data != NULL)
					unset($Work->Token->Data);
				unset($Work->Token);
				unset($Work);
				while (($this->InputHere < $this->InputSize) &&
				($this->InputBuf[$this->InputHere] != "\r") &&
				($this->InputBuf[$this->InputHere] != "\n")) {
					$this->InputHere = $this->InputHere + 1;
				}
				if (($this->InputHere < $this->InputSize) &&
						($this->InputBuf[$this->InputHere] == "\r")) {
					$this->InputHere = $this->InputHere + 1;
				}
				if (($this->InputHere < $this->InputSize) &&
						($this->InputBuf[$this->InputHere] == "\n")) {
					$this->InputHere = $this->InputHere + 1;
				}
				$this->Line = $this->Line + 1;
				$this->Column = 1;
				continue;
			}

			/* If parse error then exit. */
			if ($this->Grammar->SymbolArray[$Work->Token->Symbol]->Kind == self::SYMBOLERROR) {
				$this->ParseCleanup($this->TokenStack, $Work);
				return(self::PARSELEXICALERROR);
			}

			/* Ignore whitespace. */
			if ($this->Grammar->SymbolArray[$Work->Token->Symbol]->Kind == self::SYMBOLWHITESPACE) {
				if ($Work->Token->Data != NULL)
					unset($Work->Token->Data);
				unset($Work->Token);
				unset($Work);
				continue;
			}

			/* The tokenizer should never return a non-terminal symbol. */
			if ($this->Grammar->SymbolArray[$Work->Token->Symbol]->Kind == self::SYMBOLNONTERMINAL) {
				if ($this->Debug > 0) {
					printf("Error: tokenizer returned self::SYMBOLNONTERMINAL '%S'.\n", $Work->Token->Data);
				}
				$this->ParseCleanup($this->TokenStack, $Work);
				return(self::PARSETOKENERROR);
			}

			if ($this->Debug > 0) {
				printf("Token Read: Lalr=%d Symbol=%S[%d] Value='%S'\n", $this->LalrState, $this->Grammar->SymbolArray[$Work->Token->Symbol]->Name, $Work->Token->Symbol, $Work->Token->Data);
			}

			/* Feed the Symbol to the LALR state machine. It can do several
			  things, such as wind back and iteratively call itself. */
			$this->InputToken = $Work;
			$Result = $this->ParseToken();

			/* If out of memory then exit. */
			if ($Result == self::LALRMEMORYERROR) {
				$this->ParseCleanup($this->TokenStack, $Work);
				return(self::PARSEMEMORYERROR);
			}

			/* If syntax error then exit. */
			if ($Result == self::LALRSYNTAXERROR) {
				/* Return LALR state in the Token->Symbol. */
				$Work->Token->Symbol = $this->LalrState;
				$this->ParseCleanup($this->TokenStack, $Work);
				return(self::PARSESYNTAXERROR);
			}

			/* Exit if the LALR state machine says it has reached it's exit. */
			if ($Result == self::LALRACCEPT) {
				if ($this->Grammar->SymbolArray[$Work->Token->Symbol]->Kind == self::SYMBOLEOF) {
					if ($Work->Token->Data != NULL)
						unset($Work->Token->Data);
					unset($Work->Token);
					unset($Work);
				}
				
				$Temp = NULL;
				$this->ParseCleanup($this->TokenStack, $Temp);
				
				return(self::PARSEACCEPT);
			}

			/* Push the token onto the TokenStack. */
			$Work->NextToken = $this->TokenStack;
			$this->TokenStack = $Work;
		}

		/* Should never get here. */
	}

	/* Make a readable copy of a string. All characters outside 32...127 are
	   displayed as a HEX number in square brackets, for example "[0A]". */
	//void ReadableString(wchar_t *Input, wchar_t *Output, long Width) {
	function ReadableString(&$Input, &$Output) {
	  
		$s1 = '';
		$i1 = 0;
		$i2 = 0;
		
		$Width = self::BUFSIZ;

		/* Sanity check. */
		if (($Output == NULL) || ($Width < 1)) return;
		$Output[0] = 0;
		if ($Input == NULL) return;

		while (($i2 < $Width - 1) && ($Input[$i1] != 0)) {
			if (($Input[$i1] >= 32) && ($Input[$i1] <= 127)) {
				$Output[$i2++] = $Input[$i1];
			} else {
				if ($Width - $i2 > 4) {
					sprintf($s1,"%02X",$Input[$i1]);
					$Output[$i2++] = '[';
					$Output[$i2++] = $s1[0];
					$Output[$i2++] = $s1[1];
					$Output[$i2++] = ']';
				}
			}
			$i1++;
		}
	  $Output[$i2] = 0;
	}

	function ShowIndent($Indent) {
		for($i = 0; $i < $Indent; $i++) printf("  ");
	}

	function ShowErrorMessage($Result) {

		$Token = $this->FirstToken;
		$Symbol = 0;
		$i = 0;
		$s1 = '';

		switch ($Result) {
			case self::PARSELEXICALERROR:
				printf("Lexical error");
				break;
			case self::PARSECOMMENTERROR:
				printf("Comment error");
				break;
			case self::PARSETOKENERROR:
				printf("Tokenizer error");
				break;
			case self::PARSESYNTAXERROR:
				printf("Syntax error");
				break;
			case self::PARSEMEMORYERROR:
				printf("Out of memory");
				break;
		}

		if ($Token != NULL) printf(" at line %d column %d", $Token->Line, $Token->Column);
		printf(".\n");

		if ($Result == self::PARSELEXICALERROR) {
			if ($Token->Data != NULL) {
				$this->ReadableString($Token->Data, $s1);
				printf("The grammar does not specify what to do with '%s'.\n", $s1);
			} else {
				printf("The grammar does not specify what to do.\n");
			}
		}
		if ($Result == self::PARSETOKENERROR) {
			printf("The tokenizer returned a non-terminal.\n");
		}
		if ($Result == self::PARSECOMMENTERROR) {
			printf("The comment has no end, it was started but not finished.\n");
		}
		if ($Result == self::PARSESYNTAXERROR) {
			if ($Token->Data != NULL) {
				$this->ReadableString($Token->Data, $s1);
				printf("Encountered '%s', but expected ", $s1);
			} else {
				printf("Expected ");
			}
			for ($i = 0; $i < $this->Grammar->LalrArray[$Token->Symbol]->ActionCount; $i++) {
				$Symbol = $this->Grammar->LalrArray[$Token->Symbol]->Actions[$i]->Entry;
				if ($this->Grammar->SymbolArray[$Symbol]->Kind == self::SYMBOLTERMINAL) {
					if ($i > 0) {
						printf(", ");
						if ($i >= $this->Grammar->LalrArray[$Token->Symbol]->ActionCount - 2) printf("or ");
					}
					printf("'%s'", $this->Grammar->SymbolArray[$Symbol]->Name);
				}
			}
			printf(".\n");
		}
	}

}

?>