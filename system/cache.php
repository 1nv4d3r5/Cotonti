<?php
/**
 * Cache subsystem library
 * @package Cotonti
 * @version 0.7.0
 * @author Trustmaster
 * @copyright Copyright (c) Cotonti Team 2009-2010
 * @license BSD
 */

/**
 * Stores the list of advanced cachers provided by the host
 * @var array
 */
$cot_cache_drivers = array();

/**
 * Default cache realm
 */
define('COT_DEFAULT_REALM', 'cot');
/**
 * Default time to live for temporary cache objects
 */
define('COT_DEFAULT_TTL', 3600);
/**
 * Default cache type, uneffective
 */
define('COT_CACHE_TYPE_ALL', 0);
/**
 * Disk cache type
 */
define('COT_CACHE_TYPE_DISK', 1);
/**
 * Database cache type
 */
define('COT_CACHE_TYPE_DB', 2);
/**
 * Shared memory cache type
 */
define('COT_CACHE_TYPE_MEMORY', 3);

/**
 * Abstract class containing code common for all cache drivers
 * @author trustmaster
 */
abstract class Cache_driver
{
	/**
	 * Clears all cache entries served by current driver
	 * @param string $realm Cache realm name, to clear specific realm only
	 * @return bool
	 */
	abstract public function clear($realm = COT_DEFAULT_REALM);

	/**
	 * Checks if an object is stored in cache
	 * @param string $id Object identifier
	 * @param string $realm Cache realm
	 * @return bool
	 */
	abstract public function exists($id, $realm = COT_DEFAULT_REALM);

	/**
	 * Returns value of cached image
	 * @param string $id Object identifier
	 * @param string $realm Realm name
	 * @return mixed
	 */
	abstract public function get($id, $realm = COT_DEFAULT_REALM);

	/**
	 * Removes object image from cache
	 * @param string $id Object identifier
	 * @param string $realm Realm name
	 * @return bool
	 */
	abstract public function remove($id, $realm = COT_DEFAULT_REALM);
}

/**
 * Static cache is used to store large amounts of rarely modified data
 */
abstract class Static_cache_driver
{
	/**
	 * Stores data as object image in cache
	 * @param string $id Object identifier
	 * @param mixed $data Object value
	 * @param string $realm Realm name
	 * @return bool
	 */
	abstract public function store($id, $data, $realm = COT_DEFAULT_REALM);
}

/**
 * Dynamic cache is used to store data that is not too large
 * and is modified more or less frequently
 */
abstract class Dynamic_cache_driver
{
	/**
	 * Stores data as object image in cache
	 * @param string $id Object identifier
	 * @param mixed $data Object value
	 * @param string $realm Realm name
	 * @param int $ttl Time to live, 0 for unlimited
	 * @return bool
	 */
	abstract public function store($id, $data, $realm = COT_DEFAULT_REALM, $ttl = COT_DEFAULT_TTL);
}

/**
 * Persistent cache driver that writes all entries back on script termination.
 * Persistent cache drivers work slower but guarantee long-term data consistency.
 */
abstract class Writeback_cache_driver extends Dynamic_cache_driver
{
	/**
	 * Values for delayed writeback to persistent cache
	 * @var array
	 */
	protected $writeback_data = array();
	/**
	 * Keys that are to be removed
	 */
	protected $removed_data = array();

	/**
	 * Writes modified entries back to persistent storage
	 */
	abstract public function  __destruct();

	/**
	 * @see Cache_driver::remove()
	 */
	public function remove($id, $realm = COT_DEFAULT_REALM)
	{
		$this->removed_data[] = array('id' => $id, 'realm' => $realm);
	}

	/**
	 * Removes item immediately, avoiding writeback.
	 * @param string $id Item identifirer
	 * @param string $realm Cache realm
	 * @return bool
	 * @see Cache_driver::remove()
	 */
	abstract public function remove_now($id, $realm = COT_DEFAULT_REALM);

	/**
	 * @see Cache_driver::store()
	 */
	public function store($id, $data, $realm = COT_DEFAULT_REALM, $ttl = COT_DEFAULT_TTL)
	{
		$this->writeback_data[] = array('id' => $id, 'data' => $data, 'realm' =>  $realm, 'ttl' => $ttl);
	}

	/**
	 * Writes item to cache immediately, avoiding writeback.
	 * @param string $id Object identifier
	 * @param mixed $data Object value
	 * @param string $realm Realm name
	 * @param int $ttl Time to live, 0 for unlimited
	 * @return bool
	 * @see Cache_driver::store()
	 */
	abstract public function store_now($id, $data, $realm = COT_DEFAULT_REALM, $ttl = COT_DEFAULT_TTL);
}

/**
 * Query cache drivers are driven by database
 */
abstract class Db_cache_driver extends Writeback_cache_driver
{
	/**
	 * Loads all variables from a specified realm(s) into the global scope
	 * @param mixed $realm Realm name or array of realm names
	 * @return int Number of items loaded
	 */
	abstract public function get_all($realm = COT_DEFAULT_REALM);
}

/**
 * Temporary cache driver is fast in-memory cache. It usually works faster and provides
 * automatic garbage collection, but it doesn't save data if PHP stops whatsoever.
 * Use it for individual frequently modified variables.
 */
