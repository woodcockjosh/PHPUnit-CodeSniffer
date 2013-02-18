<?php

class PHPUnitStandard_Sniffs_BestPractice_ValidAssertionSniff
extends PHP_CodeSniffer_Standards_AbstractScopeSniff {

	private $validAssertions = array(
								'assertArrayHasKey',
								'assertClassHasAttribute',
								'assertClassHasStaticAttribute',
								'assertContains',
								'assertContainsOnly',
								'assertEquals',
								'assertFalse',
								'assertFileEquals',
								'assertFileExists',
								'assertGreaterThan',
								'assertGreaterThanOrEqual',
								'assertLessThan',
								'assertLessThanOrEqual',
								'assertNull',
								'assertObjectHasAttribute',
								'assertRegExp',
								'assertSame',
								'assertTrue',
								'assertType',
								'assertXmlFileEqualsXmlFile',
								'assertXmlStringEqualsXmlString',
								'assertArrayNotHasKey',
								'assertClassNotHasAttribute',
								'assertClassNotHasStaticAttribute',
								'assertNotContains',
								'assertNotContainsOnly',
								'assertNotEquals',
								'assertFileNotEquals',
								'assertFileNotExists',
								'assertNotNull',
								'assertNotRegExp',
								'assertNotSame',
								'assertNotType',
								'assertObjectNotHasAttribute',
								'assertXmlFileNotEqualsXmlFile',
								'assertXmlStringNotEqualsXmlString',
								'assertAttributeContains',
								'assertAttributeNotContains',
								'assertAttributeEquals',
								'assertAttributeNotEquals',
								'assertAttributeSame',
								'assertAttributeNotSame',
								'assertInternalType',
								'assertInstanceOf'
									);

	private $maxAssertionsPerTest = 1;
	private $minAssertionsPerTest = 1;

    public function __construct() {
        parent::__construct(array(T_CLASS, T_INTERFACE), array(T_FUNCTION), true);
    }

    protected function processTokenWithinScope(PHP_CodeSniffer_File $phpcsFile, $stackPtr, $currScope) {
        $methodName = $phpcsFile->getDeclarationName($stackPtr);

        //see if the member function is a test
        if (preg_match(';^(test)(.*);', $methodName, $matches) === 0)
        {
        	//since its not: return
			return;
        }

        //get the stackPtr location of the next function so we do not start looking into the variables
        // in the next function
        $nextFunctionStackPtr = $phpcsFile->findNext(T_FUNCTION, $stackPtr + 1);

        //set the initial variable index to the function index
        $varStackPtr = $stackPtr;

        //get the file tokens
        $tokens = $phpcsFile->getTokens();

        //initialize counter for assertions
        $assertionCount = 0;

        //cycle through the function tokens which are variables
        while($varStackPtr = $phpcsFile->findNext(T_VARIABLE,++$varStackPtr))
        {
        	//see if the variable belongs to the current function
        	//if there is another function and the variable is in that function then we are done for this function
        	if($nextFunctionStackPtr != false && $varStackPtr > $nextFunctionStackPtr )
        	{
        		//then there is no more variables left in this function
        		break;
        	}

        	//get the variable name
        	$varName = ltrim($tokens[$varStackPtr]['content'], '$');

        	//if the variable name is not 'this' then it's not a member function
        	if($varName != 'this')
        	{
        		//go to the next variable
        		continue;
        	}

        	//get the member function which is being called
        	$memberFunction = $phpcsFile->getTokensAsString($varStackPtr + 2, 1);

        	//see if the method is an assertion
        	if(substr($memberFunction,0,6) != 'assert')
        	{
        		//since its not: go to the next variable
        		continue;
        	}

        	// see if the method is a valid assertion
        	if(!in_array($memberFunction, $this->validAssertions))
        	{
        		//there cannot be a member function or variable which starts with 'assert' but is not a valid assertions
        		$error = 'assertion "%s" is not a valid PHPUnit_Framework_TestCase assertion';

        		$data  = array($memberFunction);

				$phpcsFile->addError($error, $varStackPtr, 'InvalidAssertion', $data);
        	}

        	//since this is a valid assertion, increment the counter
        	$assertionCount++;

			//see if there is too many assertions per test
			if($assertionCount > $this->maxAssertionsPerTest)
			{
				//there cannot be a member function or variable which starts with 'assert' but is not a valid assertions
				$error = 'Too many assertions. %s of %s allowed per test';

				$data  = array($assertionCount,$this->maxAssertionsPerTest);

				$phpcsFile->addError($error, $varStackPtr, 'TooManyAssertions', $data);
				continue;
			}
        }

        //see if there is at least 1 assertion in the test
        if($assertionCount < $this->minAssertionsPerTest)
        {
        	//there must be at least 1 assertion per test
        	$s = $this->minAssertionsPerTest == 1 ? 's' : '';
        	$error = 'At least %s assertion%s Required in %s. 0 Found';

        	$data  = array($this->minAssertionsPerTest,$s,$methodName);

        	$phpcsFile->addError($error, $stackPtr, 'NotEnoughAssertions', $data);
        }
    }
}