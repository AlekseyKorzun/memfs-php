<?php
/**
 * Benchmark loading files via MemFS .vs regular includes
 *
 * @version 0.1
 * @license MIT
 * @author Aleksey Korzun <al.ko@webfoundation.net>
 * @link https://github.com/AlekseyKorzun/MemFS-PHP-5
 * @link http://www.alekseykorzun.com
 */

/**
 * You must run `composer install` in order to generate autoloader for this example
 */
require __DIR__ . '/../vendor/autoload.php';


// Configure Memcache a server pool to use when testing
$servers = array(
    array('127.0.0.1', 11211)
);

$benchmarker = new Benchmarker($servers);
$benchmarker->run();

use MemFS\File;

/**
 * Simple benchmarker class
 */
class Benchmarker
{
    /**
     * Number of files to benchmark on
     *
     * @var int
     */
    const FILES = 500;

    /**
     * Stores an instance of MemFS
     *
     * @var File
     */
    protected $memfs;

    /**
     * Directory where we store test files
     *
     * @var string
     */
    protected static $directory;

    /**
     * Simple constructor
     *
     * @param string[] $servers
     */
    public function __construct(array $servers)
    {
        $this->memfs = new File('memfs', $servers);
    }

    /**
     * Let's begin our benchmark
     */
    public function run()
    {
        // Warm-up and generate files for testing
        $this->warm();

        // Perform include benchmark
        $this->doInclude();

        // Perform MemFS benchmark
        $this->doMemFS();

        // Perform MemFS multi benchmarks
        $this->doMemFSMulti(10);
        $this->doMemFSMulti(250);
        $this->doMemFSMulti(500);
    }

    /**
     * Warm up
     */
    protected function warm()
    {
        // Remove garbage from previous runs
        $this->clean();

        // Create dummy files
        foreach (self::range() as $number) {
            file_put_contents(
                self::directory() . $number . '.php',
                self::source()
            );
        }
    }

    /**
     * Perform include() based test
     */
    protected function doInclude()
    {
        // Begin include benchmark
        $start = microtime(true);

        foreach (self::range() as $number) {
            include(self::directory() . $number . '.php');
        }

        $end = microtime(true);

        $time = $end - $start;

        echo "Loaded " . self::FILES . " files for using regular include() in " . $time . "\n";
    }

    /**
     * Perform MemFS based test
     */
    protected function doMemFS()
    {
        $start = microtime(true);

        foreach (self::range() as $number) {
            $this->memfs->load(self::directory() . $number . '.php');
        }

        $end = microtime(true);

        $time = $end - $start;

        echo "Loaded " . self::FILES . " files for using MemFS in " . $time . "\n";
    }

    /**
     * Perform MemFS based test with 5 file inclusion at once
     *
     * @param int $pack number of files to request at once
     */
    protected function doMemFSMulti($pack = 10)
    {
        $start = microtime(true);

        $count = 1;

        while ($count < self::FILES) {
            $files = array();

            while (count($files) < (int) $pack) {
                $files[] = self::directory() . $count . '.php';
                ++$count;
            }

            $this->memfs->load($files);
        }

        $end = microtime(true);

        $time = $end - $start;

        echo "Loaded " . self::FILES . " files for using MemFS (" . $pack . " files at the time) " . $time . "\n";
    }

    /**
     * Get testing range (number of files)
     *
     * @return int[]
     */
    protected static function range()
    {
        return range(1, self::FILES);
    }

    /**
     * Get dummy source for PHP file
     *
     * @return string
     */
    protected static function source()
    {
        return "<?php ?>";
    }

    /**
     * Output temporary directory used for benchmarking
     *
     * @return string
     */
    protected static function directory()
    {
        if (is_null(self::$directory)) {
            self::$directory = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'benchmark' . DIRECTORY_SEPARATOR;

            if (!is_dir(self::$directory)) {
                mkdir(self::$directory);
            }
        }

        return self::$directory;
    }

    /**
     * Clean up temporary files
     */
    protected function clean()
    {
        $files = glob(self::directory() . "*.php");
        if ($files) {
            foreach ($files as $file) {
                if (is_file($file)) {
                    unlink($file);
                }
            }
        }
    }

    /**
     * Destructor
     */
    public function __destruct()
    {
        $this->clean();

        echo "###################\n" .
             "Please make sure you run this benchmark twice to insure files are cached in Memcache\n" .
             "###################\n";
    }
}

exit(0);
