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

namespace OCA\Deck\Controller;

use OCA\Deck\Db\Acl;
use OCA\Deck\Service\SubBoardService;
use OCA\Deck\Service\PermissionService;
use OCP\AppFramework\ApiController;
use OCP\IRequest;

class SubBoardController extends BoardController {

    protected $boardService;
    protected $userId;
    protected $permissionService;

	public function __construct($appName, IRequest $request, SubBoardService $boardService, PermissionService $permissionService, $userId) {
		parent::__construct($appName, $request, $boardService, $permissionService, $userId);

        $this->boardService = $boardService;
        $this->userId = $userId;
        $this->permissionService = $permissionService;
    }

	/**
	 * @NoAdminRequired
	 * @param $title
	 * @param $color
	 * @return \OCP\AppFramework\Db\Entity
	 */
	public function create($title, $color, $parent = null) {
        return $this->boardService->create($title, $this->userId, $color, $parent);
            
    }

    /**
     * @NoAdminRequired
     * @param $parent
     */
    public function index($parent = null) {
        return $this->boardService->findAll();
    }

    /**
     * Return all objects by parent id
     * 
     * @NoAdminRequired
     * @param integer $parent_id
     * @return void
     */
    public function findByParent($parent_id) {
        return $this->boardService->findByParent($parent_id);
    }
    
}