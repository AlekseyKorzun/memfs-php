<?php
namespace MemFS;

use \Exception;
use \Memcached\Wrapper as Cache;

/**
 * MemFS class that caches and looks up requested files within Memcached pool
 *
 * If you are not using composer, you must download and set-up:
 * https://github.com/AlekseyKorzun/Memcached-Wrapper-PHP-5
 *
 * @package MemFS
 * @see http://pecl.php.net/package/memcached
 * @author Aleksey Korzun <al.ko@webfoundation.net>
 * @version 0.1
 * @license MIT
 * @link http://www.webfoundation.net
 * @link http://www.alekseykorzun.com
 */
class File
{
    /**
     * Instance of Memcached wrapper
     *
     * @see https://github.com/AlekseyKorzun/Memcached-Wrapper-PHP-5
     * @var Cache
     */
    protected $cache;

    /**
     * Activates MemFS
     *
     * @param string $pool name of a pool MemFs should use via Memcached
     * @param mixed[] $servers a list of Memcached servers we will be using, each entry
     * in servers is supposed to be an array containing hostname, port, and
     * optionally, weight of the server
     *
     * Example:
     *
     *  $servers = array(
     *        array('mem1.domain.com', 11211, 33),
     *        array('mem2.domain.com', 11211, 67)
     *  );
     *
     * See: http://www.php.net/manual/en/memcached.addservers.php
     */
    public function __construct($pool = null, array $servers = null)
    {
        $this->cache = new Cache($pool, $servers);
    }

    /**
     * Load a file, functions like include() or require()
     *
     * @throws Exception
     * @param string $filename path or uri
     * @param bool $isRequired if file is required but could not be accessed exception will be thrown
     */
    public function load($filename, $isRequired = false)
    {
        // Initialize variable
        $resource = false;

        $key = md5($filename);
        if (!$this->cache->get($key, $resource)) {
            // If we detect an URI, encode filename
            if (strpos($filename, 'http') === 0) {
                $filename = urlencode($filename);
            }

            $resource = file_get_contents($filename);
            if (!$resource && $isRequired) {
                throw new Exception('We are unable to load required resource: ' . $filename);
            }

            // Make sure opening tag is there
            if (strpos($resource, '<?') === false) {
                throw new Exception('Requested resource must have opening tags: ' . $filename);
            }
            // Append closing tag
            $position = strrpos($resource, '?>');
            if ($position === false || trim(substr($resource, $position + 2, strlen($resource))) != '') {
                $resource .= "\n?>\n";
            }

            $this->cache->set(
                $key,
                $resource
            );
        }

        if ($resource) {
            eval('?>' . $resource . '<?');
        }
    }

    /**
     * Only load file if it was not previously loaded, functions likee
     * include_once() or require_once()
     *
     * @param string $filename path or uri
     * @param bool $isRequired if file is required but could not be accessed exception will be thrown
     */
    public function once($filename, $isRequired = false)
    {
        $key = md5($filename);
        if (!$this->cache->isStored($key)) {
            $this->load($filename);
        }
    }

    /**
     * Flush all cached resources
     */
    public function flush()
    {
        $this->cache->flush();
    }
}

