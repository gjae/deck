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
	<v-collapse-wrapper  ref="collapse_content" class="bullet-area">
		<div :id="`board-${board.id}`"
			:title="board.title"
			class="board-list-row"
			tag="div">

			<div v-show="!editing" v-collapse-toggle >
				<div  v-if="!isFetching" class="board-list-bullet-cell"  @click.prevent.stop="toggleCollapse" >
					<div :style="{ 'background-color': getColor }"  class="board-list-bullet bullet-color"/>
					<div :class="{'icon-triangle-e': !bulletOpen, 'icon-triangle-s': bulletOpen, 'bullet-caret': true}" />
				</div>
				<div v-else-if="isFetching" style="text-align: center; margin-left: 22px;">
					<div :class="{'icon-loading': isFetching}" />
				</div>
			</div>

			<div
				v-show="!editing"  
				class="board-list-title-cell"
				@click="navigateClick(routeTo)">
				{{ boardTitle }}
			</div>
		
			<div v-show="editing" class="board-list-title-cell board-edit">
				<ColorPicker class="app-navigation-entry-bullet-wrapper" :value="`#${board.color}`" @input="updateColor">
					<div :style="{ backgroundColor: getColor }" class="color0 icon-colorpicker app-navigation-entry-bullet" />
				</ColorPicker>
				<form @submit.prevent.stop="applyEdit">
					<input @keydown.esc="cancelEditing" v-model="editTitle" type="text" required>
					<input type="submit" value="" class="icon-confirm">
					<Actions>
						<ActionButton 
						icon="icon-close" 
						@click.stop.prevent="cancelEditing" />
					</Actions>
				</form>
			</div>
			<div class="board-list-avatars-cell" title="" v-show="!editing">
				<Avatar :user="board.owner.uid" :display-name="board.owner.displayname" class="board-list-avatar" />
				<Avatar v-for="user in limitedAcl"
					:key="user.id"
					:user="user.participant.uid"
					:display-name="user.participant.displayname"
					class="board-list-avatar" />
				<div v-if="board.acl.length > 5" v-tooltip="otherAcl" class="avatardiv popovermenu-wrapper board-list-avatar icon-more" />
			</div>
			<div class="board-list-actions-cell"  v-show="!editing" >
				<Actions>
					<ActionButton
						icon="icon-rename"
						 @click.prevent.stop="activeEditItem" 
						 v-show="canManage"
						:close-after-click="true"
					>
						{{ t('deck', 'Edit board ') }}
					</ActionButton>
					<ActionButton
						icon="icon-delete"
						v-show="canManage"
						:close-after-click="true"
						@click="actionDelete"
					>
						{{ t('deck', 'Delete board ') }}
					</ActionButton>
					<ActionButton
						v-if="canManage"
						:close-after-click="true"
						icon="icon-add"
						@click="actionNewSubboard">
						{{  t('deck', 'Add new sub-board')  }}
					</ActionButton>
				</Actions>
			</div>
		</div>
		<div
			:close-after-submit="true"
			v-show="enableNewSubboardForm"
			class="board-edit"
			:style="{
				marginLeft: 65,
			}"
		>
			<form @submit.prevent.stop="saveSubboard">
				<input 
					v-model="newSubboardName"  
					:placeholder="t('deck', 'New board title')" 
					type="text" required />
				<input type="submit" value="" class="icon-confirm">
				<Actions>
					<ActionButton 
						icon="icon-close" 
						@click.stop.prevent="cancelAddNewSubboard" />
					</Actions>
			</form>
		</div>
		<div class="content" v-collapse-content v-show="collapseContent">
			<div style="align-items: center;">
			</div>
			<div  
				v-show="canDisplayBoardChildren"  
				v-for="board in childBoards" :key="board.id" 
				class="sub-board-list">
				<BoardItem :onSubboardDelete="removeBoard" :board="board" :boardLevel="nextPaddingLevel" />
			</div>
		</div>
	</v-collapse-wrapper>
</template>

<script>
import { Avatar,  ColorPicker, Actions, ActionButton } from '@nextcloud/vue'
import ClickOutside from 'vue-click-outside'

