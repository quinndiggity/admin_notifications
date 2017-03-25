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

namespace OCA\AdminNotifications\Controller;

use OCA\AdminNotifications\AppInfo\Application;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\DataResponse;
use OCP\AppFramework\OCSController;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\IRequest;
use OCP\IUser;
use OCP\IUserManager;
use OCP\Notification\IManager;

class APIController extends OCSController {

	/** @var ITimeFactory */
	protected $timeFactory;

	/** @var IUserManager */
	protected $userManager;

	/** @var IManager */
	protected $notificationManager;

	/**
	 * @param string $appName
	 * @param IRequest $request
	 * @param ITimeFactory $timeFactory
	 * @param IUserManager $userManager
	 * @param IManager $notificationManager
	 */
	public function __construct($appName, IRequest $request, ITimeFactory $timeFactory, IUserManager $userManager, IManager $notificationManager) {
		parent::__construct($appName, $request);

		$this->timeFactory = $timeFactory;
		$this->userManager = $userManager;
		$this->notificationManager = $notificationManager;
	}

	/**
	 * @param string $userId
	 * @param string $shortMessage
	 * @param string $longMessage
	 * @return DataResponse
	 */
	public function generateNotification($userId, $shortMessage, $longMessage) {
		$shortMessage = (string) $shortMessage;
		$longMessage = (string) $longMessage;

		$user = $this->userManager->get($userId);

		if (!$user instanceof IUser) {
			return new DataResponse(null, Http::STATUS_NOT_FOUND);
		}

		if ($shortMessage === '' || strlen($shortMessage) > 255) {
			return new DataResponse(null, Http::STATUS_BAD_REQUEST);
		}

		if ($longMessage !== '' && strlen($longMessage) > 4000) {
			return new DataResponse(null, Http::STATUS_BAD_REQUEST);
		}

		$notification = $this->notificationManager->createNotification();
		$time = $this->timeFactory->getTime();
		$datetime = new \DateTime();
		$datetime->setTimestamp($time);

		try {
			$notification->setApp(Application::APP_ID)
				->setUser($user->getUID())
				->setDateTime($datetime)
				->setObject(Application::APP_ID, dechex($time))
				->setSubject('ocs', [$shortMessage]);

			if ($longMessage !== '') {
				$notification->setMessage('ocs', [$longMessage]);
			}

			$this->notificationManager->notify($notification);
		} catch (\InvalidArgumentException $e) {
			return new DataResponse(null, Http::STATUS_INTERNAL_SERVER_ERROR);
		}

		return new DataResponse();
	}
}
