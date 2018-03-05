<?php

/**
 * Description of Logger
 */
class Logger {

	protected $enableXMLLogs;
	protected $logPath;
	protected $folderNamePattern;
	protected $fileNamePrefix;
	protected $enableInternalLogs;
	protected $fileName;

	public function __construct(Configuration $configuration) {
		$this->enableXMLLogs = $configuration->enableXMLLogs;
		$this->logPath = $configuration->logPath;
		$this->folderNamePattern = $configuration->folderNamePattern;
		$this->fileNamePrefix = $configuration->fileNamePrefix;
		$this->enableInternalLogs = $configuration->enableInternalLogs;
		$this->fileName = $configuration->fileName;
	}

	/**
	 * Logs a trace message to the config['fileName']
	 * 
	 * @param string $message
	 */
	public function Log($message) {
		$callers = debug_backtrace();
		$this->Write($callers[1]['function'], $message);
	}

    /** Saves the desired dom or xml into the logPath folder
     * @param DOMDocument/string $dom
     * @param bool $isXML
     * @param string $fileName
     */
    public function LogXmlMessage($dom, $isXML = false, $fileName = '') {

        if ($this->enableXMLLogs) {
			if ($isXML) {
				$domtree = new DOMDocument();
				$domtree->loadXML($dom);

				$dom = $domtree;
			}

			$dirName = $this->logPath . date($this->folderNamePattern);
			if (!file_exists($dirName)) {
				mkdir($dirName, 0777, true);
			}

			$t = microtime(true);
			$micro = sprintf("%06d", ($t - floor($t)) * 1000000);
			$d = new DateTime(date('Y-m-d H:i:s.' . $micro, $t));

            // $dom->save($dirName . '/' . $d->format($this->fileNamePrefix) . '-' . (!empty($dom->documentElement->localName) ? $dom->documentElement->localName : '') . '.xml');
            $name = (!empty($dom->documentElement->localName) ? $dom->documentElement->localName : '');
            if (!empty($fileName)) {
                $name = $fileName;
            }
            $dom->save($dirName . '/' . $d->format($this->fileNamePrefix) . '-' . $name . '.xml');
        }
	}

	public function Write($function, $message) {

		if ($this->enableInternalLogs) {
			$dirName = $this->logPath . date($this->folderNamePattern);
			if (!file_exists($dirName)) {
				mkdir($dirName, 0777, true);
			}

			file_put_contents($dirName . '/' . $this->fileName, date('Y-m-d H:i:s : ') . $function . '() - ' . $message . "\n", FILE_APPEND);
		}
	}

}
