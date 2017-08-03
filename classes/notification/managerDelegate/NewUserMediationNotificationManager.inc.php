<?php
/**
 * @file classes/notification/managerDelegate/NewUserMediationNotificationManager.inc.php
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2003-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class NewUserMediationNotificationManager
 * @ingroup managerDelegate
 *
 * @brief Pending approval of new user notification
 */

import('lib.pkp.classes.notification.NotificationManagerDelegate');
import('classes.core.PageRouter');

class NewUserMediationNotificationManager extends NotificationManagerDelegate {
	
	/**
	 * Constructor.
	 * @param $notificationType int NOTIFICATION_TYPE_...
	 */
	function __construct($notificationType) {
		parent::__construct($notificationType);
	}
	
	/**
	 * @copydoc PKPNotificationOperationManager::getStyleClass()
	 */
	public function getStyleClass($notification) {
		return NOTIFICATION_TYPE_USER_MEDIATION;
	}
	
	/**
	 * @copydoc PKPNotificationOperationManager::getNotificationUrl()
	 */
	public function getNotificationUrl($request, $notification) {
		$pageRouter = new PageRouter();
		return $pageRouter->url($request, null, 'management', 'settings', 'access');
	}
	
	/**
	 * @copydoc PKPNotificationOperationManager::getNotificationMessage()
	 */
	public function getNotificationMessage($request, $notification) {
		$userDAO = DAORegistry::getDAO('UserDAO');
		$user = $userDAO->getById($notification->getUserId());
		return __('notification.type.pendingMediation',array('username' => $user->getUsername()));
	}
	
	/**
	 * @copydoc PKPNotificationOperationManager::getNotificationTitle()
	 */
	public function getNotificationTitle($notification) {
		return __('notification.type.pendingMediation.title');
	}
	
	/**
	 * @copydoc NotificationManagerDelegate::updateNotification()
	 */
	public function updateNotification($request, $userIds, $assocType, $assocId) {
		$userId = current($userIds);
		$removeNotifications = false;
		
		$userDAO = DAORegistry::getDAO('UserDAO');
		$user = $userDAO->getById($assocId);

		if ($user->getDisabled() & USER_DISABLED_MEDIATION) {
			$context = $request->getContext();
			$notificationDao = DAORegistry::getDAO('NotificationDAO'); /* @var $notificationDao NotificationDAO */
			$notificationFactory = $notificationDao->getByAssoc(
					ASSOC_TYPE_USER,
					$assocId,
					$userId,
					NOTIFICATION_TYPE_USER_MEDIATION,
					$context->getId()
					);
			if ($notificationFactory->wasEmpty()) {				// Create or update a pending revision task notification.
				$notificationDao = DAORegistry::getDAO('NotificationDAO'); /* @var $notificationDao NotificationDAO */
				$notificationDao->build(
						$context->getId(),
						NOTIFICATION_LEVEL_TASK,
						$this->getNotificationType(),
						ASSOC_TYPE_USER,
						$assocId,
						null
						);
			}
		} else {
			$removeNotifications = true;
		}
	
		
		if ($removeNotifications) {
			$context = $request->getContext();
			$notificationDao = DAORegistry::getDAO('NotificationDAO');
			$notificationDao->deleteByAssoc(ASSOC_TYPE_USER, $assocId, null, $this->getNotificationType(), $context->getId());
			$notificationDao->deleteByAssoc(ASSOC_TYPE_USER, $assocId, null, NOTIFICATION_TYPE_USER_MEDIATION, $context->getId());
		}
	}
}


?>