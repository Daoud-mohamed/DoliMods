<?php
/* Copyright (C) 2006-2011  Laurent Destailleur     <eldy@users.sourceforge.net>
 * Copyright (C) 2024		Frédéric France			<frederic.france@free.fr>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 * or see https://www.gnu.org/
 */

/**
 *      \file       htdocs/core/modules/security/captcha/modCaptchaGoogle.class.php
 *      \ingroup    core
 *		\brief      File to manage captcha generation according to dolibarr native code
 */

require_once DOL_DOCUMENT_ROOT.'/core/modules/security/captcha/modules_captcha.php';


/**
 *	Class to generate a password according to a dolibarr standard rule (12 random chars)
 */
class modCaptchaGoogle extends ModeleCaptcha
{
	/**
	 * @var string ID
	 */
	public $id;

	public $picto = 'fa-shield-alt';

	/**
	 *	Constructor
	 *
	 *  @param		DoliDB		$db			Database handler
	 *	@param		Conf		$conf		Handler de conf
	 *	@param		Translate	$langs		Handler de langue
	 *	@param		User		$user		Handler du user connected
	 */
	public function __construct($db, $conf, $langs, $user)
	{
		$this->id = strtolower(preg_replace('/^modCaptcha/i', '', get_class()));

		//$this->picto = 'fab-google';

		$this->db = $db;
		$this->conf = $conf;
		$this->langs = $langs;
		$this->user = $user;
	}

	/**
	 *		Return description of module
	 *
	 *      @return     string      Description of module
	 */
	public function getDescription()
	{
		global $langs;
		return $langs->trans("GoogleCaptchaV3");
	}

	/**
	 * 		Return an example of password generated by this module
	 *
	 *      @return     string      Example of password
	 */
	public function getExample()
	{
		return '';
	}


	/**
	 * 	Return the HTML content to output on a form that need the captcha
	 *
	 *  @param		string	$php_self	An URL for the a href link
	 *  @return     string				The HTML code to ouput
	 */
	public function getCaptchaCodeForForm($php_self = '')
	{
		// Show the captcha code on form

		return '';
	}


	/**
	 * 	Validate a captcha
	 * 	This function is called on loggin submission to validate a captcha, before validating a password.
	 *
	 *  @return     int					0 if KO, >0 if OK
	 */
	public function validateCodeAfterLoginSubmit()
	{
		return 1;
	}
}