<?php

declare(strict_types=1);
/**
 * @copyright Copyright (c) 2020, Roeland Jago Douma <roeland@famdouma.nl>
 *
 * @author Roeland Jago Douma <roeland@famdouma.nl>
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 */

namespace OCA\Notifications\Push;

use OCP\AppFramework\Services\IPush;

class WebPush {

	/** @var IPush */
	private $push;

	public function __construct(IPush $push) {
		$this->push = $push;
	}

	public function pushNotify(string $uid) {
		$this->push->publish($uid, new WebPushContent(['action' => 'add']));
	}

	public function pushDelete(string $uid) {
		$this->push->publish($uid, new WebPushContent(['action' => 'delete']));
	}
}
