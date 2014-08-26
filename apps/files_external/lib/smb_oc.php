<?php
/**
 * Copyright (c) 2014 Robin McCorkell <rmccorkell@karoshi.org.uk>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

namespace OC\Files\Storage;


use Icewind\SMB\Exception\AccessDeniedException;
use Icewind\SMB\Exception\Exception;
use Icewind\SMB\Server;

class SMB_OC extends SMB {
	private $username_as_share;

	public function __construct($params) {
		if (isset($params['host']) && \OC::$session->exists('smb-credentials')) {
			$host = $params['host'];
			$this->username_as_share = ($params['username_as_share'] === 'true');

			$params_auth = \OC::$session->get('smb-credentials');
			$user = \OC::$session->get('loginname');
			$password = $params_auth['password'];

			$root = isset($params['root']) ? $params['root'] : '/';
			$share = '';

			if ($this->username_as_share) {
				$share = '/' . $user;
			} elseif (isset($params['share'])) {
				$share = $params['share'];
			} else {
				throw new \Exception();
			}
			parent::__construct(array(
				"user" => $user,
				"password" => $password,
				"host" => $host,
				"share" => $share,
				"root" => $root
			));
		} else {
			throw new \Exception();
		}
	}

	public static function login($params) {
		\OC::$session->set('smb-credentials', $params);
	}

	public function isSharable($path) {
		return false;
	}

	public function test($isPersonal = true) {
		if ($isPersonal) {
			if ($this->stat('')) {
				return true;
			}
			return false;
		} else {
			$server = new Server($this->server->getHost(), '', '');

			try {
				$server->listShares();
				return true;
			} catch (AccessDeniedException $e) {
				// expected due to anonymous login
				return true;
			} catch (Exception $e) {
				return false;
			}
		}
	}
}
