<?php

namespace go\core {

	use go\core\cache\CacheInterface;
	use go\core\cache\Disk;
	use go\core\db\Connection;
	use go\core\db\Database;
	use go\core\mail\Mailer;
	use go\core\fs\Folder;
	use go\core\jmap\State;

	/**
	 * Application class.
	 * 
	 * A singleton instance that can be accessed from anywhere in the framework with:
	 * 
	 * ```
	 * App::get()
	 * ```
	 * 
	 * 
	 * 
	 */
	class App extends Singleton {

		/**
		 *
		 * @var Connection
		 */
		private $dbConnection;

		/**
		 *
		 * @var ErrorHandler 
		 */
		private $errorHandler;

		/**
		 * @var Mailer
		 */
		private $mailer;

		/**
		 *
		 * @var CacheInterface 
		 */
		private $cache;

		protected function __construct() {
			date_default_timezone_set("UTC");

			$this->errorHandler = new ErrorHandler();
			$this->initCompatibility();

			parent::__construct();
		}
		
		public function getVersion() {
			return require(Environment::get()->getInstallFolder()->getPath() . '/version.php');
		}

		private function initCompatibility() {
			require(Environment::get()->getInstallFolder()->getPath() . "/go/GO.php");
			spl_autoload_register(array('GO', 'autoload'));
		}

		/**
		 * The mail object
		 * ```
		 * $message = App::getMailer()->compose();
		 * $message->setTo()->setFrom()->setBody()->send();
		 * ```
		 * @return Mailer
		 */
		public function getMailer() {
			if (!isset($this->mailer)) {
				$this->mailer = new Mailer();
			}
			return $this->mailer;
		}

		/**
		 * Get the installer object
		 * 
		 * @return Installer
		 */
		public function getInstaller() {
			if (!isset($this->installer)) {
				$this->installer = new Installer();
			}
			return $this->installer;
		}

		/**
		 * Get the data folder
		 * 
		 * @return Folder
		 */
		public function getDataFolder() {
			return new Folder($this->getConfig()['general']['dataPath']);
		}

		/**
		 * Get the temporary files folder
		 * 
		 * @return Folder
		 */
		public function getTmpFolder() {
			return new Folder($this->getConfig()['general']['tmpPath']);
		}

		private $config;

		/**
		 * Get the configuration data
		 * 
		 * ```
		 * 
		  "general" => [
		  "dataPath" => "/foo/bar"
		  ],
		  "db" => [
		  "dsn" => 'mysql:host=localhost;dbname=groupoffice,
		  "username" => "user",
		  "password" => "secret"
		  ]
		  ]
		 * ```
		 * @return array
		 */
		public function getConfig() {

			if (isset($this->config)) {
				return $this->config;
			}

			$ini = $this->findConfigFile('config.ini');
			if ($ini) {
				$this->config = parse_ini_file($ini, true);
				return $this->config;
			}
			if (defined('GO_CONFIG_FILE')) {
				$oldConfig = GO_CONFIG_FILE;
			} else {
				$oldConfig = $this->findConfigFile('config.php');
			}

			require($oldConfig);

			$this->config = [
					"general" => [
							"dataPath" => $config['file_storage_path'] ?? '/home/groupoffice',
							"tmpPath" => $config['tmpdir'] ?? sys_get_temp_dir() . '/groupoffice',
							"debug" => !empty($config['debug'])
					],
					"db" => [
							"dsn" => 'mysql:host=' . ($config['db_host'] ?? "localhost") . ';port=' . ($config['db_port'] ?? 3306) . ';dbname=' . ($config['db_name'] ?? "groupoffice"),
							"username" => $config['db_user'] ?? "groupoffice",
							"password" => $config['db_pass'] ?? ""
					],
					"limits" => [
							"maxUsers" => 0,
							"storageQuota" => 0,
							"allowedModules" => ""
					]
			];
			return $this->config;
		}

		/**
		 * Get the database connection
		 * 
		 * @return Connection
		 */
		public function getDbConnection() {

			if (!isset($this->dbConnection)) {
				$db = $this->getConfig()['db'];
				$this->dbConnection = new Connection(
								$db['dsn'], $db['username'], $db['password']
				);
			}
			return $this->dbConnection;
		}

		/**
		 *
		 * @var Database
		 */
		private $database;

		/**
		 * Get the database object
		 * 
		 * @return Database
		 */
		public function getDatabase() {
			if (!isset($this->database)) {
				$this->database = new Database();
			}

			return $this->database;
		}

		/**
		 *
		 * @var Installer
		 */
		private $installer;

		/**
		 * Get a simple key value caching object
		 * 
		 * @return Disk
		 */
		public function getCache() {
			if (!isset($this->cache)) {

				$this->cache = new Disk();
//			if(!$this->cache->isSupported()) {
//				$this->cache = new cache\None();
//			}
			}
			return $this->cache;
		}

		/**
		 * Get a simple key value caching object
		 * 
		 * @return Debugger
		 */
		public function getDebugger() {
			if (!isset($this->debugger)) {
				$this->debugger = new Debugger();
			}

			return $this->debugger;
		}

		/**
		 * Add debug output
		 * 
		 * {@see Debugger::debug()}
		 * 
		 * @todo calls that happen in jsonSerialize() are never sent to output
		 * 
		 * @param string|callable|array|object $msg
		 */
		public function debug($msg, $type = 'general', $traceBackSteps = 0) {
			$this->getDebugger()->debug($msg, $type, $traceBackSteps);
		}

		private $authState;

		public function setAuthState(auth\State $authState) {
			$this->authState = $authState;
		}

		/**
		 * Get the authentication handler
		 * 
		 * @return State
		 */
		public function getAuthState() {
			return $this->authState;
		}

		/**
		 * Get the authenticated user
		 * 
		 * @return auth\model\User
		 */
		public function getUser() {
			if ($this->getAuthState() instanceof \go\core\auth\State) {
				return $this->authState->getUser();
			}
			return null;
		}

		/**
		 * Get the application settings
		 * 
		 * @return AppSettings
		 */
		public function getSettings() {
			return \go\modules\core\core\model\Settings::get();
		}

		/**
		 * Translates a language variable name into the local language.
		 * 
		 * @param String $str String to translate
		 * @param String $module Name of the module to find the translation
		 * @param String $coreSection Only applies if module is set to 'base'
		 */
		public function t($str, $moduleName, $coreSection = 'common') {
			return Language::get()->t($str, $moduleName, $coreSection);
		}

		public static function findConfigFile($name = 'config.ini') {

			$count = 0;
			$workingDir = __DIR__;

			while ($count != 10) {
				$count++;
				$workingFile = $workingDir . '/' . $name;

				if (file_exists($workingFile)) {
					return $workingFile;
				}

				$workingDir = dirname($workingDir);

				if ($count == 10 || dirname($workingDir) == $workingDir) {
					//hit max of 10 or root of filesystem
					break;
				}
			}

			if (!empty($_SERVER['SERVER_NAME'])) {
				$workingFile = '/etc/groupoffice/' . $_SERVER['SERVER_NAME'] . '/' . $name;
				if (file_exists($workingFile)) {
					return $workingFile;
				}
			}

			$workingFile = '/etc/groupoffice/' . $name;
			if (file_exists($workingFile)) {
				return $workingFile;
			}
			return false;
		}

	}

}

namespace {

	/**
	 * @return App
	 */
	function GO() {
		return \go\core\App::get();
	}

}