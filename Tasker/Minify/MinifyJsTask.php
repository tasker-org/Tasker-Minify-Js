<?php
/**
 * Class MinifyJsTask
 *
 * @author: Jiří Šifalda <sifalda.jiri@gmail.com>
 * @date: 19.11.13
 */
namespace Tasker\Minify;

use JShrink\Minifier;
use Tasker\Tasks\Task;
use Tasker\Concat\IConcatFiles;
use Tasker\Utils\FileSystem;
use Tasker\Concat\ConcatFiles;
use Tasker\InvalidStateException;

class MinifyJsTask extends Task
{

	/** @var IConcatFiles  */
	private $concatFiles;

	/**
	 * @param IConcatFiles $concatFiles
	 */
	function __construct(IConcatFiles $concatFiles = null)
	{
		if($concatFiles === null) {
			$concatFiles = new ConcatFiles;
		}

		$this->concatFiles = $concatFiles;
	}

	/**
	 * @param array $config
	 * @return array|mixed|string
	 * @throws \Tasker\InvalidStateException
	 */
	public function run($config)
	{
		if(!isset($config['files']) || !count($config['files'])) {
			return 'No files to process. Please set "files" section.';
		}

		$results = array();
		$files = $config['files'];
		unset($config['files']);

		foreach($files as $dest => $sources) {
			if(!is_string($dest)) {
				throw new InvalidStateException('Destination must be valid path. "' . $dest . '" given.');
			}

			$files = $files = $this->concatFiles->getFiles($sources);
			$content = $this->getMinified($this->concatFiles->getFilesContent($files, $this->setting->getRootPath()), $config);
			$result = FileSystem::write($this->setting->getRootPath() . DIRECTORY_SEPARATOR . $dest, $content);

			if($result === false) {
				$results[] = 'File "' . $dest . '" cannot be concatenated.';
			}else{
				$results[] = 'File "' . $dest . '" was concatenated and minified. ' . count($files) . ' files included.';
			}
		}

		return $results;
	}

	/**
	 * @param $content
	 * @param $config
	 * @return string
	 */
	protected function getMinified($content, $config)
	{
		return Minifier::minify($content, $config);
	}
}