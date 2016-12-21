<?php
use \StephenHarris\WordPressBehatExtension\Context\WordPressContext;

use Behat\Behat\Event\SuiteEvent;

use Behat\Testwork\Hook\Scope\BeforeSuiteScope,
    Behat\Testwork\Hook\Scope\AfterSuiteScope,
    Behat\Behat\Hook\Scope\BeforeScenarioScope,
    Behat\Behat\Hook\Scope\AfterScenarioScope;

use Behat\Gherkin\Node\PyStringNode;

use Behat\Behat\Context\Context,
    Behat\Behat\Context\SnippetAcceptingContext;

use \WP_CLI\Process;
use \WP_CLI\Utils;


require_once __DIR__ . '/../../vendor/wp-cli/wp-cli/php/utils.php';

/**
 * Features context.
 */
class FeatureContext implements Context, SnippetAcceptingContext {

    public $variables = array();

    /**
     * @BeforeSuite
     */
    public static function prepare( BeforeSuiteScope $event ) {

        $result = Process::create( 'wp cli info' )->run_check();
        echo PHP_EOL;
        echo $result->stdout;
        echo PHP_EOL;

        //self::cache_wp_files();
        $result = Process::create( Utils\esc_cmd( 'wp core version --path=%s', '/tmp/wordpress' ) )->run_check();
        echo 'WordPress ' . $result->stdout;
        echo PHP_EOL;
    }


    public function proc( $command, $assoc_args = array(), $path = '' ) {
        if ( !empty( $assoc_args ) )
            $command .= Utils\assoc_args_to_str( $assoc_args );

        if ( isset( $this->variables['RUN_DIR'] ) ) {
            $cwd = "{$this->variables['RUN_DIR']}/{$path}";
        } else {
            $cwd = null;
        }

        return Process::create( $command, $cwd, $env );
    }

    function invoke_proc( $proc, $mode ) {
        $map = array(
            'run' => 'run_check',
            'try' => 'run'
        );
        $method = $map[ $mode ];
        return $proc->$method();
    }

    /**
     * @Given an empty directory :dirPath
     */
    public function anEmptyDirectory( $dirPath )
    {
        if (! is_dir($dirPath)) {
            mkdir($dirPath);
            return;
        }
        self::deleteDirectory($dirPath);
        mkdir($dirPath);
    }

    private static function deleteDirectory( $dirPath )
    {

        if (!is_dir($dirPath)) {
            throw \Exception( "$dirPath does not exist" );
        }

        $dirPath = rtrim( $dirPath, '/' );
        $files = scandir($dirPath);
        foreach ($files as $file) {

            if ($file == "." || $file == "..") {
                continue;
            }

            if (is_dir($dirPath."/".$file)) {
                self::deleteDirectory($dirPath."/".$file);
            } else {
                unlink($dirPath."/".$file);
            }
        }
        rmdir($dirPath);
    }

    /**
     * @When /I (run|try) `([^`]+)`/
     */
    public function iRun($mode,$cmd)
    {
       // $cmd = $world->replace_variables( $cmd );
        $this->result = $this->invoke_proc( $this->proc( $cmd ), $mode );
    }

    /**
     * @Then /^the return code should be (\d+)$/
     */
    public function assertReturnCode($return_code)
    {
        if ( $return_code != $this->result->return_code ) {
            throw new RuntimeException( $this->result );
        }
    }

    /**
     * @Then /^(STDOUT|STDERR) should (be|contain|not contain):$/
     */
    public function assertCommandOutput($stream,$action, PyStringNode $expected)
    {
        $stream = strtolower( $stream );
        $this->checkString( $this->result->$stream, (string) $expected, $action, $this->result );
    }

    /**
     * @Then the file :filePath should contain
     */
    public function theFileShouldContain($filePath, PyStringNode $expectedSubcontent)
    {
        if ( ! file_exists( $filePath ) ) {
            throw new \Exception( "$filePath does not exist" );
        }

        $actualContent = file_get_contents( $filePath );

        $this->checkString( $actualContent, (string) $expectedSubcontent, 'contain' );
    }

    function checkString( $output, $expected, $action, $message = false ) {
        switch ( $action ) {

            case 'be':
                $r = $expected === rtrim( $output, "\n" );
                break;

            case 'contain':
                $r = false !== strpos( $output, $expected );
                break;

            case 'not contain':
                $r = false === strpos( $output, $expected );
                break;

            default:
                throw new Behat\Behat\Exception\PendingException();
        }

        if ( !$r ) {
            if ( false === $message )
                $message = $output;
            throw new Exception( $message );
        }
    }

}
