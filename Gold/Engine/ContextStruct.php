<?php
namespace Gold\Engine;

/* Struct for transporting data between rules. Add whatever you need.
   Note: you could also use global variables to store stuff, but using
   a struct like this makes the interpreter thread-safe. */

class ContextStruct {
  public /*wchar_t* */ $ReturnValue;             /* In this template all rules return a string. */
  public /*int*/ $Indent;                       /* For printing debug messages. */
  public /*int*/ $Debug;                        /* 0=off, 1=on */
}

?>