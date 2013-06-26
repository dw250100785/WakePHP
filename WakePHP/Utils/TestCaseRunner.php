<?php
namespace WakePHP\Utils;

class TestCaseRunner {
	use \PHPDaemon\Traits\ClassWatchdog;
	public function __construct() {
		$this->addNonPSRLibrariesAutoloader();
	}

	protected function addNonPSRLibrariesAutoloader() {
		if (file_exists(__DIR__ . '/../../vendor/autoload.php')) {
			include_once __DIR__ . '/../../vendor/autoload.php';
		}
	}

	/**
	 * Run unit tests from file and get results
	 * @param string $path_to_test_file
	 * @throws FileNotExistsException
	 * @throws WrongTestClassException
	 * @return \PHPUnit_Framework_TestResult
	 */
	public function getTestResult($path_to_test_file) {
		if (!file_exists($path_to_test_file)) {
			throw new FileNotExistsException();
		}

		$test = new $path_to_test_file();
		if (!($test instanceof \PHPUnit_Framework_TestCase)) {
			throw new WrongTestClassException('Class ' . $test . ' is not instance of PHPUnit_Framework_TestCase');
		}
		$result = new \PHPUnit_Framework_TestResult();
		/** @var \PHPUnit_Framework_TestCase $test */
		$test->run($result);
		return $result;
	}

	/**
	 * @param \PHPUnit_Framework_TestResult[] $results
	 */
	public function getResultsOutput(array $results, $output_stream = null) {
		assert(is_array($results));
		assert(!empty($results));
		$Printer = new \PHPUnit_TextUI_ResultPrinter($output_stream);
		foreach ($results as $Result) {
			$Printer->printResult($Result);
		}
	}
}
