<?php
namespace DG\T3Less\Controller;
	/* * *************************************************************
     *  Copyright notice
     *
     *  (c) 2013 David Greiner <hallo@davidgreiner.de>
     *  All rights reserved
     *
     *  This script is part of the TYPO3 project. The TYPO3 project is
     *  free software; you can redistribute it and/or modify
     *  it under the terms of the GNU General Public License as published by
     *  the Free Software Foundation; either version 3 of the License, or
     *  (at your option) any later version.
     *
     *  The GNU General Public License can be found at
     *  http://www.gnu.org/copyleft/gpl.html.
     *
     *  This script is distributed in the hope that it will be useful,
     *  but WITHOUT ANY WARRANTY; without even the implied warranty of
     *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
     *  GNU General Public License for more details.
     *
     *  This copyright notice MUST APPEAR in all copies of the script!
     * ************************************************************* */

/**
 *
 *
 * @package TYPO3
 * @subpackage t3_less
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 * @author  David Greiner <hallo@davidgreiner.de>
 * @author  Thomas Heuer <technik@thomas-heuer.de>
 */
class BaseController extends \TYPO3\CMS\Extbase\Mvc\Controller\ActionController
{
	/**
	 * configuration array from constants
	 * @var array $configuration
	 */
	protected $configuration;

	/**
	 * folder for lessfiles
	 * @var string $lessfolder
	 */
	protected $lessfolder;

	/**
	 * folder for compiled files
	 * @var string $outputfolder
	 */
	protected $outputfolder;

	public function __construct()
	{
		//makeInstance should not be used, but injection does not work without FE-plugin?
		if( TYPO3_MODE != 'FE' )
		{
			return;
		}

		$objectManager = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance( 'TYPO3\\CMS\\Extbase\\Object\\ObjectManager' );
		$configurationManager = $objectManager->get( 'TYPO3\\CMS\\Extbase\\Configuration\\ConfigurationManagerInterface' );
		$configuration = $configurationManager->getConfiguration(
			\TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface::CONFIGURATION_TYPE_FRAMEWORK, 'T3Less', ''
		);
		$this->configuration = $configuration;
		$cObj = $configurationManager->getContentObject();
		$tsconfig = $GLOBALS['TSFE']->tmpl->setup['plugin.']['tx_t3less.'];
		if(is_array($this->configuration['files']['pathToLessFiles'])) {
			$this->lessfolder = \DG\T3Less\Utility\Utilities::getPath( $cObj->cObjGetSingle($tsconfig['files.']['pathToLessFiles'],$tsconfig['files.']['pathToLessFiles.']) );
		} else {
			$this->lessfolder = \DG\T3Less\Utility\Utilities::getPath( $cObj->cObjGetSingle($tsconfig['files.']['pathToLessFiles'],$tsconfig['files.']['pathToLessFiles.']) );
		}
		if(is_array($this->configuration['files']['outputFolder'])) {
			$this->outputfolder = \DG\T3Less\Utility\Utilities::getPath(  $cObj->cObjGetSingle($tsconfig['files.']['outputFolder'], $tsconfig['files.']['outputFolder.']) );
		} else {
			$this->outputfolder = \DG\T3Less\Utility\Utilities::getPath(  $cObj->cObjGetSingle($tsconfig['files.']['outputFolder'], $tsconfig['files.']['outputFolder.']) );
		}
		parent::__construct();
	}