abstract class Temporary_cache_driver extends Dynamic_cache_driver
{
	/**
	 * Increments counter value
	 * @param string $id Counter identifier
	 * @param string $realm Realm name
	 * @param int $value Increment value
	 * return int Result value
	 */
	public function inc($id, $realm = COT_DEFAULT_REALM, $value = 1)
	{
		$res = $this->get($id, $realm);
		$res += $value;
		$this->store($id, $res, $realm);
		return $res;
	}

	/**
	 * Decrements counter value
	 * @param string $id Counter identifier
	 * @param string $realm Realm name
	 * @param int $value Increment value
	 * return int Result value
	 */
	public function dec($id, $realm = COT_DEFAULT_REALM, $value = 1)
	{
		$res = $this->get($id, $realm);
		$res -= $value;
		$this->store($id, $res, $realm);
		return $res;
	}

	/**
	 * Returns information about memory usage if available.
	 * Possible keys: available, occupied, max.
	 * If the driver cannot provide a value, it sets it to -1.
	 * @return array Associative array containing information
	 */
	abstract public function get_info();

	/**
	 * Gets a size limit from php.ini
	 * @param string $name INI setting name
	 * @return int Number of bytes
	 */
	protected function get_ini_size($name)
	{
		$ini = ini_get($name);
		$suffix = strtoupper(substr($ini, -1));
		$prefix = substr($ini, 0, -1);
		switch ($suffix)
		{
			case 'K':
				return ((int) $prefix) * 1024;
				break;
			case 'M':
				return ((int) $prefix) * 1048576;
				break;
			case 'G':
				return ((int) $prefix) * 1073741824;
				break;
			default:
				return (int) $ini;
		}
	}
}

/**
 * A persistent cache using local file system tree. It does not use multilevel structure
 * or lexicograph search, so it may slow down when your cache grows very big.
 * But normally it is very fast reads.
 * @author trustmaster
 */
class File_cache extends Static_cache_driver
{
	/**
	 * Cache root directory
	 * @var string
	 */
	private $dir;

	/**
	 * Cache storage object constructor
	 * @param string $dir Cache root directory. System default will be used if empty.
	 * @return File_cache
	 */
	public function __construct($dir = '')
	{
		global $cfg;
		if (empty($dir)) $dir = $cfg['cache_dir'];

		if (file_exists($dir) && is_writeable($dir))
		{
			$this->dir = $dir;
		}
		else
		{
			throw new Exception('Cache directory '.$dir.' is not writeable!'); // TODO: Need translate
		}
	}

	/**
	 * @see Cache_driver::clear()
	 */
	public function clear($realm = COT_DEFAULT_REALM)
	{
		if (empty($realm))
		{
			$dp = opendir($this->dir);
			while ($f = readdir($dp))
			{
				$dname = $this->dir.'/'.$f;
				if ($f[0] != '.' && is_dir($dname))
				{
					$this->clear($f);
				}
			}
			closedir($dp);
		}
		else
		{
			$dp = opendir($this->dir.'/'.$realm);
			while ($f = readdir($dp))
			{
				$fname = $this->dir.'/'.$realm.'/'.$f;
				if (is_file($fname))
				{
					unlink($fname);
				}
			}
			closedir($dp);
		}
		return TRUE;
	}

	/**
	 * @see Cache_driver::exists()
	 */
	public function exists($id, $realm = COT_DEFAULT_REALM)
	{
		return file_exists($this->dir.'/'.$realm.'/'.$id);
	}

	/**
	 * @see Cache_driver::get()
	 */
	public function get($id, $realm = COT_DEFAULT_REALM)
	{
		if ($this->exists($id, $realm))
		{
			return unserialize(file_get_contents($this->dir.'/'.$realm.'/'.$id));
		}
		else
		{
			return NULL;
		}
	}

	/**
	 * @see Cache_driver::remove()
	 */
	public function remove($id, $realm = COT_DEFAULT_REALM)
	{
		if ($this->exists($id, $realm))
		{
			unlink($this->dir.'/'.$realm.'/'.$id);
			return true;
		}
		else return false;
	}

	/**
	 * @see Cache_driver::store()
	 */
	public function store($id, $data, $realm = COT_DEFAULT_REALM)
	{
		if (!file_exists($this->dir.'/'.$realm))
		{
			mkdir($this->dir.'/'.$realm);
		}
		file_put_contents($this->dir.'/'.$realm.'/'.$id, serialize($data));
		return true;
	}
}

/**
 * A very popular caching solution using MySQL as a storage. It is quite slow compared to
 * File_cache but may be considered more reliable.
 * @author trustmaster
 */
class MySQL_cache extends Db_cache_driver
{
	/**
	 * Prefetched data to avoid duplicate queries
	 * @var array
	 */
	private $buffer = array();

	/**
	 * Performs pre-load actions
	 */
	public function __construct()
	{
		// TODO might use GC probability
		$this->gc();
	}

