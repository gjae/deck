<!--
* @copyright Copyright (c) 2018 Michael Weimann <mail@michael-weimann.eu>
*
* @author Michael Weimann <mail@michael-weimann.eu>
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
* along with this program. If not, see <http://www.gnu.org/licenses/>.
*
-->

<template>
	<v-collapse-wrapper  ref="collapse_content" >
		<router-link :id="`board-${board.id}`"
			:title="board.title"
			:to="routeTo"
			class="board-list-row"
			tag="div">

			<div v-collapse-toggle @click.prevent.stop="toggleCollapse">
				<div class="board-list-bullet-cell">
					<div :style="{ 'background-color': `#${board.color}` }" class="board-list-bullet" />
				</div>
			</div>

			<div class="board-list-title-cell">
				{{ board.title }}
			</div>
			<div class="board-list-avatars-cell" title="">
				<Avatar :user="board.owner.uid" :display-name="board.owner.displayname" class="board-list-avatar" />
				<Avatar v-for="user in limitedAcl"
					:key="user.id"
					:user="user.participant.uid"
					:display-name="user.participant.displayname"
					class="board-list-avatar" />
				<div v-if="board.acl.length > 5" v-tooltip="otherAcl" class="avatardiv popovermenu-wrapper board-list-avatar icon-more" />
			</div>
			<strong class="icon-more"></strong>
			<div class="board-list-actions-cell" />
		</router-link>
		<div class="content" v-collapse-content v-show="collapseContent">
			<div class="board-list-row">
				<div v-if="isFetching" style="text-align: center">
					Cargando ...
				</div>
			</div>
			<div  
				v-show="canDisplayBoardChildren"  
				v-for="board in childBoards" :key="board.id" 
				class="sub-board-list">
				<BoardItem :board="board" :boardLevel="nextPaddingLevel" />
			</div>
		</div>
	</v-collapse-wrapper>
</template>

<script>
import { Avatar } from '@nextcloud/vue'

export default {
	name: 'BoardItem',
	components: {
		Avatar,
	},
	props: {
		board: {
			type: Object,
			default: () => { return {} },
		},
		paddingIdentend: {
			default: 17
		},
		boardLevel : {
			default: 0
		},
	},
	data() {
		return  {
			collapseContent: false,
			childBoards: [],
			isFetching: false
		}
	},
	methods: {
		toggleCollapse(e) {

			this.collapseContent = !this.collapseContent
			this.isFetching = true
			this.$store.dispatch('loadBoardsByParentId', {parentId: this.board.id})
				.then(
					(boards) => {
						this.childBoards = boards
					}
				)
				.finally(()=> this.isFetching = false )
		}
	},
	computed: {
		routeTo: function() {
			return {
				name: 'board',
				params: { id: this.board.id },
			}
		},
		limitedAcl() {
			return [...this.board.acl].splice(0, 5)
		},
		otherAcl() {
			return [...this.board.acl].splice(6).map((item) => item.participant.displayname || item.participant).join(', ')
		},
		canDisplayBoardChildren() {
			return !this.isFetching && this.childBoards.length > 0 
		},
		currentPaddingLevel() {
			return this.boardLevel * this.paddingIdentend
		},
		nextPaddingLevel() {
			return this.boardLevel + 1
		}
	},
}
</script>

<style lang="scss" scoped>

	.board-list-bullet-cell {
		padding: 6px 15px;

		.board-list-bullet {
			border-radius: 50%;
			cursor: pointer;
			height: 32px;
			width: 32px;
		}
	}

	.board-list-title-cell {
		padding: 0 15px;
	}

	.sub-board-list {
		padding-left: 17px;
	}

	.board-list-avatars-cell {
		display: flex;
		padding: 6px 15px;

		.board-list-avatar {
			border-radius: 50%;
			height: 32px;
			width: 32px;
			margin-left: 3px;
			&.icon-more {
				background-color:var(--color-background-dark);
			}
		}
	}

</style>