	/**
	 * action base
	 *
	 */
	public function baseAction()
	{
		if( TYPO3_MODE != 'FE' )
		{
			return;
		}

		$files = array( );
		// compiler activated?
		if( $this->configuration['other']['activateCompiler'] )
		{
			// folders defined?
			if( $this->lessfolder && $this->outputfolder )
			{
				// are there files in the defined less folder?
				if( \TYPO3\CMS\Core\Utility\GeneralUtility::getFilesInDir( $this->lessfolder, "less", TRUE ) )
				{
					$files = \TYPO3\CMS\Core\Utility\GeneralUtility::getFilesInDir( $this->lessfolder, "less", TRUE );

				}
				else
				{
					echo \DG\T3Less\Utility\Utilities::wrapErrorMessage( \TYPO3\CMS\Extbase\Utility\LocalizationUtility::translate( 'noLessFilesInFolder', $this->extensionName, $arguments = array( 's' => $this->lessfolder ) ) );
				}
			}
			else
			{
				echo \DG\T3Less\Utility\Utilities::wrapErrorMessage( \TYPO3\CMS\Extbase\Utility\LocalizationUtility::translate( 'emptyPathes', $this->extensionName ) );
			}
		}


		/* Hook to pass less-files from other extension, see manual */
		if( isset( $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['t3less']['addForeignLessFiles'] ) )
		{
			foreach( $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['t3less']['addForeignLessFiles'] as $hookedFilePath )
			{
				$hookPath = \DG\T3Less\Utility\Utilities::getPath( $hookedFilePath );
				$files[] = \TYPO3\CMS\Core\Utility\GeneralUtility::getFilesInDir( $hookPath, "less", TRUE );
			}
			$files = \DG\T3Less\Utility\Utilities::flatArray( null, $files );
		}

		$newstamp = 0;
		$dirs = \TYPO3\CMS\Core\Utility\GeneralUtility::get_dirs( $this->lessfolder);
		if($this->lessfolder != 'typo3conf/ext/template_local/Resources/Public/Stylesheet/less/') {
			$dirsmain = \TYPO3\CMS\Core\Utility\GeneralUtility::get_dirs('typo3conf/ext/template_local/Resources/Public/Stylesheet/less/');
		}

		foreach($dirs as $dir) {
			$infiles = \TYPO3\CMS\Core\Utility\GeneralUtility::getFilesInDir( $this->lessfolder.$dir, "less", TRUE );
			foreach($infiles as $ffiles) {
				$timedat = filemtime($ffiles);
				if ($timedat > $newstamp) {
					$newstamp = $timedat;
				}
			}
		}
		if($this->lessfolder != 'typo3conf/ext/template_local/Resources/Public/Stylesheet/less/') {
			foreach ($dirsmain as $dir) {
				$infiles = \TYPO3\CMS\Core\Utility\GeneralUtility::getFilesInDir('typo3conf/ext/template_local/Resources/Public/Stylesheet/less/' . $dir, "less", TRUE);
				foreach ($infiles as $ffiles) {
					$timedat = filemtime($ffiles);
					if ($timedat > $newstamp) {
						$newstamp = $timedat;
					}
				}
			}
		}

		foreach($files as $file) {
			if(strstr($file, 'main.less')) {
				$content = file_get_contents($file);
				$tempcontent = explode('//version',$content);
				if(strlen($tempcontent[0])>10 && intval(trim($tempcontent[1])) != $newstamp) { 
					$content = $tempcontent[0].'//version'.$newstamp;
					$test = file_put_contents($file,$content);
				} else {
					\TYPO3\CMS\Core\Utility\GeneralUtility::sysLog('Empty file detected (file_get_contents):'.$file,'t3_less',2);
				}
			}
		}


		switch( $this->configuration['enable']['mode'] )
		{
			case 'PHP-Compiler':
				$controller = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance( 'DG\\T3Less\\Controller\\LessPhpController' );
				$controller->lessPhp( $files );
				break;

			case 'JS-Compiler':
				$controller = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance( 'DG\\T3Less\\Controller\\LessJsController' );
				$controller->lessJs( $files );
				break;

			case 'JS-Compiler via Node.js':
				$controller = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance( 'DG\\T3Less\\Controller\\LessJsNodeController' );
				if( $controller->isLesscInstalled() )
				{
					$controller->lessc( $files );
				}
				else
				{
					echo \DG\T3Less\Utility\Utilities::wrapErrorMessage( \TYPO3\CMS\Extbase\Utility\LocalizationUtility::translate( 'lesscRequired', $this->extensionName ) );
				}
				break;
		}
	}

}