	/**
	 * Saves all modified data with one query
	 */
	public function  __destruct()
	{
		global $db_cache, $sys;
		if (count($this->removed_data) > 0)
		{
			$q = "DELETE FROM $db_cache WHERE";
			$i = 0;
			foreach ($this->removed_data as $entry)
			{
				$c_name = sed_sql_prep($entry['id']);
				$c_realm = sed_sql_prep($entry['realm']);
				$or = $i == 0 ? '' : ' OR';
				$q .= $or." (c_name = '$c_name' AND c_realm = '$c_realm')";
				$i++;
			}
			sed_sql_query($q);
		}
		if (count($this->writeback_data) > 0)
		{
			$q = "INSERT INTO $db_cache (c_name, c_realm, c_expire, c_value) VALUES ";
			$i = 0;
			foreach ($this->writeback_data as $entry)
			{
				$c_name = sed_sql_prep($entry['id']);
				$c_realm = sed_sql_prep($entry['realm']);
				$c_expire = $entry['ttl'] > 0 ? $sys['now'] + $entry['ttl'] : 0;
				$c_value = sed_sql_prep(serialize($entry['data']));
				$comma = $i == 0 ? '' : ',';
				$q .= $comma."('$c_name', '$c_realm', $c_expire, '$c_value')";
				$i++;
			}
			$q .= " ON DUPLICATE KEY UPDATE c_value=VALUES(c_value), c_expire=VALUES(c_expire)";
			sed_sql_query($q);
		}
	}

	/**
	 * @see Cache_driver::clear()
	 */
	public function clear($realm = '')
	{
		global $db_cache;
		if (empty($realm))
		{
			sed_sql_query("TRUNCATE $db_cache");
		}
		else
		{
			sed_sql_query("DELETE FROM $db_cache WHERE c_realm = '$realm'");
		}
		$this->buffer = array();
		return TRUE;
	}

	/**
	 * @see Cache_driver::exists()
	 */
	public function exists($id, $realm = COT_DEFAULT_REALM)
	{
		global $db_cache;
		$sql = sed_sql_query("SELECT c_value FROM $db_cache WHERE c_realm = '$realm' AND c_name = '$id'");
		$res = sed_sql_numrows($sql) == 1;
		if ($res)
		{
			$this->buffer[$realm][$id] = unserialize(sed_sql_result($sql, 0, 0));
		}
		return $res;
	}

	/**
	 * Garbage collector function. Removes cache entries which are not valid anymore.
	 * @return int Number of entries removed
	 */
	private function gc()
	{
		global $db_cache, $sys;
		sed_sql_query("DELETE FROM $db_cache WHERE c_expire > 0 AND c_expire < ".$sys['now']);
		return sed_sql_affectedrows();
	}

	/**
	 * @see Cache_driver::get()
	 */
	public function get($id, $realm = COT_DEFAULT_REALM)
	{
		global $db_cache;
		if(!$this->exists($id, $realm))
		{
			return $this->buffer[$realm][$id];
		}
		else
		{
			return null;
		}
	}

	/**
	 * @see Db_cache_driver::get_all()
	 */
	public function get_all($realms = COT_DEFAULT_REALM)
	{
		global $db_cache;
		if (is_array($realms))
		{
			$r_where = "c_realm IN(";
			$i = 0;
			foreach ($realms as $realm)
			{
				$glue = $i == 0 ? "'" : ",'";
				$r_where .= $glue.sed_sql_prep($realm)."'";
				$i++;
			}
			$r_where .= ')';
		}
		else
		{
			$r_where = "c_realm = '".sed_sql_prep($realms)."'";
		}
		$sql = sed_sql_query("SELECT c_name, c_value FROM `$db_cache` WHERE c_auto=1 AND $r_where");
		$i = 0;
		while ($row = sed_sql_fetchassoc($sql))
		{
			global ${$row['c_name']};
			${$row['c_name']} = unserialize($row['c_value']);
			$i++;
		}
		return $i;
	}

	/**
	 * @see Writeback_cache_driver::remove_now()
	 */
	public function remove_now($id, $realm = COT_DEFAULT_REALM)
	{
		global $db_cache;
		sed_sql_query("DELETE FROM $db_cache WHERE c_realm = '$realm' AND c_id = $id");
		unset($this->buffer[$realm][$id]);
		return sed_sql_affectedrows() == 1;
	}