export default {
	name: 'BoardItem',
	components: {
		Avatar,
		ColorPicker,
		Actions,
		ActionButton,
	},
	directives: {
		ClickOutside,
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
		onSubboardDelete: {
			default: null
		}
	},
	data() {
		return  {
			collapseContent: false,
			childBoards: [],
			isFetching: false,
			bulletOpen: false,
			editing: false,
			editTitle: '',
			editColor: '',
			// DATOS DEL BOARD
			boardColor: '',
			boardTitle: '',
			enableNewSubboardForm: false,
			newSubboardName: ''
		}
	},
	methods: {
		toggleCollapse(e) {
			this.onBullet()
			// Only fetch if childBoards dont has items
			if( this.collapseContent && this.childBoards.length == 0 ){ 
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
		activeEditItem(e) {
			this.editTitle = this.board.title
			this.editColor = '#' + this.board.color
			this.editing = true
		},
		cancelEditing(e) {
			this.editTitle = this.boardTitle
			this.editColor = this.board.color
			this.editing = false
		},
		updateColor(newColor) {
			this.editColor = newColor
			this.boardColor= newColor
		},
		applyEdit(e) {
			this.editing = false
			if (this.editTitle || this.editColor) {
				this.loading = true
				const copy = JSON.parse(JSON.stringify(this.board))
				copy.title = this.editTitle
				copy.color = (typeof this.editColor.hex !== 'undefined' ? this.editColor.hex : this.editColor).substring(1)
				copy.belongs_board_id = this.board.BelongsBoardId
				this.$store.dispatch('updateBoard', copy)
					.then(() => {
						this.boardTitle = this.editTitle
						this.boardColor = this.boardColor
					})
					.catch(()=> {
						this.boardTitle = this.board.title
						this.boardColor = '#'+this.board.color
					})
					.finally(()=> this.loading = false)
			}
		},
		navigateClick(routeTo) {
			if( !this.editing ){
				this.$router.push(routeTo)
			}
		},
		actionDelete() {
			OC.dialogs.confirmDestructive(
				t('deck', 'Are you sure you want to delete the board {title}? This will delete all the data of this board.', { title: this.board.title }),
				t('deck', 'Delete the board?'),
				{
					type: OC.dialogs.YES_NO_BUTTONS,
					confirm: t('deck', 'Delete'),
					confirmClasses: 'error',
					cancel: t('deck', 'Cancel'),
				},
				(result) => {
					if (result) {
						this.loading = true
						this.boardApi.deleteBoard(this.board)
							.then(() => {
								this.loading = false
								this.deleted = true
								this.undoTimeoutHandle = setTimeout(() => {
									this.$store.dispatch('removeBoard', this.board)
									if( this.onSubboardDelete != null)
										this.onSubboardDelete(this.board)
								}, 1000)
							})
					}
				},
				true
			)
		},
		actionNewSubboard(board_id = null)	{
			this.enableNewSubboardForm = true;
			this.onBullet()
		},
		cancelAddNewSubboard(e){
			this.enableNewSubboardForm = false
			this.newSubboardName = ''
			this.onBullet()
		},
		onBullet() {

			this.collapseContent = !this.collapseContent
			this.bulletOpen = !this.bulletOpen
		},
		saveSubboard(e){
			this.$store.dispatch('createSubBoard', {
				title: this.newSubboardName,
				color: '000000',
				parent: this.board.id
			}).then( (board)=> {
				this.toggleCollapse()
			} )
			this.cancelAddNewSubboard()
		},
		removeBoard(board) {
			console.log("REMOVEING BOARD", board, this.board)
			if( board.belongs_board_id == this.board.id || board.belongsBoardId == this.board.id  )
			{
				this.childBoards = this.childBoards.filter( (item)=> item.id != board.id )
			}
		}
	},
	mounted() {
		this.boardTitle = this.board.title
		this.boardColor = '#'+this.board.color
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
		canManage() {
			return this.board.permissions.PERMISSION_MANAGE
		},
		currentPaddingLevel() {
			return this.boardLevel * this.paddingIdentend
		},
		nextPaddingLevel() {
			return this.boardLevel + 1
		},
		getColor() {
			return this.boardColor
		},
	},
	inject: [
		'boardApi',
	],
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

	.bullet-caret {
		display: none;
	}

	.edit-button {
		display: none;
	}

	.bullet-area:hover {
		
		.bullet-color {
			display: none;
		}

		.bullet-caret {
			display: block; 
		}

		.edit-button {
			display: inline;
		}
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

	.board-edit {
		margin-left: 44px;
		order: 1;
		display: flex;
		height: 44px;

		form {
			display: flex;
			flex-grow: 1;

			input[type="text"] {
				flex-grow: 1;
			}
		}
	}

	.app-navigation-entry-bullet-wrapper {
		width: 44px;
		height: 44px;
		.color0 {
			width: 30px !important;
			margin: 5px;
			margin-left: 7px;
			height: 30px;
			border-radius: 50%;
			background-size: 14px;
		}
	}
</style>
