<?php
/**
 * @copyright Copyright (c) 2017 Joas Schilling <coding@schilljs.com>
 *
 * @author Joas Schilling <coding@schilljs.com>
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

namespace OCA\Notifications\Tests\Unit\Controller;

use OCA\Notifications\Controller\APIController;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\DataResponse;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\IRequest;
use OCP\IUser;
use OCP\IUserManager;
use OCP\Notification\IManager;
use OCP\Notification\INotification;

/**
 * Class APIControllerTest
 *
 * @package OCA\Notifications\Tests\Unit\Controller
 * @group DB
 */
class APIControllerTest extends \Test\TestCase {
	/** @var ITimeFactory|\PHPUnit_Framework_MockObject_MockObject */
	protected $timeFactory;
	/** @var IUserManager|\PHPUnit_Framework_MockObject_MockObject */
	protected $userManager;
	/** @var IManager|\PHPUnit_Framework_MockObject_MockObject */
	protected $notificationManager;
	/** @var APIController */
	protected $controller;

	protected function setUp(): void {
		parent::setUp();

		/** @var IRequest|\PHPUnit_Framework_MockObject_MockObject $request */
		$request = $this->createMock(IRequest::class);
		$this->timeFactory = $this->createMock(ITimeFactory::class);
		$this->userManager = $this->createMock(IUserManager::class);
		$this->notificationManager = $this->createMock(IManager::class);

		$this->controller = new APIController(
			'notifications',
			$request,
			$this->timeFactory,
			$this->userManager,
			$this->notificationManager
		);
	}

	public function dataGenerateNotification() {
		return [
			['user', '', '', false, null, false, null, 123, null, Http::STATUS_NOT_FOUND],
			['user', '', '', false, null, false, 'user', 123, null, Http::STATUS_BAD_REQUEST],
			['user', str_repeat('a', 256), '', false, null, false, 'user', 123, null, Http::STATUS_BAD_REQUEST],
			['user', 'short', '', true, false, false, 'user', 123, '7b', Http::STATUS_OK],
			['user', 'short', str_repeat('a', 4001), false, null, false, 'user', 123, null, Http::STATUS_BAD_REQUEST],
			['user', 'short', str_repeat('a', 4000), true, false, true, 'user', 123, '7b', Http::STATUS_OK],
			['user', 'short', 'long', true, true, true, 'user', 123, '7b', Http::STATUS_INTERNAL_SERVER_ERROR],
		];
	}

	/**
	 * @dataProvider dataGenerateNotification
	 * @param string $userId
	 * @param string $short
	 * @param string $long
	 * @param bool $createNotification
	 * @param bool $notifyThrows
	 * @param bool $validLong
	 * @param string|null $user
	 * @param int $time
	 * @param string|null $hexTime
	 * @param int $statusCode
	 */
	public function testGenerateNotification($userId, $short, $long, $createNotification, $notifyThrows, $validLong, $user, $time, $hexTime, $statusCode) {
		if ($user !== null) {
			$u = $this->createMock(IUser::class);
			$u->expects($createNotification ? $this->once() : $this->never())
				->method('getUID')
				->willReturn($user);
		} else {
			$u = null;
		}
		$this->userManager->expects($this->any())
			->method('get')
			->with($userId)
			->willReturn($u);

		$this->timeFactory->expects($hexTime === null ? $this->never() : $this->once())
			->method('getTime')
			->willReturn($time);

		if ($createNotification) {
			$n = $this->createMock(INotification::class);
			$n->expects($this->once())
				->method('setApp')
				->with('admin_notifications')
				->willReturnSelf();
			$n->expects($this->once())
				->method('setUser')
				->with($user)
				->willReturnSelf();
			$n->expects($this->once())
				->method('setDateTime')
				->willReturnSelf();
			$n->expects($this->once())
				->method('setObject')
				->with('admin_notifications', $hexTime)
				->willReturnSelf();
			$n->expects($this->once())
				->method('setSubject')
				->with('ocs', [$short])
				->willReturnSelf();
			if ($validLong) {
				$n->expects($this->once())
					->method('setMessage')
					->with('ocs', [$long])
					->willReturnSelf();
			} else {
				$n->expects($this->never())
					->method('setMessage');
			}

			$this->notificationManager->expects($this->once())
				->method('createNotification')
				->willReturn($n);

			if ($notifyThrows === null) {
				$this->notificationManager->expects($this->never())
					->method('notify');
			} else if ($notifyThrows === false) {
				$this->notificationManager->expects($this->once())
					->method('notify')
					->with($n);
			} else if ($notifyThrows === true) {
				$this->notificationManager->expects($this->once())
					->method('notify')
					->willThrowException(new \InvalidArgumentException());
			}
		}

		$response = $this->controller->generateNotification($userId, $short, $long);

		$this->assertInstanceOf(DataResponse::class, $response);
		$this->assertSame($statusCode, $response->getStatus());
	}
}