	/**
	 * Writes item to cache immediately, avoiding writeback.
	 * @param string $id Object identifier
	 * @param mixed $data Object value
	 * @param string $realm Realm name
	 * @param int $ttl Time to live, 0 for unlimited
	 * @return bool
	 * @see Cache_driver::store()
	 */
	public function store_now($id, $data, $realm = COT_DEFAULT_REALM, $ttl = COT_DEFAULT_TTL)
	{
		global $db_cache;
		$c_name = sed_sql_prep($id);
		$c_realm = sed_sql_prep($realm);
		$c_expire = $ttl > 0 ? $sys['now'] + $ttl : 0;
		$c_value = sed_sql_prep(serialize($data));
		sed_sql_query("INSERT INTO $db_cache (c_name, c_realm, c_expire, c_value)
			VALUES ('$c_name', '$c_realm', $c_expire, '$c_value')");
		$this->buffer[$realm][$id] = $data;
		return sed_sql_affectedrows() == 1;
	}
}

if (extension_loaded('memcache'))
{
	$cot_cache_drivers[] = 'Memcache_driver';

	/**
	 * Memcache distributed persistent cache driver implementation. Give it a higher priority
	 * if a cluster of webservers is used and Memcached is running via TCP/IP between them.
	 * In other circumstances this only should be used if no APC/eAccelerator/XCache available,
	 * keeping in mind that File_cache might be still faster.
	 * @author trustmaster
	 */
	class Memcache_driver extends Temporary_cache_driver
	{
		/**
		 * PHP Memcache instance
		 * @var Memcache
		 */
		protected $memcache = NULL;
		/**
		 * Compression flag
		 * @var int
		 */
		protected $compressed = true;

		/**
		 * Creates an object and establishes Memcached server connection
		 * @param string $host Memcached host
		 * @param int $port Memcached port
		 * @param bool $persistent Use persistent connection
		 * @param bool $compressed Use compression
		 * @return Memcache_driver
		 */
		public function __construct($host = 'localhost', $port = 11211, $persistent = true, $compressed = true)
		{
			$this->memcache = new Memcache;
			$this->memcache->addServer($host, $port, $persistent);
			$this->compressed = $compressed ? MEMCACHE_COMPRESSED : 0;
		}

		/**
		 * @see Cache_driver::clear()
		 */
		public function clear($realm = '')
		{
			if (empty($realm))
			{
				return $this->memcache->flush();
			}
			else
			{
				// FIXME implement exact realm cleanup (not yet provided by Memcache)
				return $this->memcache->flush();
			}
		}

		/**
		 * @see Temporary_cache_driver::dec()
		 */
		public function dec($id, $realm = COT_DEFAULT_REALM, $value = 1)
		{
			if ($this->compressed == MEMCACHE_COMPRESSED)
			{
				return parent::dec($id, $realm, $value);
			}
			else
			{
				return $this->memcache->decrement($realm.'/'.$id, $value);
			}
		}

		/**
		 * @see Cache_driver::exists()
		 */
		public function exists($id, $realm = COT_DEFAULT_REALM)
		{
			return $this->memcache->get($realm.'/'.$id, $this->compressed) !== FALSE;
		}

		/**
		 * @see Cache_driver::get()
		 */
		public function get($id, $realm = COT_DEFAULT_REALM)
		{
			return $this->memcache->get($realm.'/'.$id, $this->compressed);
		}

		/**
		 * @see Temporary_cache_driver::get_info()
		 */
		public function get_info()
		{
			$info = $this->memcache->getstats();
			return array(
				'available' => $info['limit_maxbytes'] - $info['bytes'],
				'max' => $info['limit_maxbytes'],
				'occupied' => $info['bytes']
			);
		}

		/**
		 * @see Temporary_cache_driver::inc()
		 */
		public function inc($id, $realm = COT_DEFAULT_REALM, $value = 1)
		{
			if ($this->compressed == MEMCACHE_COMPRESSED)
			{
				return parent::inc($id, $realm, $value);
			}
			else
			{
				return $this->memcache->increment($realm.'/'.$id, $value);
			}
		}

		/**
		 * @see Cache_driver::remove()
		 */
		public function remove($id, $realm = COT_DEFAULT_REALM)
		{
			return $this->memcache->delete($realm.'/'.$id);
		}

		/**
		 * @see Dynamic_cache_driver::store()
		 */
		public function store($id, $data, $realm = COT_DEFAULT_REALM, $ttl = COT_DEFAULT_TTL)
		{
			return $this->memcache->set($realm.'/'.$id, $data, $this->compressed, $ttl);
		}
	}
}

if (extension_loaded('apc'))
{
	$cot_cache_drivers[] = 'APC_driver';

	/**
	 * Accelerated PHP Cache driver implementation. This should be used as default cacher
	 * on APC-enabled hosts.
	 * @author trustmaster
	 */
	class APC_driver extends Temporary_cache_driver
	{
		/**
		 * @see Cache_driver::clear()
		 */
		public function clear($realm = '')
		{
			if (empty($realm))
			{
				return apc_clear_cache();
			}
			else
			{
			// TODO implement exact realm cleanup
				return FALSE;
			}
		}

		/**
		 * @see Cache_driver::exists()
		 */
		public function exists($id, $realm = COT_DEFAULT_REALM)
		{
			return apc_fetch($realm.'/'.$id) !== FALSE;
		}

		/**
		 * @see Cache_driver::get()
		 */
		public function get($id, $realm = COT_DEFAULT_REALM)
		{
			return unserialize(apc_fetch($realm.'/'.$id));
		}

		/**
		 * @see Temporary_cache_driver::get_info()
		 */
		public function get_info()
		{
			$info = apc_sma_info();
			$max = ini_get('apc.shm_segments') * ini_get('apc.shm_size') * 1024 * 1024;
			$occupied = $max - $info['avail_mem'];
			return array(
				'available' => $info['avail_mem'],
				'max' => $max,
				'occupied' => $occupied
			);
		}

		/**
		 * @see Cache_driver::remove()
		 */
		public function remove($id, $realm = COT_DEFAULT_REALM)
		{
			return apc_delete($realm.'/'.$id);
		}

		/**
		 * @see Dynamic_cache_driver::store()
		 */
		public function store($id, $data, $realm = COT_DEFAULT_REALM, $ttl = COT_DEFAULT_TTL)
		{
			return apc_store($realm.'/'.$id, serialize($data), $ttl);
		}
	}
}

if (extension_loaded('eaccelerator') && function_exists('eaccelerator_get'))
{
	$cot_cache_drivers[] = 'eAccelerator_driver';

	/**
	 * eAccelerator driver implementation. This should be used as default cacher
	 * on hosts providing eAccelerator.
	 * @author trustmaster
	 */
	class eAccelerator_driver extends Temporary_cache_driver
	{
		/**
		 * @see Cache_driver::clear()
		 */
		public function clear($realm = '')
		{
			if (empty($realm))
			{
				eaccelerator_clear();
				return TRUE;
			}
			else
			{
				// FIXME implement exact realm cleanup (not yet provided by eAccelerator)
				eaccelerator_clear();
				return TRUE;
			}
		}

		/**
		 * @see Cache_driver::exists()
		 */
		public function exists($id, $realm = COT_DEFAULT_REALM)
		{
			return !is_null(eaccelerator_get($realm.'/'.$id));
		}

		/**
		 * @see Cache_driver::get()
		 */
		public function get($id, $realm = COT_DEFAULT_REALM)
		{
			return eaccelerator_get($realm.'/'.$id);
		}

		/**
		 * @see Temporary_cache_driver::get_info()
		 */
		public function get_info()
		{
			$info = eaccelerator_info();
			return array(
				'available' => $info['memorySize'] - $info['memoryAllocated'],
				'max' => $info['memorySize'],
				'occupied' => $info['memoryAllocated']
			);
		}

		/**
		 * @see Cache_driver::remove()
		 */
		public function remove($id, $realm = COT_DEFAULT_REALM)
		{
			return eaccelerator_rm($realm.'/'.$id);
		}

		/**
		 * @see Dynamic_cache_driver::store()
		 */
		public function store($id, $data, $realm = COT_DEFAULT_REALM, $ttl = COT_DEFAULT_TTL)
		{
			return eaccelerator_put($realm.'/'.$id, $data, $ttl);
		}

		private function get_keys()
		{
			return eaccelerator_list_keys();
		}
	}
}

if (extension_loaded('xcache'))
{
	$cot_cache_drivers[] = 'Xcache_driver';

	/**
	 * XCache variable cache driver. It should be used on hosts that use XCache for
	 * PHP acceleration and variable cache.
	 * @author trustmaster
	 */
	class Xcache_driver extends Temporary_cache_driver
	{
		/**
		 * @see Cache_driver::clear()
		 */
		public function clear($realm = '')
		{
			if (empty($realm))
			{
				return xcache_unset_by_prefix('');
			}
			else
			{
				return xcache_unset_by_prefix($realm.'/');
			}
		}

		/**
		 * @see Cache_driver::exists()
		 */
		public function exists($id, $realm = COT_DEFAULT_REALM)
		{
			return xcache_isset($realm.'/'.$id);
		}

		/**
		 * @see Temporary_cache_driver::dec()
		 */
		public function dec($id, $realm = COT_DEFAULT_REALM, $value = 1)
		{
			return xcache_dec($realm.'/'.$id, $value);
		}

		/**
		 * @see Cache_driver::get()
		 */
		public function get($id, $realm = COT_DEFAULT_REALM)
		{
			return xcache_get($realm.'/'.$id);
		}

		/**
		 * @see Temporary_cache_driver::get_info()
		 */
		public function get_info()
		{
			return array(
				'available' => -1,
				'max' => $this->get_ini_size('xcache.var_size'),
				'occupied' => -1
			);
		}

		/**
		 * @see Temporary_cache_driver::inc()
		 */
		public function inc($id, $realm = COT_DEFAULT_REALM, $value = 1)
		{
			return xcache_inc($realm.'/'.$id, $value);
		}

		/**
		 * @see Cache_driver::remove()
		 */
		public function remove($id, $realm = COT_DEFAULT_REALM)
		{
			return xcache_unset($realm.'/'.$id);
		}

		/**
		 * @see Dynamic_cache_driver::store()
		 */
		public function store($id, $data, $realm = COT_DEFAULT_REALM, $ttl = COT_DEFAULT_TTL)
		{
			return xcache_set($realm.'/'.$id, $data, $ttl);
		}
	}
}

/**
 * Multi-layer universal cache controller for Cotonti
 *
 * @property-read bool $mem_available Memory storage availability flag
 */
class Cache
{
	/**
	 * Persistent cache underlayer driver
	 * @var Static_cache_driver
	 */
	private $disk;
	/**
	 * Intermediate query cache driver
	 * @var Db_cache_driver
	 */
	private $db;
	/**
	 * Mutable top-layer shared memory driver
	 * @var Temporary_cache_driver
	 */
	private $mem;
	/**
	 * Event bindings
	 * @var array
	 */
	private $bindings;
	/**
	 * A flag to apply binding changes before termination
	 * @var bool
	 */
	private $resync_on_exit = false;
	/**
	 * A flag of memory driver availability
	 * @var bool
	 */
	private $mem_avail = false;
	/**
	 * Selected memory driver
	 * @var string
	 */
	private $selected_drv = '';

