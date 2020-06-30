<?php
/**
 * @copyright Copyright (c) 2020 Giovanny Avila <gjavilae@gmail.com>
 *
 * @author Giovanny Avila <gjavilae@gmail.com>
 *
 * @license GNU AGPL version 3 or any later version
 *
 *  This program is free software: you can redistribute it and/or modify
 *  it under the terms of the GNU Affero General Public License as
 *  published by the Free Software Foundation, either version 3 of the
 *  License, or (at your option) any later version.
 *
 *  This program is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU Affero General Public License for more details.
 *
 *  You should have received a copy of the GNU Affero General Public License
 *  along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 */

 namespace OCA\Deck\Service;

 use OCA\Deck\Activity\ActivityManager;
 use OCA\Deck\Activity\ChangeSet;
 use OCA\Deck\Db\Acl;
 use OCA\Deck\Db\AclMapper;
 use OCA\Deck\Db\AssignedUsersMapper;
 use OCA\Deck\Db\ChangeHelper;
 use OCA\Deck\Db\IPermissionMapper;
 use OCA\Deck\Db\Label;
 use OCA\Deck\Db\Stack;
 use OCA\Deck\Db\StackMapper;
 use OCA\Deck\NoPermissionException;
 use OCA\Deck\Notification\NotificationHelper;
 use OCP\AppFramework\Db\DoesNotExistException;
 use OCP\IGroupManager;
 use OCP\IL10N;
 use OCA\Deck\Db\Subboard;
 use OCA\Deck\Db\SubboardMapper;
 use OCA\Deck\Db\LabelMapper;
 use OCP\IUserManager;
 use OCA\Deck\BadRequestException;
 use Symfony\Component\EventDispatcher\EventDispatcherInterface;
 use Symfony\Component\EventDispatcher\GenericEvent;

 class SubBoardService extends BoardService
 {

    protected $boardMapper;

	public function __construct(
		SubboardMapper $boardMapper,
		StackMapper $stackMapper,
		IL10N $l10n,
		LabelMapper $labelMapper,
		AclMapper $aclMapper,
		PermissionService $permissionService,
		NotificationHelper $notificationHelper,
		AssignedUsersMapper $assignedUsersMapper,
		IUserManager $userManager,
		IGroupManager $groupManager,
		ActivityManager $activityManager,
		EventDispatcherInterface $eventDispatcher,
		ChangeHelper $changeHelper,
		$userId
	) {
        parent::__construct(
            $boardMapper,
            $stackMapper,
            $l10n,
            $labelMapper,
            $aclMapper,
            $permissionService,
            $notificationHelper,
            $assignedUsersMapper,
            $userManager,
            $groupManager,
            $activityManager,
            $eventDispatcher,
            $changeHelper,
            $userId
        );

        $this->boardMapper = $boardMapper;
    }
    
     /**
      *
      * @param [type] $title
      * @param [type] $userId
      * @param [type] $color
      * @param [type] $parent
      * @return void
      */
     public function create($title, $userId, $color, $parent = null)
     {
         $new_board = parent::create($title, $userId, $color);

         if( !is_null($parent) ) {
             $board = $this->boardMapper->find( $new_board->getId() );
             $board->setbelongs_board_id($parent);
             $this->boardMapper->update($board);
         }

         return $new_board;
     }

     /**
      * Filter: just return all entries where belongs_noard_id is null
      *
      * @return array
      */
     public function findAll() {
         $entries = parent::findAll();
         
         $remove_entries = [];
         foreach( $entries as $key => $entry) {
             if( !is_null($entry->getBelongsBoardId()) )
                array_push($remove_entries, $key);
         }

         foreach( $remove_entries as $key => $value )
            unset($entries[$value]);

        return array_values($entries);
     }

 }