	/**
	 * Initializes controller components
	 */
	public function  __construct()
	{
		global $cfg, $cot_cache_autoload, $cot_cache_drivers, $cot_cache_bindings, $z;
		$this->disk = new File_cache($cfg['cache_dir']);
		$this->db = new MySQL_cache();
		$cot_cache_autoload = is_array($cot_cache_autoload)
			? array_merge(array('system', 'cot', $z), $cot_cache_autoload)
				: array('system', 'cot', $z);
		$this->db->get_all($cot_cache_autoload);
		$cfg['cache_drv'] .= '_driver';
		if (in_array($cfg['cache_drv'], $cot_cache_drivers))
		{
			$selected = $cfg['cache_drv'];
		}
		elseif (count($cot_cache_drivers) > 0)
		{
			$selected = $cot_cache_drivers[0];
		}
		if (!empty($selected))
		{
			$this->mem = new $selected();
			$this->mem_avail = true;
			$this->selected_drv = $selected;
		}
		else
		{
			$this->mem = $this->db;
			$this->mem_avail = false;
		}
		if (!$cot_cache_bindings)
		{
			$this->resync_bindings();
		}
		else
		{
			unset($cot_cache_bindings);
		}
	}

	/**
	 * Performs actions before script termination
	 */
	public function  __destruct()
	{
		if ($this->resync_on_exit)
		{
			$this->resync_bindings();
		}
	}

	/**
	 * Property handler
	 * @param string $name Property name
	 * @return mixed Property value
	 */
	public function __get($name)
	{
		switch ($name)
		{
			case 'mem_available':
				return $this->mem_avail;
			break;

			case 'mem_driver':
				return $this->selected_drv;
			break;

			default:
				return null;
			break;
		}
	}

	/**
	 * Rereads bindings from database
	 */
	private function resync_bindings()
	{
		global $db_cache_bindings;
		$this->bindings = array();
		$sql = sed_sql_query("SELECT * FROM `$db_cache_bindings`");
		while ($row = sed_sql_fetchassoc($sql))
		{
			$this->bindings[$row['c_event']][] = array('id' => $row['c_id'], 'realm' => $row['c_realm']);
		}
		sed_sql_freeresult($sql);
		$this->db->store('cot_cache_bindings', $this->bindings, 'system');
	}

	/**
	 * Binds an event to automatic cache field invalidation
	 * @param string $event Event name
	 * @param string $id Cache entry id
	 * @param string $realm Cache realm name
	 * @param int $type Storage type, one of COT_CACHE_TYPE_* values
	 * @return bool TRUE on success, FALSE on error
	 */
	public function bind($event, $id, $realm = COT_DEFAULT_REALM, $type = COT_CACHE_TYPE_DEFAULT)
	{
		global $db_cache_bindings;
		$c_event = sed_sql_prep($event);
		$c_id = sed_sql_prep($id);
		$c_realm = sed_sql_prep($realm);
		$c_type = (int) $type;
		sed_sql_query("INSERT INTO `$db_cache_bindings` (c_event, c_id, c_realm, c_type)
			VALUES ('$c_event', '$c_id', '$c_realm', $c_type)");
		$res = sed_sql_affectedrows() == 1;
		if ($res)
		{
			$this->resync_on_exit = true;
		}
		return $res;
	}

	/**
	 * Binds multiple cache fields to events, all represented as an associative array
	 * Binding keys:
	 * event - name of the event the field is binded to
	 * id - cache object id
	 * realm - cache realm name
	 * type - cache storage type, one of COT_CACHE_TYPE_* constants
	 * @param array $bindings An indexed array of bindings.
	 * Each binding is an associative array with keys: event, realm, id, type.
	 * @return int Number of bindings added
	 */
	public function bind_array($bindings)
	{
		global $db_cache_bindings;
		$q = "INSERT INTO `$db_cache_bindings` (c_event, c_id, c_realm, c_type) VALUES ";
		$i = 0;
		foreach ($bindings as $entry)
		{
			$c_event = sed_sql_prep($entry['event']);
			$c_id = sed_sql_prep($entry['id']);
			$c_realm = sed_sql_prep($entry['realm']);
			$c_type = (int) $entry['type'];
			$comma = $i == 0 ? '' : ',';
			$q .= $comma."('$c_event', '$c_id', '$c_realm', $c_type)";
		}
		sed_sql_query($q);
		$res = sed_sql_affectedrows();
		if ($res > 0)
		{
			$this->resync_on_exit = true;
		}
		return $res;
	}

	/**
	 * Clears all cache entries
	 * @param int $type Cache storage type:
	 * COT_CACHE_TYPE_ALL, COT_CACHE_TYPE_DB, COT_CACHE_TYPE_DISK, COT_CACHE_TYPE_MEMORY.
	 */
	public function clear($type = COT_CACHE_TYPE_ALL)
	{
		switch ($type)
		{
			case COT_CACHE_TYPE_DB:
				$this->db->clear();
			break;

			case COT_CACHE_TYPE_DISK:
				$this->disk->clear();
			break;

			case COT_CACHE_TYPE_MEMORY:
				$this->mem->clear();
			break;

			default:
				$this->mem->clear();
				$this->db->clear();
				$this->disk->clear();
		}
	}

	/**
	 * Clears cache in specific realm
	 * @param string $realm Realm name
	 * @param int $type Cache storage type:
	 * COT_CACHE_TYPE_ALL, COT_CACHE_TYPE_DB, COT_CACHE_TYPE_DISK, COT_CACHE_TYPE_MEMORY.
	 */
	public function clear_realm($realm = COT_DEFAULT_REALM, $type = COT_CACHE_TYPE_ALL)
	{
		switch ($type)
		{
			case COT_CACHE_TYPE_DB:
				$this->db->clear($realm);
			break;

			case COT_CACHE_TYPE_DISK:
				$this->disk->clear($realm);
			break;

			case COT_CACHE_TYPE_MEMORY:
				$this->mem->clear($realm);
			break;

			default:
				$this->mem->clear($realm);
				$this->db->clear($realm);
				$this->disk->clear($realm);
		}
	}

	/**
	 * Gets the object from database cache. It is recommended to use memory cache
	 * for particular objects rather than DB cache.
	 * @param string $id Object identifier
	 * @param string $realm Realm name
	 * @return mixed Cached item value or NULL if the item was not found in cache
	 */
	public function db_get($id, $realm = COT_DEFAULT_REALM)
	{
		return $this->db->get($id, $realm);
	}

	/**
	 * Checks if an object is stored in database cache
	 * @param string $id Object identifier
	 * @param string $realm Cache realm
	 * @return bool
	 */
	public function db_isset($id, $realm = COT_DEFAULT_REALM)
	{
		return $this->db->exists($id, $realm);
	}

	/**
	 * Loads all variables from a specified realm(s) into the global scope
	 * @param mixed $realm Realm name or array of realm names
	 * @return int Number of items loaded
	 */
	public function db_load($realm)
	{
		return $this->db->get_all($realm);
	}

	/**
	 * Stores data as object image in the database cache
	 * @param string $id Object identifier
	 * @param mixed $data Object value
	 * @param string $realm Realm name
	 * @param int $ttl Time to live, 0 for unlimited
	 * @return bool
	 */
	public function db_set($id, $data, $realm = COT_DEFAULT_REALM, $ttl = 0)
	{
		return $this->db->store($id, $data, $realm, $ttl);
	}

	/**
	 * Removes cache image of the object from the database
	 * @param string $id Object identifier
	 * @param string $realm Realm name
	 */
	public function db_unset($id, $realm = COT_DEFAULT_REALM)
	{
		$this->db->remove($id, $realm);
	}

	/**
	 * Gets an object directly from disk, avoiding the shared memory.
	 * @param string $id Object identifier
	 * @param string $realm Realm name
	 * @return mixed Cached item value or NULL if the item was not found in cache
	 */
	public function disk_get($id, $realm = COT_DEFAULT_REALM)
	{
		return $this->disk->get($id, $realm);
	}

	/**
	 * Checks if an object is stored in disk cache
	 * @param string $id Object identifier
	 * @param string $realm Cache realm
	 * @return bool
	 */
	public function disk_isset($id, $realm = COT_DEFAULT_REALM)
	{
		return $this->disk->exists($id, $realm);
	}

	/**
	 * Stores disk-only cache entry. Use it for large objects, which you don't want to put
	 * into memory cache.
	 * @param string $id Object identifier
	 * @param mixed $data Object value
	 * @param string $realm Realm name
	 * @return bool
	 */
	public function disk_set($id, $data, $realm = COT_DEFAULT_REALM)
	{
		return $this->disk->store($id, $data, $realm);
	}

	/**
	 * Removes cache image of the object from disk
	 * @param string $id Object identifier
	 * @param string $realm Realm name
	 */
	public function disk_unset($id, $realm = COT_DEFAULT_REALM)
	{
		$this->disk->remove($id, $realm);
	}

	/**
	 * Returns information about memory driver usage
	 * @return array Usage information
	 */
	public function get_info()
	{
		return $this->mem->get_info();
	}

	/**
	 * Gets the object from shared memory cache
	 * @param string $id Object identifier
	 * @param string $realm Realm name
	 * @return mixed Cached item value or NULL if the item was not found in cache
	 * @see Cache::set(), Cache::get_disk(), Cache::get_shared()
	 */
	public function mem_get($id, $realm = COT_DEFAULT_REALM)
	{
		return $this->mem->get($id, $realm);
	}

	/**
	 * Increments counter value
	 * @param string $id Counter identifier
	 * @param string $realm Realm name
	 * @param int $value Increment value
	 * return int Result value
	 */
	public function mem_inc($id, $realm = COT_DEFAULT_REALM, $value = 1)
	{
		return $this->mem->inc($id, $realm, $value);
	}

	/**
	 * Checks if an object is stored in shared memory cache
	 * @param string $id Object identifier
	 * @param string $realm Cache realm
	 * @return bool
	 */
	public function mem_isset($id, $realm = COT_DEFAULT_REALM)
	{
		return $this->mem->exists($id, $realm);
	}

	/**
	 * Stores data as object image in shared memory cache
	 * @param string $id Object identifier
	 * @param mixed $data Object value
	 * @param string $realm Realm name
	 * @param int $ttl Time to live, 0 for unlimited
	 * @return bool
	 */
	public function mem_set($id, $data, $realm = COT_DEFAULT_REALM, $ttl = COT_DEFAULT_TTL)
	{
		return $this->mem->store($id, $data, $realm, $ttl);
	}

	/**
	 * Removes cache image of the object from shared memory
	 * @param string $id Object identifier
	 * @param string $realm Realm name
	 */
	public function mem_unset($id, $realm = COT_DEFAULT_REALM)
	{
		$this->mem->remove($id, $realm);
	}

	/**
	 * Invalidates cache cells which were binded to the event.
	 * @param string $event Event name
	 * @return int Number of cells cleaned
	 */
	public function trigger($event)
	{
		$cnt = 0;
		if (count($this->bindings[$event]) > 0)
		{
			foreach ($this->bindings[$event] as $cell)
			{
				switch ($cell['type'])
				{
					case COT_CACHE_TYPE_DISK:
						$this->disk->remove($cell['id'], $cell['realm']);
					break;

					case COT_CACHE_TYPE_DB:
						$this->db->remove($cell['id'], $cell['realm']);
					break;

					case COT_CACHE_TYPE_MEMORY:
						$this->mem->remove($cell['id'], $cell['realm']);
					break;

					default:
						$this->mem->remove($cell['id'], $cell['realm']);
						$this->disk->remove($cell['id'], $cell['realm']);
						$this->db->remove($cell['id'], $cell['realm']);
				}
				$cnt++;
			}
		}
		return $cnt;
	}

	/**
	 * Removes event/cache bindings
	 * @param string $realm Realm name (required)
	 * @param string $id Object identifier. Optional, if not specified, all bindings from the realm are removed.
	 * @return int Number of bindings removed
	 */
	public function unbind($realm, $id = '')
	{
		global $db_cache_bindings;
		$c_realm = sed_sql_prep($realm);
		$q = "DELETE FROM `$db_cache_bindings` WHERE c_realm = '$c_realm'";
		if (!empty($id))
		{
			$c_id = sed_sql_prep($id);
			$q .= " AND c_id = '$c_id'";
		}
		sed_sql_query($q);
		$res = sed_sql_affectedrows();
		if ($res > 0)
		{
			$this->resync_on_exit = true;
		}
		return $res;
	}
}

/*
 * ================================ Old Cache Subsystem ================================
 */
// TODO scheduled for complete removal and replacement with new cache system

/**
 * Clears cache item
 * @deprecated Deprecated since 0.7.0, use $cot_cache object instead
 * @param string $name Item name
 * @return bool
 */
function sed_cache_clear($name)
{
	global $db_cache;

	sed_sql_query("DELETE FROM $db_cache WHERE c_name='$name'");
	return(TRUE);
}

/**
 * Clears cache completely
 * @deprecated Deprecated since 0.7.0, use $cot_cache object instead
 * @return bool
 */
function sed_cache_clearall()
{
	global $db_cache;
	sed_sql_query("DELETE FROM $db_cache");
	return TRUE;
}

/**
 * Clears HTML-cache
 *
 * @todo Add trigger support here to clean non-standard html fields
 * @return bool
 */
function sed_cache_clearhtml()
{
	global $db_pages, $db_forum_posts, $db_pm;
	$res = TRUE;
	$res &= sed_sql_query("UPDATE $db_pages SET page_html=''");
	$res &= sed_sql_query("UPDATE $db_forum_posts SET fp_html=''");
	$res &= sed_sql_query("UPDATE $db_pm SET pm_html = ''");
	return $res;
}

/**
 * Fetches cache value
 * @deprecated Deprecated since 0.7.0, use $cot_cache object instead
 * @param string $name Item name
 * @return mixed
 */
function sed_cache_get($name)
{
	global $cfg, $sys, $db_cache;

	$sql = sed_sql_query("SELECT c_value FROM $db_cache WHERE c_name='$name' AND c_expire>'".$sys['now']."'");
	if ($row = sed_sql_fetcharray($sql))
	{
		return(unserialize($row['c_value']));
	}
	else
	{
		return(FALSE);
	}
}

/**
 * Get all cache data and import it into global scope
 * @deprecated Deprecated since 0.7.0
 * @param int $auto Only with autoload flag
 * @return mixed
 */
function sed_cache_getall($auto = 1)
{
	global $cfg, $sys, $db_cache;

	$sql = sed_sql_query("DELETE FROM $db_cache WHERE c_expire<'".$sys['now']."'");
	if ($auto)
	{
		$sql = sed_sql_query("SELECT c_name, c_value FROM $db_cache WHERE c_auto=1");
	}
	else
	{
		$sql = sed_sql_query("SELECT c_name, c_value FROM $db_cache");
	}
	if (sed_sql_numrows($sql) > 0)
	{
		return($sql);
	}
	else
	{
		return(FALSE);
	}
}

/**
 * Puts an item into cache
 * @deprecated Deprecated since 0.7.0, use $cot_cache object instead
 * @param string $name Item name
 * @param mixed $value Item value
 * @param int $expire Expires in seconds
 * @param int $auto Autload flag
 * @return bool
 */
function sed_cache_store($name, $value, $expire, $auto = "1")
{
	global $db_cache, $sys, $cfg;

	if (!$cfg['cache']) return(FALSE);
	$sql = sed_sql_query("REPLACE INTO $db_cache (c_name, c_value, c_expire, c_auto) VALUES ('$name', '".sed_sql_prep(serialize($value))."', '".($expire + $sys['now'])."', '$auto')");
	return(TRUE);
}

?>